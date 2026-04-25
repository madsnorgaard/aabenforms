<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Plugin\AabenformsDashboard;

use Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionBase;
use Drupal\aabenforms_core\Dashboard\Attribute\AabenformsDashboardSection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AabenformsDashboardSection(id: 'audit_log', weight: -10)]
class AuditLogSection extends AabenformsDashboardSectionBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected readonly Connection $database,
    protected readonly TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('datetime.time'),
    );
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Audit Log');
  }

  public function getHeroMetric(): ?array {
    $since = $this->time->getRequestTime() - 86400;
    try {
      $count = (int) $this->database->select('aabenforms_audit_log', 'l')
        ->condition('timestamp', $since, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Throwable) {
      $count = 0;
    }
    return [
      'value' => $count,
      'label' => $this->t('events (24h)'),
    ];
  }

  public function getSecondaryMetrics(): array {
    $since = $this->time->getRequestTime() - 86400;
    try {
      $errors = (int) $this->database->select('aabenforms_audit_log', 'l')
        ->condition('timestamp', $since, '>=')
        ->condition('status', 'failure')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Throwable) {
      $errors = 0;
    }
    return [
      ['label' => $this->t('Failures (24h)'), 'value' => $errors],
    ];
  }

  public function getMainLink(): array {
    return [
      'label' => $this->t('View activity'),
      'url' => Url::fromRoute('aabenforms_core.dashboard', [], ['fragment' => 'activity']),
    ];
  }

  public function getCacheTags(): array {
    return ['aabenforms_dashboard:activity'];
  }

}
