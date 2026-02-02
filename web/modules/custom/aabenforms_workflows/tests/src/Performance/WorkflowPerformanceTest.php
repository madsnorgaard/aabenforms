<?php

namespace Drupal\Tests\aabenforms_workflows\Performance;

use Drupal\KernelTests\KernelTestBase;

/**
 * Performance tests for workflow execution.
 *
 * @group aabenforms_workflows
 * @group performance
 */
class WorkflowPerformanceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'eca',
    'eca_base',
    'aabenforms_core',
    'aabenforms_workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Test action plugin instantiation time.
   */
  public function testActionPluginInstantiationPerformance(): void {
    $action_manager = \Drupal::service('plugin.manager.action');

    $start = microtime(TRUE);

    // Instantiate all 4 action plugins 100 times.
    $actions = [
      'aabenforms_mitid_validate',
      'aabenforms_cpr_lookup',
      'aabenforms_cvr_lookup',
      'aabenforms_audit_log',
    ];

    for ($i = 0; $i < 100; $i++) {
      foreach ($actions as $action_id) {
        $action_manager->createInstance($action_id, []);
      }
    }

    $duration = microtime(TRUE) - $start;

    $this->assertLessThan(1.0, $duration,
      "Plugin instantiation took {$duration}s, should be under 1s");
  }

  /**
   * Test token service performance.
   */
  public function testTokenGenerationPerformance(): void {
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');

    $start = microtime(TRUE);

    // Generate 1000 tokens.
    for ($i = 0; $i < 1000; $i++) {
      $token_service->generateToken($i, 1);
    }

    $duration = microtime(TRUE) - $start;

    $this->assertLessThan(1.0, $duration,
      "Token generation took {$duration}s for 1000 tokens, should be under 1s");
  }

  /**
   * Test token validation performance.
   */
  public function testTokenValidationPerformance(): void {
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');

    // Generate test token.
    $token = $token_service->generateToken(123, 1);

    $start = microtime(TRUE);

    // Validate token 1000 times.
    for ($i = 0; $i < 1000; $i++) {
      $token_service->validateToken(123, 1, $token);
    }

    $duration = microtime(TRUE) - $start;

    $this->assertLessThan(0.5, $duration,
      "Token validation took {$duration}s for 1000 validations, should be under 0.5s");
  }

  /**
   * Test BPMN template loading performance.
   */
  public function testBpmnTemplateLoadingPerformance(): void {
    $template_manager = \Drupal::service('aabenforms_workflows.bpmn_template_manager');

    $start = microtime(TRUE);

    // Get available templates.
    $templates = $template_manager->getAvailableTemplates();

    // Load each template 10 times.
    foreach (array_keys($templates) as $template_id) {
      for ($i = 0; $i < 10; $i++) {
        $template_manager->loadTemplate($template_id);
      }
    }

    $duration = microtime(TRUE) - $start;

    $this->assertLessThan(2.0, $duration,
      "Template loading took {$duration}s, should be under 2s");
  }

  /**
   * Test BPMN template validation performance.
   */
  public function testBpmnTemplateValidationPerformance(): void {
    $template_manager = \Drupal::service('aabenforms_workflows.bpmn_template_manager');

    // Get available templates.
    $templates = $template_manager->getAvailableTemplates();

    if (empty($templates)) {
      $this->markTestSkipped('No BPMN templates available for testing');
    }

    $start = microtime(TRUE);

    // Validate each template 5 times.
    foreach (array_keys($templates) as $template_id) {
      for ($i = 0; $i < 5; $i++) {
        $template_manager->validateTemplate($template_id);
      }
    }

    $duration = microtime(TRUE) - $start;

    $template_count = count($templates);
    $this->assertLessThan(1.0, $duration,
      "Template validation took {$duration}s for {$template_count} templates x 5, should be under 1s");
  }

  /**
   * Test workflow service memory usage.
   */
  public function testWorkflowServiceMemoryUsage(): void {
    $initial_memory = memory_get_usage();

    // Load all workflow services.
    $token_service = \Drupal::service('aabenforms_workflows.approval_token');
    $template_manager = \Drupal::service('aabenforms_workflows.bpmn_template_manager');
    $action_manager = \Drupal::service('plugin.manager.action');

    // Perform typical operations.
    $template_manager->getAvailableTemplates();
    $token_service->generateToken(123, 1);
    $action_manager->createInstance('aabenforms_audit_log', []);

    $memory_used = memory_get_usage() - $initial_memory;
    $memory_mb = $memory_used / 1024 / 1024;

    $this->assertLessThan(10, $memory_mb,
      "Workflow services used {$memory_mb}MB, should be under 10MB");
  }

}
