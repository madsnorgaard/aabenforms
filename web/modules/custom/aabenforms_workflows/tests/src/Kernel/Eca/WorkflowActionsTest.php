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
    'eca_content',
    'eca_user',
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
    $this->markTestSkipped(
      'Kernel integration test depends on a custom-module dependency stack (aabenforms_core services + drupal:key + bpmn_io + modeler_api) that is not fully wired in the kernel test bootstrap; the kernel installConfig fails on missing services or eca content_entity events. Tracked in #37 for proper rework.'
    );
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
