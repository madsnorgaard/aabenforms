<?php

namespace Drupal\Tests\aabenforms_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests JSON:API integration.
 *
 * @group aabenforms_core
 */
class JsonApiIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'file',
    'jsonapi',
    'serialization',
    'aabenforms_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['aabenforms_core']);
  }

  /**
   * Tests JSON:API resource availability placeholder.
   */
  public function testJsonApiResourcesAvailable() {
    // Placeholder test - verify module loads correctly.
    $moduleHandler = \Drupal::moduleHandler();
    $this->assertTrue($moduleHandler->moduleExists('aabenforms_core'), 'ÅbenForms Core module is enabled');
    $this->assertTrue($moduleHandler->moduleExists('jsonapi'), 'JSON:API module is enabled');
  }

}
