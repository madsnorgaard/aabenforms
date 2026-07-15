<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Service;

use Drupal\aabenforms_esdh\EsdhConnectorInterface;
use Drupal\aabenforms_esdh\EsdhConnectorManager;
use Drupal\aabenforms_esdh\Exception\EsdhException;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Resolves the ESDH connector selected in configuration.
 *
 * Defaults to the demo connector (which has no external effect - it only
 * synthesises a local reference), so an unset config is safe. An unknown
 * connector id fails hard rather than silently degrading.
 */
final class EsdhConnectorResolver {

  public function __construct(
    private readonly EsdhConnectorManager $manager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns the active connector.
   *
   * @throws \Drupal\aabenforms_esdh\Exception\EsdhException
   *   When the configured connector id is not a known plugin.
   */
  public function resolve(): EsdhConnectorInterface {
    $id = (string) $this->configFactory
      ->get('aabenforms_esdh.settings')
      ->get('active_connector');
    if ($id === '') {
      $id = 'demo';
    }
    if (!$this->manager->hasDefinition($id)) {
      throw new EsdhException(sprintf('Unknown ESDH connector "%s".', $id));
    }
    /** @var \Drupal\aabenforms_esdh\EsdhConnectorInterface $connector */
    $connector = $this->manager->createInstance($id);
    return $connector;
  }

}
