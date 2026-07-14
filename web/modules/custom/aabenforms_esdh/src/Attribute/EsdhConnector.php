<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Defines an ESDH connector plugin.
 *
 * @see \Drupal\aabenforms_esdh\EsdhConnectorInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class EsdhConnector extends Plugin {

  /**
   * @param string $id
   *   The connector machine id (matches the settings "active_connector").
   * @param string $label
   *   The human-readable label.
   * @param bool $demo
   *   TRUE for the demo connector (runs without configuration).
   */
  public function __construct(
    public readonly string $id,
    public readonly string|\Stringable $label = '',
    public readonly bool $demo = FALSE,
  ) {}

}
