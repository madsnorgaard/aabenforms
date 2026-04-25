<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Plugin\AabenformsDashboard;

use Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionBase;
use Drupal\aabenforms_core\Dashboard\Attribute\AabenformsDashboardSection;
use Drupal\aabenforms_workflows\Service\ElectionService;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Elections card on the AabenForms dashboard.
 *
 * Hidden when no elections exist - the card is irrelevant on sites
 * that don't run MED nominations. Shows status of the most recent
 * election plus total count.
 */
#[AabenformsDashboardSection(id: 'elections', weight: -5)]
class ElectionsSection extends AabenformsDashboardSectionBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected readonly ElectionService $election,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('aabenforms_workflows.election'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Elections');
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(): bool {
    return $this->election->list(1) !== [];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusBadge(): ?array {
    $rows = $this->election->list(1);
    if (!$rows) {
      return NULL;
    }
    $status = $rows[0]['status'] ?? 'pending';
    return match ($status) {
      ElectionService::STATUS_OPEN => ['label' => $this->t('Voting open'), 'tone' => 'brand'],
      ElectionService::STATUS_CLOSED => ['label' => $this->t('Awaiting publish'), 'tone' => 'warning'],
      ElectionService::STATUS_PUBLISHED => ['label' => $this->t('Published'), 'tone' => 'success'],
      default => ['label' => $this->t('Pending'), 'tone' => 'neutral'],
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondaryMetrics(): array {
    $rows = $this->election->list(1);
    $latest = $rows[0] ?? [];
    return [
      ['label' => $this->t('Latest'), 'value' => (string) ($latest['label'] ?? '—')],
      ['label' => $this->t('Total elections'), 'value' => count($this->election->list(50))],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMainLink(): array {
    return [
      'label' => $this->t('Browse elections'),
      'url' => Url::fromRoute('aabenforms_workflows.elections'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return ['aabenforms_election_list'];
  }

}
