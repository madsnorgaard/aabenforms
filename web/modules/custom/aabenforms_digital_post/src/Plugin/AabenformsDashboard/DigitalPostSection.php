<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Plugin\AabenformsDashboard;

use Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionBase;
use Drupal\aabenforms_core\Dashboard\Attribute\AabenformsDashboardSection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AabenformsDashboardSection(id: 'digital_post', weight: -40)]
class DigitalPostSection extends AabenformsDashboardSectionBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected readonly ConfigFactoryInterface $configFactory,
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
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('datetime.time'),
    );
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Digital Post');
  }

  /**
   * Tone rules from the UX brief:
   * - mock state (fake_db, wiremock) is NEUTRAL
   * - live_test is BRAND
   * - live with sender_cvr set is SUCCESS
   * - live without sender_cvr is DANGER (misconfigured prod)
   */
  public function getStatusBadge(): ?array {
    $settings = $this->configFactory->get('aabenforms_digital_post.settings');
    $mode = (string) $settings->get('test_mode') ?: 'fake_db';
    $cvr = (string) $settings->get('sender_cvr');

    return match (TRUE) {
      $mode === 'live' && $cvr !== '' => ['label' => $this->t('Live'), 'tone' => 'success'],
      $mode === 'live' => ['label' => $this->t('Live · CVR missing'), 'tone' => 'danger'],
      $mode === 'live_test' => ['label' => $this->t('Live test'), 'tone' => 'brand'],
      default => ['label' => $this->modeLabel($mode), 'tone' => 'neutral'],
    };
  }

  public function getSecondaryMetrics(): array {
    $cvr = (string) $this->configFactory->get('aabenforms_digital_post.settings')->get('sender_cvr');

    $since = $this->time->getRequestTime() - 86400;
    try {
      $sent24h = (int) $this->database->select('aabenforms_digital_post_log', 'l')
        ->condition('created', $since, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Throwable) {
      $sent24h = 0;
    }

    return [
      ['label' => $this->t('Sender CVR'), 'value' => $cvr !== '' ? $cvr : $this->t('Not set')],
      ['label' => $this->t('Sent (24h)'), 'value' => $sent24h],
    ];
  }

  public function getMainLink(): array {
    return [
      'label' => $this->t('Configure'),
      'url' => Url::fromRoute('aabenforms_digital_post.settings'),
    ];
  }

  public function getCacheTags(): array {
    return [
      'config:aabenforms_digital_post.settings',
      'aabenforms_dashboard:activity',
    ];
  }

  protected function modeLabel(string $mode): TranslatableMarkup {
    return match ($mode) {
      'wiremock' => $this->t('WireMock'),
      'fake_db' => $this->t('Fake DB'),
      default => $this->t('Mode: @m', ['@m' => $mode]),
    };
  }

}
