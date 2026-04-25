<?php

declare(strict_types=1);

namespace Drupal\aabenforms_webform\Plugin\AabenformsDashboard;

use Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionBase;
use Drupal\aabenforms_core\Dashboard\Attribute\AabenformsDashboardSection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AabenformsDashboardSection(id: 'webforms', weight: -20)]
class WebformsSection extends AabenformsDashboardSectionBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
    );
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Webforms');
  }

  public function getHeroMetric(): ?array {
    try {
      $count = $this->entityTypeManager->getStorage('webform')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      $count = 0;
    }
    return [
      'value' => (int) $count,
      'label' => $this->t('webforms'),
    ];
  }

  public function getSecondaryMetrics(): array {
    $since = $this->time->getRequestTime() - (7 * 86400);
    try {
      $submissions = $this->entityTypeManager->getStorage('webform_submission')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $since, '>=')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      $submissions = 0;
    }
    return [
      ['label' => $this->t('Submissions (7d)'), 'value' => (int) $submissions],
    ];
  }

  public function getMainLink(): array {
    return [
      'label' => $this->t('Browse webforms'),
      'url' => Url::fromUri('internal:/admin/structure/webform'),
    ];
  }

  public function getCacheTags(): array {
    return ['webform_list', 'webform_submission_list'];
  }

}
