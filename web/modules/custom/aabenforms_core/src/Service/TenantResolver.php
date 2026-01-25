<?php

namespace Drupal\aabenforms_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Multi-tenant domain detection and configuration service.
 *
 * Integrates with Domain module to provide tenant-specific configuration
 * for Danish government integrations (MitID credentials, Serviceplatformen
 * certificates, etc.).
 *
 * Example usage:
 * @code
 * $tenant = $this->tenantResolver->getCurrentTenant();
 * $mitid_client_id = $tenant->getConfig('mitid.client_id');
 * @endcode
 *
 * @see https://www.drupal.org/project/domain
 */
class TenantResolver {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected DomainNegotiatorInterface $domainNegotiator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a TenantResolver.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    DomainNegotiatorInterface $domain_negotiator,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->domainNegotiator = $domain_negotiator;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('aabenforms_core');
  }

  /**
   * Gets the current tenant (active domain).
   *
   * @return \Drupal\domain\DomainInterface|null
   *   The current domain entity, or NULL if not in multi-tenant context.
   */
  public function getCurrentTenant() {
    return $this->domainNegotiator->getActiveDomain();
  }

  /**
   * Gets the current tenant ID.
   *
   * @return string|null
   *   The domain ID (e.g., 'aarhus', 'odense'), or NULL if not multi-tenant.
   */
  public function getCurrentTenantId(): ?string {
    $domain = $this->getCurrentTenant();
    return $domain ? $domain->id() : NULL;
  }

  /**
   * Gets tenant-specific configuration value.
   *
   * @param string $key
   *   The configuration key (e.g., 'mitid.client_id', 'serviceplatformen.cert_path').
   * @param mixed $default
   *   Default value if not set.
   *
   * @return mixed
   *   The configuration value.
   */
  public function getTenantConfig(string $key, $default = NULL) {
    $tenant_id = $this->getCurrentTenantId();

    if (!$tenant_id) {
      // Not in multi-tenant context - use global config.
      $config = $this->configFactory->get('aabenforms_core.settings');
      return $config->get($key) ?? $default;
    }

    // Get tenant-specific config.
    $config = $this->configFactory->get("aabenforms_core.tenant.{$tenant_id}");
    $value = $config->get($key);

    if ($value === NULL) {
      // Fall back to global config.
      $global_config = $this->configFactory->get('aabenforms_core.settings');
      $value = $global_config->get($key) ?? $default;
    }

    return $value;
  }

  /**
   * Checks if the current context is multi-tenant.
   *
   * @return bool
   *   TRUE if multi-tenant (domain module active), FALSE otherwise.
   */
  public function isMultiTenant(): bool {
    return $this->getCurrentTenant() !== NULL;
  }

  /**
   * Gets tenant display name.
   *
   * @return string
   *   The tenant name (e.g., 'Aarhus Kommune'), or 'Default' if not multi-tenant.
   */
  public function getTenantName(): string {
    $domain = $this->getCurrentTenant();
    return $domain ? $domain->label() : 'Default';
  }

}
