<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Controller;

use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Renders the anonymous brand landing at /aabenforms.
 *
 * Also serves the front page since aabenforms_core_install() points
 * system.site.page.front at this route. Tiny page: wordmark, one-line
 * description, three links (citizen frontend, sign-in, GitHub). No
 * analytics, no marketing copy. The point is to make the API hostname
 * "obvious" rather than empty.
 */
class LandingController extends ControllerBase {

  /**
   * Returns the brand landing render array, or redirects admins to the dashboard.
   *
   * @return array|\Drupal\Core\Cache\CacheableRedirectResponse
   *   Render array for anonymous visitors; redirect response for users
   *   that have the access aabenforms admin permission.
   */
  public function page(): array|CacheableRedirectResponse {
    // Authenticated users with the dashboard permission already know
    // what AabenForms is - skip the brand landing and send them to the
    // dashboard directly. The cache context ensures anon and admin see
    // distinct cached responses for the same URL.
    if ($this->currentUser()->hasPermission('access aabenforms admin')) {
      $response = new CacheableRedirectResponse(
        Url::fromRoute('aabenforms_core.dashboard')->toString()
      );
      $response->getCacheableMetadata()->addCacheContexts(['user.permissions']);
      return $response;
    }

    return [
      '#theme' => 'aabenforms_dashboard_landing',
      '#title' => $this->t('AabenForms'),
      '#tagline' => $this->t('Open-source workflow automation for Danish municipalities.'),
      '#frontend_url' => 'https://aabenforms.dk',
      '#login_url' => Url::fromRoute('user.login')->toString(),
      '#docs_url' => 'https://github.com/madsnorgaard/aabenforms',
      '#attached' => [
        'library' => [
          'aabenforms_core/admin',
          'aabenforms_core/dashboard',
        ],
      ],
      '#cache' => [
        'contexts' => ['user.permissions'],
        'max-age' => 3600,
      ],
    ];
  }

}
