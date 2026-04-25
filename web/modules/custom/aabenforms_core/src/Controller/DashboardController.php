<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Controller;

use Drupal\aabenforms_core\Dashboard\AabenformsDashboardSectionManager;
use Drupal\aabenforms_core\Dashboard\RecentActivityBuilder;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Renders the AabenForms admin dashboard at /admin/aabenforms.
 *
 * Phase A: skeleton render array with header + section grid placeholders.
 * Phase B+: composes feature-module section plugins into cards.
 */
class DashboardController extends ControllerBase {

  public function __construct(
    protected readonly AabenformsDashboardSectionManager $sectionManager,
    protected readonly RecentActivityBuilder $activityBuilder,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.aabenforms_dashboard_section'),
      $container->get('aabenforms_core.dashboard_activity'),
    );
  }

  public function overview(Request $request): array {
    $sections = $this->sectionManager->getApplicableSections();

    $cards = [];
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user.permissions']);
    $cache->addCacheTags(['aabenforms_dashboard:overview']);
    $cache->mergeCacheMaxAge(60);

    foreach ($sections as $id => $section) {
      $cards[$id] = $this->buildSectionCard($id, $section);
      try {
        $cache->merge(CacheableMetadata::createFromObject($section));
      }
      catch (\Throwable $e) {
        $this->logSectionFailure($id, 'cache_metadata', $e);
      }
    }

    $filter = (string) $request->query->get('filter', RecentActivityBuilder::DEFAULT_FILTER);
    try {
      $activity = $this->activityBuilder->build($filter);
    }
    catch (\Throwable $e) {
      \Drupal::logger('aabenforms_core')->error('Activity feed failed: @msg', ['@msg' => $e->getMessage()]);
      $activity = ['rows' => [], 'pivoted' => FALSE, 'filter' => $filter];
    }
    $cache->addCacheTags(['aabenforms_dashboard:activity']);
    $cache->addCacheContexts(['url.query_args:filter']);

    $build = [
      '#theme' => 'aabenforms_dashboard',
      '#header' => [
        'title' => $this->t('AabenForms'),
        'environment' => $this->resolveEnvironment(),
        'tagline' => $this->t('Workflow automation backend for Danish municipalities.'),
      ],
      '#quick_actions' => $this->buildQuickActions(),
      '#sections' => $cards,
      '#activity' => [
        '#theme' => 'aabenforms_dashboard_activity',
        '#filter' => $activity['filter'],
        '#filters' => $this->buildActivityFilters($activity['filter']),
        '#rows' => $activity['rows'],
        '#pivoted' => $activity['pivoted'],
        '#empty_message' => $this->t('No events yet.'),
      ],
      '#attached' => [
        'library' => [
          'aabenforms_core/admin',
          'aabenforms_core/dashboard',
        ],
      ],
    ];

    $cache->applyTo($build);
    return $build;
  }

  /**
   * Compose one section card with each accessor wrapped in try/catch.
   *
   * Any single accessor blowing up (e.g. a DB query against a column
   * that briefly disappears during cim, a config key shape change, a
   * stale plugin cache between cim and cr) drops to a safe default for
   * that field instead of bubbling the exception and 500'ing the whole
   * dashboard.
   */
  protected function buildSectionCard(string $id, $section): array {
    $card = [
      '#theme' => 'aabenforms_dashboard_section_card',
      '#section_id' => $id,
      '#label' => $this->safeAccessor($id, 'getLabel', fn () => $section->getLabel(), $this->t('@id', ['@id' => $id])),
      '#status_badge' => $this->safeAccessor($id, 'getStatusBadge', fn () => $section->getStatusBadge()),
      '#hero_metric' => $this->safeAccessor($id, 'getHeroMetric', fn () => $section->getHeroMetric()),
      '#secondary_metrics' => $this->safeAccessor($id, 'getSecondaryMetrics', fn () => $section->getSecondaryMetrics(), []),
      '#main_link' => $this->safeAccessor($id, 'getMainLink', fn () => $section->getMainLink(), []),
    ];

    // If everything bombed (no badge, no metric, no link) tag the card
    // visually as unavailable so the user sees something rather than an
    // empty hole.
    $allEmpty = $card['#status_badge'] === NULL
      && $card['#hero_metric'] === NULL
      && empty($card['#secondary_metrics'])
      && empty($card['#main_link']);
    if ($allEmpty) {
      $card['#status_badge'] = ['label' => $this->t('Unavailable'), 'tone' => 'warning'];
    }
    return $card;
  }

  /**
   * Run a section-data accessor; return a safe fallback on any throw.
   */
  protected function safeAccessor(string $id, string $method, callable $fn, $fallback = NULL) {
    try {
      return $fn();
    }
    catch (\Throwable $e) {
      $this->logSectionFailure($id, $method, $e);
      return $fallback;
    }
  }

  protected function logSectionFailure(string $id, string $method, \Throwable $e): void {
    \Drupal::logger('aabenforms_core')->error(
      'Dashboard section @id::@method failed: @msg',
      ['@id' => $id, '@method' => $method, '@msg' => $e->getMessage()],
    );
  }

  /**
   * Build the filter chips. Each chip is an anchor that re-issues the
   * dashboard request with a different ?filter=. No JS.
   */
  protected function buildActivityFilters(string $active): array {
    $labels = [
      'all' => $this->t('All'),
      'digital_post' => $this->t('Digital Post'),
      'audit' => $this->t('Audit'),
      'errors' => $this->t('Errors'),
    ];
    $chips = [];
    foreach ($labels as $key => $label) {
      $chips[] = [
        'key' => $key,
        'label' => $label,
        'active' => $key === $active,
        'url' => Url::fromRoute('aabenforms_core.dashboard', ['filter' => $key], ['fragment' => 'activity'])->toString(),
      ];
    }
    return $chips;
  }

  /**
   * @return array{label:string,tone:string}
   */
  protected function resolveEnvironment(): array {
    $env = getenv('DRUPAL_ENV') ?: ($_SERVER['DRUPAL_ENV'] ?? '');
    if ($env === 'prod' || $env === 'production') {
      return ['label' => 'PROD', 'tone' => 'neutral'];
    }
    if ($env === 'staging') {
      return ['label' => 'STAGING', 'tone' => 'warning'];
    }
    return ['label' => 'POC', 'tone' => 'warning'];
  }

  /**
   * Quick-action buttons. Routes referenced here are guaranteed by the
   * modules we hard-depend on (workflows, digital_post via _eca, webform).
   * Routes that don't exist are skipped silently to keep the dashboard
   * resilient to module disablement.
   */
  protected function buildQuickActions(): array {
    $candidates = [
      [
        'label' => $this->t('New workflow'),
        'route' => 'aabenforms_workflows.template_browser',
        'primary' => TRUE,
      ],
      [
        'label' => $this->t('Send test Digital Post'),
        'route' => 'aabenforms_digital_post.settings',
      ],
      [
        'label' => $this->t('Open ECA modeller'),
        'route' => 'entity.eca.collection',
      ],
      [
        'label' => $this->t('View submissions'),
        'route' => 'entity.webform_submission.collection',
      ],
    ];

    $provider = \Drupal::service('router.route_provider');
    $actions = [];
    foreach ($candidates as $candidate) {
      try {
        $provider->getRouteByName($candidate['route']);
        $actions[] = [
          'label' => $candidate['label'],
          'url' => Url::fromRoute($candidate['route']),
          'primary' => $candidate['primary'] ?? FALSE,
        ];
      }
      catch (\Symfony\Component\Routing\Exception\RouteNotFoundException) {
        // Skip — module providing the route isn't enabled.
      }
    }
    return $actions;
  }

}
