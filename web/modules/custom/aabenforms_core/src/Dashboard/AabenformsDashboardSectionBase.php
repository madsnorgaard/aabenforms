<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Dashboard;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sane defaults for a dashboard section. Most plugins override only the
 * methods they need.
 */
abstract class AabenformsDashboardSectionBase extends PluginBase implements AabenformsDashboardSectionInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('@id', ['@id' => $this->getPluginId()]);
  }

  public function getWeight(): int {
    return $this->pluginDefinition['weight'] ?? 0;
  }

  public function isApplicable(): bool {
    return TRUE;
  }

  public function getStatusBadge(): ?array {
    return NULL;
  }

  public function getHeroMetric(): ?array {
    return NULL;
  }

  public function getSecondaryMetrics(): array {
    return [];
  }

  public function getCacheTags(): array {
    return [];
  }

  public function getCacheContexts(): array {
    return ['user.permissions'];
  }

  public function getCacheMaxAge(): int {
    return 60;
  }

}
