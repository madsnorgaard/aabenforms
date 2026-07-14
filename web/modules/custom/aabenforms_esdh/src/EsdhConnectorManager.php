<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh;

use Drupal\aabenforms_esdh\Attribute\EsdhConnector;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for ESDH connectors.
 *
 * Connectors live in each module's Plugin/AabenformsEsdh namespace and are
 * declared with the #[EsdhConnector] attribute.
 */
final class EsdhConnectorManager extends DefaultPluginManager {

  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/AabenformsEsdh',
      $namespaces,
      $module_handler,
      EsdhConnectorInterface::class,
      EsdhConnector::class,
    );
    $this->alterInfo('aabenforms_esdh_connector_info');
    $this->setCacheBackend($cache_backend, 'aabenforms_esdh_connector_plugins');
  }

}
