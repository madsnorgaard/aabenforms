<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Plugin\AabenformsDashboard;

use Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionBase;
use Drupal\aabenforms_core\Dashboard\Attribute\AabenformsDashboardSection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mock services card. DDEV-only by design.
 *
 * Hides itself in production: if Digital Post test_mode is `live` AND
 * we're not in DDEV (no DDEV_HOSTNAME env), the card doesn't render.
 * Implementors deploying to prod don't need this noise.
 */
#[AabenformsDashboardSection(id: 'mock_services', weight: 10)]
class MockServicesSection extends AabenformsDashboardSectionBase {

  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ClientInterface $httpClient,
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
      $container->get('config.factory'),
      $container->get('http_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Mock Services');
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(): bool {
    // The mock services live in DDEV - their endpoints are localhost
    // ports unreachable from prod containers. Pinging them from prod
    // always reports "Down", which is noise rather than signal. Render
    // the card only when DDEV is detected via its env vars.
    return (bool) (getenv('DDEV_HOSTNAME') ?: getenv('DDEV_PROJECT'));
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusBadge(): ?array {
    $endpoints = [
      'Keycloak' => 'http://keycloak:8080/realms/master',
      'WireMock' => 'http://wiremock:8080/__admin/health',
    ];
    $up = 0;
    foreach ($endpoints as $url) {
      try {
        $this->httpClient->request('GET', $url, ['timeout' => 1, 'connect_timeout' => 1]);
        $up++;
      }
      catch (\Throwable) {
        // Service unreachable.
      }
    }
    return match ($up) {
      0 => ['label' => $this->t('Down'), 'tone' => 'danger'],
      1 => ['label' => $this->t('Degraded'), 'tone' => 'warning'],
      default => ['label' => $this->t('Running'), 'tone' => 'success'],
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondaryMetrics(): array {
    return [
      ['label' => $this->t('Keycloak'), 'value' => $this->t('localhost:8080')],
      ['label' => $this->t('WireMock'), 'value' => $this->t('localhost:8081')],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMainLink(): array {
    return [
      'label' => $this->t('Open Keycloak admin'),
      'url' => Url::fromUri('http://localhost:8080/admin'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    // Health checks should refresh more often than other cards.
    return 30;
  }

}
