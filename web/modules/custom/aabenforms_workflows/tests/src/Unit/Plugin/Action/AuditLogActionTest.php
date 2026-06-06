<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\aabenforms_core\Service\CprAccess;
use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\AuditLogAction;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface as EcaTokenInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the AuditLogAction ECA plugin.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\AuditLogAction
 * @group aabenforms_workflows
 */
class AuditLogActionTest extends UnitTestCase {

  /**
   * The AuditLogAction plugin.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\AuditLogAction
   */
  protected AuditLogAction $action;

  /**
   * Mock audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $auditLogger;

  /**
   * Mock logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock ECA token services.
   *
   * @var \Drupal\eca\Token\TokenInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tokenServices;

  /**
   * Token storage for testing.
   *
   * @var array
   */
  protected array $tokenStorage = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock all ECA ActionBase dependencies.
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $currentUser = $this->createMock(AccountProxyInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $ecaState = $this->createMock(EcaState::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    // Mock ECA token services.
    $this->tokenServices = $this->createMock(EcaTokenInterface::class);
    $this->tokenServices->method('getTokenData')
      ->willReturnCallback(
              function ($name) {
                  return $this->tokenStorage[$name] ?? NULL;
              }
          );
    $this->tokenServices->method('addTokenData')
      ->willReturnCallback(
              function ($name, $value) {
                  $this->tokenStorage[$name] = $value;
                  return $this->tokenServices;
              }
          );

    // Mock audit logger.
    $this->auditLogger = $this->getMockBuilder(AuditLogger::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['log', 'logCprLookup', 'logCvrLookup', 'logWorkflowAccess'])
      ->getMock();

    // Create action instance.
    $configuration = [
      'event_type' => 'workflow_action',
      'cpr_token' => 'cpr',
      'message_template' => 'Workflow action executed',
      'additional_data_token' => '',
    ];

    $this->action = new AuditLogAction(
          $configuration,
          'aabenforms_audit_log',
          [],
          $entityTypeManager,
          $this->tokenServices,
          $currentUser,
          $time,
          $ecaState,
          $this->logger
      );
    $this->action->setExecutionCollector($this->createMock(WorkflowExecutionCollector::class));

    // Inject audit logger using reflection.
    $reflectionClass = new \ReflectionClass($this->action);
    $auditLoggerProperty = $reflectionClass->getProperty('auditLogger');
    $auditLoggerProperty->setAccessible(TRUE);
    $auditLoggerProperty->setValue($this->action, $this->auditLogger);

    // CPR access helper: reveal is a pass-through for these tests.
    $cprAccess = $this->createMock(CprAccess::class);
    $cprAccess->method('reveal')->willReturnArgument(0);
    $cprAccessProperty = $reflectionClass->getProperty('cprAccess');
    $cprAccessProperty->setAccessible(TRUE);
    $cprAccessProperty->setValue($this->action, $cprAccess);
  }

  /**
   * Tests audit log entry creation.
   *
   * @covers ::execute
   */
  public function testLogEntryCreation(): void {
    // Set test data.
    $this->tokenStorage['cpr'] = '0101001234';

    // Default config: event_type='workflow_action', message='Workflow action
    // executed'. Falls into the else branch of execute(); the AuditLogger's
    // generic log() is the right entry point for the multi-event-type action.
    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        'workflow_action',
        '0101001234',
        'Workflow action executed',
        'success',
        $this->callback(function ($context) {
          return isset($context['action_id'])
            && $context['action_id'] === 'aabenforms_audit_log';
        })
      );

    // Execute action.
    $this->action->execute();
  }

  /**
   * Tests structured data capture in metadata.
   *
   * @covers ::execute
   */
  public function testStructuredDataCapture(): void {
    $additionalData = [
      'workflow_id' => 'workflow-123',
      'step' => 'approval',
      'decision' => 'approved',
    ];

    // Set tokens.
    $this->tokenStorage['additional_data'] = $additionalData;

    // Update action configuration to use additional data token.
    $reflectionClass = new \ReflectionClass($this->action);
    $configProperty = $reflectionClass->getProperty('configuration');
    $configProperty->setAccessible(TRUE);
    $config = $configProperty->getValue($this->action);
    $config['additional_data_token'] = 'additional_data';
    $configProperty->setValue($this->action, $config);

    // Expect log with additional data merged into the context (5th arg).
    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->anything(),
        $this->anything(),
        $this->callback(
          function ($context) {
            return isset($context['workflow_id'])
              && $context['workflow_id'] === 'workflow-123'
              && isset($context['decision'])
              && $context['decision'] === 'approved'
              && isset($context['step'])
              && $context['step'] === 'approval';
          }
        )
      );

    // Execute action.
    $this->action->execute();
  }

  /**
   * Tests tenant context inclusion.
   *
   * @covers ::execute
   */
  public function testTenantContext(): void {
    // Set tenant context in tokens.
    $this->tokenStorage['tenant_id'] = 'aarhus-kommune';
    $this->tokenStorage['tenant_name'] = 'Aarhus Kommune';

    // Update message template to include tokens.
    $reflectionClass = new \ReflectionClass($this->action);
    $configProperty = $reflectionClass->getProperty('configuration');
    $configProperty->setAccessible(TRUE);
    $config = $configProperty->getValue($this->action);
    $config['message_template'] = 'Action by [tenant_name] (ID: [tenant_id])';
    $configProperty->setValue($this->action, $config);

    // Token-replaced message lands in the purpose slot (3rd arg).
    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        $this->anything(),
        $this->anything(),
        'Action by Aarhus Kommune (ID: aarhus-kommune)',
        $this->anything(),
        $this->anything()
      );

    // Execute action.
    $this->action->execute();
  }

  /**
   * Tests GDPR compliance with CPR masking.
   *
   * @covers ::execute
   */
  public function testGdprCompliance(): void {
    $cpr = '010100-1234';

    // Set CPR token with hyphen (should be normalized).
    $this->tokenStorage['cpr'] = $cpr;

    // Update configuration to CPR access event.
    $reflectionClass = new \ReflectionClass($this->action);
    $configProperty = $reflectionClass->getProperty('configuration');
    $configProperty->setAccessible(TRUE);
    $config = $configProperty->getValue($this->action);
    $config['event_type'] = 'cpr_access';
    $configProperty->setValue($this->action, $config);

    // CPR-access branch: action='cpr_access', identifier=normalized CPR,
    // purpose='workflow_action' (hardcoded by execute()), context contains
    // the original message under 'message'.
    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        'cpr_access',
        '0101001234',
        'workflow_action',
        'success',
        $this->callback(function ($context) {
          return isset($context['message'])
            && $context['message'] === 'Workflow action executed';
        })
      );

    // Execute action.
    $this->action->execute();
  }

  /**
   * Tests batch logging multiple entries.
   *
   * @covers ::execute
   */
  public function testBatchLogging(): void {
    // First execution.
    $this->auditLogger->expects($this->exactly(2))
      ->method('log');

    $this->action->execute();

    // Second execution (simulating batch processing).
    $this->action->execute();
  }

  /**
   * Tests token replacement in message template.
   *
   * @covers ::replaceTokensInString
   */
  public function testTokenReplacement(): void {
    // Set various token types.
    $this->tokenStorage['user_name'] = 'John Doe';
    $this->tokenStorage['workflow_id'] = 'workflow-456';
    $this->tokenStorage['action_count'] = 5;

    // Update message template with multiple tokens.
    $reflectionClass = new \ReflectionClass($this->action);
    $configProperty = $reflectionClass->getProperty('configuration');
    $configProperty->setAccessible(TRUE);
    $config = $configProperty->getValue($this->action);
    $config['message_template'] = 'User [user_name] completed [action_count] actions in [workflow_id]';
    $configProperty->setValue($this->action, $config);

    // Token-replaced message lands in the purpose slot (3rd arg).
    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        $this->anything(),
        $this->anything(),
        'User John Doe completed 5 actions in workflow-456',
        $this->anything(),
        $this->anything()
      );

    // Execute action.
    $this->action->execute();
  }

  /**
   * Tests error handling.
   *
   * @covers ::execute
   */
  public function testErrorHandling(): void {
    // Mock audit logger throwing exception.
    $this->auditLogger->expects($this->once())
      ->method('log')
      ->willThrowException(new \Exception('Database connection error'));

    // handleError() routes through the protected base log() at 'error' level,
    // which reaches the injected channel as logger->error().
    $this->logger->expects($this->once())
      ->method('error');

    // Execute action (should not throw).
    $this->action->execute();
  }

  /**
   * A status token resolving to a failed outcome is logged as failure.
   *
   * @covers ::execute
   * @covers ::resolveStatus
   * @covers ::normalizeStatus
   */
  public function testStatusTokenReflectsFailure(): void {
    // A prior CPR lookup that found nothing.
    $this->tokenStorage['citizen_data_status'] = 'not_found';

    $reflectionClass = new \ReflectionClass($this->action);
    $configProperty = $reflectionClass->getProperty('configuration');
    $configProperty->setAccessible(TRUE);
    $config = $configProperty->getValue($this->action);
    $config['status_token'] = 'citizen_data_status';
    $configProperty->setValue($this->action, $config);

    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->anything(),
        'failure',
        $this->anything()
      );

    $this->action->execute();
  }

  /**
   * A status token resolving to a verified outcome is logged as success.
   *
   * @covers ::execute
   * @covers ::resolveStatus
   * @covers ::normalizeStatus
   */
  public function testStatusTokenReflectsSuccess(): void {
    $this->tokenStorage['citizen_mitid_valid_status'] = 'verified';

    $reflectionClass = new \ReflectionClass($this->action);
    $configProperty = $reflectionClass->getProperty('configuration');
    $configProperty->setAccessible(TRUE);
    $config = $configProperty->getValue($this->action);
    $config['status_token'] = 'citizen_mitid_valid_status';
    $configProperty->setValue($this->action, $config);

    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->anything(),
        'success',
        $this->anything()
      );

    $this->action->execute();
  }

  /**
   * The fixed status is used (and normalised) when no status token is set.
   *
   * @covers ::execute
   * @covers ::resolveStatus
   */
  public function testFixedStatusUsedWhenNoToken(): void {
    $reflectionClass = new \ReflectionClass($this->action);
    $configProperty = $reflectionClass->getProperty('configuration');
    $configProperty->setAccessible(TRUE);
    $config = $configProperty->getValue($this->action);
    $config['status'] = 'denied';
    $configProperty->setValue($this->action, $config);

    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->anything(),
        'denied',
        $this->anything()
      );

    $this->action->execute();
  }

  /**
   * Tests default configuration.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration(): void {
    $defaults = $this->action->defaultConfiguration();

    $this->assertArrayHasKey('event_type', $defaults);
    $this->assertEquals('workflow_action', $defaults['event_type']);

    $this->assertArrayHasKey('cpr_token', $defaults);
    $this->assertEquals('cpr', $defaults['cpr_token']);

    $this->assertArrayHasKey('message_template', $defaults);
    $this->assertEquals('Workflow action executed', $defaults['message_template']);

    $this->assertArrayHasKey('additional_data_token', $defaults);
    $this->assertEquals('', $defaults['additional_data_token']);

    $this->assertArrayHasKey('status', $defaults);
    $this->assertEquals('success', $defaults['status']);

    $this->assertArrayHasKey('status_token', $defaults);
    $this->assertEquals('', $defaults['status_token']);
  }

}
