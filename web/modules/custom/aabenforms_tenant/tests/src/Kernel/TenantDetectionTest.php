<?php

namespace Drupal\Tests\aabenforms_tenant\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests tenant domain detection.
 *
 * @group aabenforms_tenant
 */
class TenantDetectionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'domain',
    'aabenforms_core',
    'aabenforms_tenant',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('domain');
    $this->installConfig(['domain', 'aabenforms_tenant']);
  }

  /**
   * Tests tenant detection placeholder.
   */
  public function testTenantDetection() {
    // Placeholder test - verify modules load correctly
    $moduleHandler = \Drupal::moduleHandler();
    $this->assertTrue($moduleHandler->moduleExists('aabenforms_tenant'), 'ÅbenForms Tenant module is enabled');
    $this->assertTrue($moduleHandler->moduleExists('domain'), 'Domain module is enabled');
  }

}
