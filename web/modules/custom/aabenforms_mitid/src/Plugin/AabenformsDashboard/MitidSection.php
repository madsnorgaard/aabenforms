<?php

declare(strict_types=1);

namespace Drupal\aabenforms_mitid\Plugin\AabenformsDashboard;

use Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionBase;
use Drupal\aabenforms_core\Dashboard\Attribute\AabenformsDashboardSection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MitID dashboard card.
 *
 * Note on lifecycle: aabenforms_mitid is on a deprecation track per the
 * Digital Post / NemLogin rewrite plan. When aabenforms_nemlogin ships
 * (Session 2C), this plugin moves to aabenforms_nemlogin and the
 * MitID-specific subclass (preset scopes/claims) stays. Until then, the
 * card renders here.
 */
#[AabenformsDashboardSection(id: 'mitid', weight: -30)]
class MitidSection extends AabenformsDashboardSectionBase {

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
    return $this->t('MitID');
  }

  /**
   * Tone rules:
   * - production=true + non-mock authorization_endpoint → SUCCESS Live
   * - production=false but real endpoint configured → BRAND Live test
   * - localhost/keycloak endpoint → NEUTRAL Mock
   * - production=true with mock endpoint → DANGER (misconfigured prod)
   */
  public function getStatusBadge(): ?array {
    $settings = $this->configFactory->get('aabenforms_mitid.settings');
    $production = (bool) $settings->get('production');
    $endpoint = (string) $settings->get('authorization_endpoint');
    $isMock = $endpoint !== '' && (
      str_contains($endpoint, 'localhost')
      || str_contains($endpoint, 'keycloak')
      || str_contains($endpoint, '.ddev.site')
    );

    return match (TRUE) {
      $production && $isMock => ['label' => $this->t('Mock in prod'), 'tone' => 'danger'],
      $production => ['label' => $this->t('Live'), 'tone' => 'success'],
      !$production && !$isMock && $endpoint !== '' => ['label' => $this->t('Live test'), 'tone' => 'brand'],
      default => ['label' => $this->t('Mock'), 'tone' => 'neutral'],
    };
  }

  public function getSecondaryMetrics(): array {
    $endpoint = (string) $this->configFactory->get('aabenforms_mitid.settings')->get('authorization_endpoint');
    $host = parse_url($endpoint, PHP_URL_HOST) ?: $this->t('Not set');

    $since = $this->time->getRequestTime() - 86400;
    try {
      $logins24h = (int) $this->database->select('aabenforms_audit_log', 'l')
        ->condition('action', 'mitid_login')
        ->condition('timestamp', $since, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Throwable) {
      $logins24h = 0;
    }

    return [
      ['label' => $this->t('Provider'), 'value' => (string) $host],
      ['label' => $this->t('Logins (24h)'), 'value' => $logins24h],
    ];
  }

  public function getMainLink(): array {
    // aabenforms_mitid currently has no settings UI route; link to the
    // main admin config page so the card has a useful destination. When
    // Session 2C ships aabenforms_nemlogin the link target updates to
    // the new provider entity collection.
    return [
      'label' => $this->t('Open Drupal admin'),
      'url' => Url::fromUri('internal:/admin/config'),
    ];
  }

  public function getCacheTags(): array {
    return [
      'config:aabenforms_mitid.settings',
      'aabenforms_dashboard:activity',
    ];
  }

}
