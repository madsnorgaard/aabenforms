<?php

namespace Drupal\Tests\aabenforms_core\Unit\Service;

use Drupal\aabenforms_core\Service\TenantResolver;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests TenantResolver service.
 *
 * @coversDefaultClass \Drupal\aabenforms_core\Service\TenantResolver
 * @group aabenforms_core
 * @group tenant
 */
class TenantResolverTest extends UnitTestCase {

  /**
   * The tenant resolver service.
   *
   * @var \Drupal\aabenforms_core\Service\TenantResolver
   */
  protected TenantResolver $tenantResolver;

  /**
   * Mock domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $domainNegotiator;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock dependencies.
    $this->domainNegotiator = $this->createMock(DomainNegotiatorInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')
      ->with('aabenforms_core')
      ->willReturn($this->logger);

    // Create service.
    $this->tenantResolver = new TenantResolver(
      $this->domainNegotiator,
      $this->configFactory,
      $loggerFactory
    );
  }

  /**
   * Tests getCurrentTenant with active domain.
   *
   * @covers ::getCurrentTenant
   */
  public function testGetCurrentTenantWithActiveDomain(): void {
    $domain = $this->createMockDomain('aarhus', 'Aarhus Kommune');

    $this->domainNegotiator->expects($this->once())
      ->method('getActiveDomain')
      ->willReturn($domain);

    $result = $this->tenantResolver->getCurrentTenant();

    $this->assertSame($domain, $result);
    $this->assertEquals('aarhus', $result->id());
    $this->assertEquals('Aarhus Kommune', $result->label());
  }

  /**
   * Tests getCurrentTenant without active domain.
   *
   * @covers ::getCurrentTenant
   */
  public function testGetCurrentTenantWithoutActiveDomain(): void {
    $this->domainNegotiator->expects($this->once())
      ->method('getActiveDomain')
      ->willReturn(NULL);

    $result = $this->tenantResolver->getCurrentTenant();

    $this->assertNull($result);
  }

  /**
   * Tests getCurrentTenantId with active domain.
   *
   * @covers ::getCurrentTenantId
   */
  public function testGetCurrentTenantIdWithActiveDomain(): void {
    $domain = $this->createMockDomain('odense', 'Odense Kommune');

    $this->domainNegotiator->expects($this->once())
      ->method('getActiveDomain')
      ->willReturn($domain);

    $result = $this->tenantResolver->getCurrentTenantId();

    $this->assertEquals('odense', $result);
  }

  /**
   * Tests getCurrentTenantId without active domain.
   *
   * @covers ::getCurrentTenantId
   */
  public function testGetCurrentTenantIdWithoutActiveDomain(): void {
    $this->domainNegotiator->expects($this->once())
      ->method('getActiveDomain')
      ->willReturn(NULL);

    $result = $this->tenantResolver->getCurrentTenantId();

    $this->assertNull($result);
  }

  /**
   * Tests getTenantConfig in single-tenant context.
   *
   * @covers ::getTenantConfig
   */
  public function testGetTenantConfigSingleTenant(): void {
    // No active domain (single-tenant).
    $this->domainNegotiator->method('getActiveDomain')
      ->willReturn(NULL);

    // Mock global config.
    $globalConfig = $this->createMock(ImmutableConfig::class);
    $globalConfig->method('get')
      ->with('mitid.client_id')
      ->willReturn('global-client-id-123');

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('aabenforms_core.settings')
      ->willReturn($globalConfig);

    $result = $this->tenantResolver->getTenantConfig('mitid.client_id');

    $this->assertEquals('global-client-id-123', $result);
  }

  /**
   * Tests getTenantConfig with tenant-specific value.
   *
   * @covers ::getTenantConfig
   */
  public function testGetTenantConfigWithTenantSpecificValue(): void {
    $domain = $this->createMockDomain('aarhus', 'Aarhus Kommune');

    $this->domainNegotiator->method('getActiveDomain')
      ->willReturn($domain);

    // Mock tenant-specific config.
    $tenantConfig = $this->createMock(ImmutableConfig::class);
    $tenantConfig->method('get')
      ->with('mitid.client_id')
      ->willReturn('aarhus-client-id-456');

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('aabenforms_core.tenant.aarhus')
      ->willReturn($tenantConfig);

    $result = $this->tenantResolver->getTenantConfig('mitid.client_id');

    $this->assertEquals('aarhus-client-id-456', $result);
  }

  /**
   * Tests getTenantConfig falls back to global config.
   *
   * @covers ::getTenantConfig
   */
  public function testGetTenantConfigFallsBackToGlobal(): void {
    $domain = $this->createMockDomain('odense', 'Odense Kommune');

    $this->domainNegotiator->method('getActiveDomain')
      ->willReturn($domain);

    // Mock tenant config (returns NULL).
    $tenantConfig = $this->createMock(ImmutableConfig::class);
    $tenantConfig->method('get')
      ->with('serviceplatformen.cert_path')
      ->willReturn(NULL);

    // Mock global config.
    $globalConfig = $this->createMock(ImmutableConfig::class);
    $globalConfig->method('get')
      ->with('serviceplatformen.cert_path')
      ->willReturn('/global/path/cert.pem');

    $this->configFactory->expects($this->exactly(2))
      ->method('get')
      ->willReturnCallback(function ($config_name) use ($tenantConfig, $globalConfig) {
        if ($config_name === 'aabenforms_core.tenant.odense') {
          return $tenantConfig;
        }
        if ($config_name === 'aabenforms_core.settings') {
          return $globalConfig;
        }
        return NULL;
      });

    $result = $this->tenantResolver->getTenantConfig('serviceplatformen.cert_path');

    $this->assertEquals('/global/path/cert.pem', $result);
  }

  /**
   * Tests getTenantConfig with default value.
   *
   * @covers ::getTenantConfig
   */
  public function testGetTenantConfigWithDefaultValue(): void {
    $this->domainNegotiator->method('getActiveDomain')
      ->willReturn(NULL);

    // Mock global config (returns NULL).
    $globalConfig = $this->createMock(ImmutableConfig::class);
    $globalConfig->method('get')
      ->with('missing.key')
      ->willReturn(NULL);

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('aabenforms_core.settings')
      ->willReturn($globalConfig);

    $result = $this->tenantResolver->getTenantConfig('missing.key', 'default-value');

    $this->assertEquals('default-value', $result);
  }

  /**
   * Tests getTenantConfig multi-tenant with missing config.
   *
   * @covers ::getTenantConfig
   */
  public function testGetTenantConfigMultiTenantWithMissingConfig(): void {
    $domain = $this->createMockDomain('aalborg', 'Aalborg Kommune');

    $this->domainNegotiator->method('getActiveDomain')
      ->willReturn($domain);

    // Both tenant and global config return NULL.
    $tenantConfig = $this->createMock(ImmutableConfig::class);
    $tenantConfig->method('get')->willReturn(NULL);

    $globalConfig = $this->createMock(ImmutableConfig::class);
    $globalConfig->method('get')->willReturn(NULL);

    $this->configFactory->method('get')
      ->willReturnCallback(function ($config_name) use ($tenantConfig, $globalConfig) {
        if ($config_name === 'aabenforms_core.tenant.aalborg') {
          return $tenantConfig;
        }
        if ($config_name === 'aabenforms_core.settings') {
          return $globalConfig;
        }
        return NULL;
      });

    $result = $this->tenantResolver->getTenantConfig('missing.key', 'fallback');

    $this->assertEquals('fallback', $result);
  }

  /**
   * Tests isMultiTenant with active domain.
   *
   * @covers ::isMultiTenant
   */
  public function testIsMultiTenantWithActiveDomain(): void {
    $domain = $this->createMockDomain('kobenhavn', 'København Kommune');

    $this->domainNegotiator->expects($this->once())
      ->method('getActiveDomain')
      ->willReturn($domain);

    $result = $this->tenantResolver->isMultiTenant();

    $this->assertTrue($result);
  }

  /**
   * Tests isMultiTenant without active domain.
   *
   * @covers ::isMultiTenant
   */
  public function testIsMultiTenantWithoutActiveDomain(): void {
    $this->domainNegotiator->expects($this->once())
      ->method('getActiveDomain')
      ->willReturn(NULL);

    $result = $this->tenantResolver->isMultiTenant();

    $this->assertFalse($result);
  }

  /**
   * Tests getTenantName with active domain.
   *
   * @covers ::getTenantName
   */
  public function testGetTenantNameWithActiveDomain(): void {
    $domain = $this->createMockDomain('esbjerg', 'Esbjerg Kommune');

    $this->domainNegotiator->expects($this->once())
      ->method('getActiveDomain')
      ->willReturn($domain);

    $result = $this->tenantResolver->getTenantName();

    $this->assertEquals('Esbjerg Kommune', $result);
  }

  /**
   * Tests getTenantName without active domain.
   *
   * @covers ::getTenantName
   */
  public function testGetTenantNameWithoutActiveDomain(): void {
    $this->domainNegotiator->expects($this->once())
      ->method('getActiveDomain')
      ->willReturn(NULL);

    $result = $this->tenantResolver->getTenantName();

    $this->assertEquals('Default', $result);
  }

  /**
   * Helper to create a mock domain.
   *
   * @param string $id
   *   Domain ID.
   * @param string $label
   *   Domain label.
   *
   * @return \Drupal\domain\DomainInterface|\PHPUnit\Framework\MockObject\MockObject
   *   Mock domain object.
   */
  protected function createMockDomain(string $id, string $label) {
    $domain = $this->createMock(DomainInterface::class);
    $domain->method('id')->willReturn($id);
    $domain->method('label')->willReturn($label);
    return $domain;
  }

}
