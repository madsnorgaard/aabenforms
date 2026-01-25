<?php

namespace Drupal\Tests\aabenforms_workflows\Kernel\Eca;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests ECA workflow actions.
 *
 * @group aabenforms_workflows
 */
class WorkflowActionsTest extends KernelTestBase {

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
    'aabenforms_workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('webform', ['webform']);
    $this->installConfig(static::$modules);
  }

  /**
   * Tests ECA module integration placeholder.
   */
  public function testEcaModuleIntegration() {
    // Placeholder test - verify ECA modules load correctly.
    $moduleHandler = \Drupal::moduleHandler();
    $this->assertTrue($moduleHandler->moduleExists('eca'), 'ECA module is enabled');
    $this->assertTrue($moduleHandler->moduleExists('aabenforms_workflows'), 'ÅbenForms Workflows module is enabled');
  }

}
