<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Service;

use Drupal\aabenforms_workflows\Service\ConfigOrgChartService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the config-backed org-chart directory.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Service\ConfigOrgChartService
 * @group aabenforms_workflows
 */
class ConfigOrgChartServiceTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\aabenforms_workflows\Service\ConfigOrgChartService
   */
  protected ConfigOrgChartService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $values = [
      'default_tier_limit_cents' => 200000,
      'employees' => [
        'E1001' => [
          'name' => 'Administrator',
          'account_name' => 'admin',
          'manager_email' => 'leder@example.dk',
          'tier_limit_cents' => 500000,
        ],
      ],
    ];
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(fn ($key) => $values[$key] ?? NULL);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $this->service = new ConfigOrgChartService($configFactory);
  }

  /**
   * @covers ::findManagerEmail
   */
  public function testFindManagerEmail(): void {
    $this->assertSame('leder@example.dk', $this->service->findManagerEmail('E1001'));
    // Unknown employee resolves to '' - never the self-asserted fallback.
    $this->assertSame('', $this->service->findManagerEmail('E9999', 'spoof@evil.dk'));
  }

  /**
   * @covers ::tierLimitCents
   */
  public function testTierLimitCents(): void {
    $this->assertSame(500000, $this->service->tierLimitCents('E1001'));
    $this->assertSame(200000, $this->service->tierLimitCents('E9999'));
  }

  /**
   * @covers ::employeeIdForAccountName
   */
  public function testEmployeeIdForAccountName(): void {
    $this->assertSame('E1001', $this->service->employeeIdForAccountName('admin'));
    $this->assertSame('', $this->service->employeeIdForAccountName('nobody'));
    $this->assertSame('', $this->service->employeeIdForAccountName(''));
  }

}
