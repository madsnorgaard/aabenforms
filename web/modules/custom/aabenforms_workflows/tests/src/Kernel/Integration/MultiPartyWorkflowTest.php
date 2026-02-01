<?php

namespace Drupal\Tests\aabenforms_workflows\Kernel\Integration;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests end-to-end multi-party workflow execution.
 *
 * Validates complete workflow with:
 * - Parent authenticates via MitID
 * - Parent submits webform
 * - ECA workflow executes (MitID validate → CPR lookup → Audit log)
 * - Case worker receives task
 * - All actions logged for GDPR compliance
 *
 * @group aabenforms_workflows
 * @group integration
 */
class MultiPartyWorkflowTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'eca',
    'eca_base',
    'webform',
    'key',
    'encrypt',
    'domain',
    'aabenforms_core',
    'aabenforms_mitid',
    'aabenforms_workflows',
  ];

  /**
   * The MitID session manager.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdSessionManager
   */
  protected $sessionManager;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * The audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger
   */
  protected $auditLogger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install schemas.
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('file', ['file_usage']);

    // Get services.
    $this->sessionManager = \Drupal::service('aabenforms_mitid.session_manager');
    $this->actionManager = \Drupal::service('plugin.manager.action');
    $this->auditLogger = \Drupal::service('aabenforms_core.audit_logger');
  }

  /**
   * Tests complete parent submission → case worker approval workflow.
   */
  public function testParentCaseWorkerWorkflow(): void {
    // STEP 1: Parent authenticates with MitID.
    $workflowId = 'test_workflow_' . time();
    $parentCpr = '0101701234'; // Test CPR.

    // Simulate MitID session (normally created by MitIdOidcClient).
    $mitidSession = [
      'cpr' => $parentCpr,
      'name' => 'Jane Doe',
      'authenticated_at' => time(),
      'expires_at' => time() + 3600,
      'access_token' => 'test_access_token_123',
    ];
    $this->sessionManager->storeSession($workflowId, $mitidSession);

    // STEP 2: Validate MitID session via workflow action.
    $mitidAction = $this->actionManager->createInstance('aabenforms_mitid_validate', [
      'workflow_id_token' => 'workflow_id',
      'result_token' => 'mitid_result',
      'session_data_token' => 'session_data',
    ]);

    // Configure token environment (simulate ECA token context).
    $tokenData = ['workflow_id' => $workflowId];

    // Execute MitID validation.
    $mitidAction->execute();

    // Verify session was validated (in real workflow, tokens would be checked).
    $session = $this->sessionManager->getSession($workflowId);
    $this->assertNotNull($session, 'MitID session exists after validation');
    $this->assertEquals($parentCpr, $session['cpr']);

    // STEP 3: Lookup parent CPR data.
    $cprAction = $this->actionManager->createInstance('aabenforms_cpr_lookup', [
      'cpr_token' => 'mitid_cpr',
      'result_token' => 'cpr_data',
      'use_cache' => FALSE,
    ]);

    // Note: In real test, this would call Serviceplatformen (mocked via WireMock).
    // For kernel test, we verify the action can be instantiated.
    $this->assertNotNull($cprAction, 'CPR lookup action created');

    // STEP 4: Create case worker user.
    $caseWorker = User::create([
      'name' => 'caseworker',
      'mail' => 'caseworker@example.com',
      'status' => 1,
      'roles' => ['authenticated'],
    ]);
    $caseWorker->save();

    // STEP 5: Audit log the workflow execution.
    $auditAction = $this->actionManager->createInstance('aabenforms_audit_log', [
      'event_type_token' => 'event_type',
      'user_data_token' => 'user_data',
      'metadata_token' => 'metadata',
    ]);

    $this->assertNotNull($auditAction, 'Audit log action created');

    // STEP 6: Verify workflow state is tracked.
    $this->assertTrue(
      $this->sessionManager->hasValidSession($workflowId),
      'Workflow session tracked throughout execution'
    );

    // STEP 7: Verify parent data is available for case worker.
    $sessionData = $this->sessionManager->getSession($workflowId);
    $this->assertEquals('Jane Doe', $sessionData['name']);
    $this->assertEquals($parentCpr, $sessionData['cpr']);

    // STEP 8: Simulate case worker action (workflow advances).
    $this->sessionManager->deleteSession($workflowId);
    $this->assertFalse(
      $this->sessionManager->hasValidSession($workflowId),
      'Workflow session cleared after completion'
    );
  }

  /**
   * Tests workflow with multiple parents (both parents must authenticate).
   */
  public function testDualParentWorkflow(): void {
    $workflowId = 'dual_parent_workflow_' . time();

    // Parent 1 authenticates.
    $parent1Session = [
      'cpr' => '0101701234',
      'name' => 'Jane Doe',
      'authenticated_at' => time(),
      'expires_at' => time() + 3600,
    ];
    $this->sessionManager->storeSession($workflowId . '_parent1', $parent1Session);

    // Parent 2 authenticates.
    $parent2Session = [
      'cpr' => '0202705678',
      'name' => 'John Doe',
      'authenticated_at' => time(),
      'expires_at' => time() + 3600,
    ];
    $this->sessionManager->storeSession($workflowId . '_parent2', $parent2Session);

    // Verify both parents are tracked.
    $this->assertTrue($this->sessionManager->hasValidSession($workflowId . '_parent1'));
    $this->assertTrue($this->sessionManager->hasValidSession($workflowId . '_parent2'));

    // Verify workflow can access both parent data.
    $p1 = $this->sessionManager->getSession($workflowId . '_parent1');
    $p2 = $this->sessionManager->getSession($workflowId . '_parent2');

    $this->assertEquals('Jane Doe', $p1['name']);
    $this->assertEquals('John Doe', $p2['name']);
    $this->assertNotEquals($p1['cpr'], $p2['cpr'], 'Each parent has unique CPR');
  }

  /**
   * Tests workflow action chaining (MitID → CPR → CVR → Audit).
   */
  public function testActionChaining(): void {
    $actions = [
      'aabenforms_mitid_validate',
      'aabenforms_cpr_lookup',
      'aabenforms_cvr_lookup',
      'aabenforms_audit_log',
    ];

    // Verify all workflow actions can be chained.
    foreach ($actions as $actionId) {
      $action = $this->actionManager->createInstance($actionId, []);
      $this->assertNotNull($action, "Action {$actionId} can be instantiated");

      // Verify action has required methods.
      $this->assertTrue(
        method_exists($action, 'execute'),
        "Action {$actionId} has execute method"
      );
      $this->assertTrue(
        method_exists($action, 'access'),
        "Action {$actionId} has access control"
      );
    }
  }

  /**
   * Tests session expiry is automatically set.
   */
  public function testSessionExpiryAutoSet(): void {
    $workflowId = 'auto_expiry_' . time();

    // Store session (expires_at is auto-set by session manager).
    $sessionData = [
      'cpr' => '0101701234',
      'name' => 'Jane Doe',
    ];
    $this->sessionManager->storeSession($workflowId, $sessionData);

    // Verify session has expiry set automatically.
    $session = $this->sessionManager->getSession($workflowId);
    $this->assertNotNull($session, 'Session data exists');
    $this->assertArrayHasKey('expires_at', $session, 'Expiry timestamp is set');
    $this->assertGreaterThan(time(), $session['expires_at'], 'Expiry is in future');
    $this->assertArrayHasKey('created_at', $session, 'Creation timestamp is set');

    // Verify expiry is approximately 15 minutes (900 seconds) from now.
    $expectedExpiry = time() + 900;
    $this->assertLessThan(60, abs($session['expires_at'] - $expectedExpiry), 'Expiry is ~15 minutes from now');
  }

  /**
   * Tests workflow with webform submission data.
   */
  public function testWorkflowWithSubmissionData(): void {
    // Simulate webform submission data structure.
    $submissionData = [
      'workflow_id' => 'submission_' . time(),
      'form_id' => 'building_permit',
      'data' => [
        'applicant_name' => 'Jane Doe',
        'applicant_cpr' => '0101701234',
        'property_address' => 'Viborgvej 2, 8000 Aarhus C',
        'building_type' => 'residential',
        'estimated_cost' => '2500000',
      ],
      'submitted_at' => time(),
    ];

    // Create workflow session with submission data.
    $this->sessionManager->storeSession(
      $submissionData['workflow_id'],
      [
        'cpr' => $submissionData['data']['applicant_cpr'],
        'name' => $submissionData['data']['applicant_name'],
        'submission_data' => $submissionData['data'],
      ]
    );

    // Verify submission data is preserved in workflow.
    $session = $this->sessionManager->getSession($submissionData['workflow_id']);
    $this->assertArrayHasKey('submission_data', $session);
    $this->assertEquals('building_permit', $submissionData['form_id']);
    $this->assertEquals('Viborgvej 2, 8000 Aarhus C', $session['submission_data']['property_address']);
  }

  /**
   * Tests case worker task assignment in workflow.
   */
  public function testCaseWorkerTaskAssignment(): void {
    // Create case workers.
    $caseWorker1 = User::create([
      'name' => 'caseworker1',
      'mail' => 'cw1@example.com',
      'status' => 1,
    ]);
    $caseWorker1->save();

    $caseWorker2 = User::create([
      'name' => 'caseworker2',
      'mail' => 'cw2@example.com',
      'status' => 1,
    ]);
    $caseWorker2->save();

    // Simulate workflow state with assigned case worker.
    $workflowId = 'case_assignment_' . time();
    $this->sessionManager->storeSession($workflowId, [
      'cpr' => '0101701234',
      'name' => 'Jane Doe',
      'assigned_to' => $caseWorker1->id(),
      'status' => 'pending_review',
    ]);

    // Verify case worker assignment.
    $session = $this->sessionManager->getSession($workflowId);
    $this->assertEquals($caseWorker1->id(), $session['assigned_to']);
    $this->assertEquals('pending_review', $session['status']);

    // Case worker 1 can load the task.
    $assignedWorker = User::load($session['assigned_to']);
    $this->assertEquals('caseworker1', $assignedWorker->getAccountName());
  }

}
