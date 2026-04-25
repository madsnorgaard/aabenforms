<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Renders the anonymous brand landing at /aabenforms (and at /, since
 * aabenforms_core_install() points system.site.page.front here).
 *
 * Tiny page: wordmark, one-line description, three links (citizen
 * frontend, sign-in, GitHub). No analytics, no marketing copy. The point
 * is to make the API hostname "obvious" rather than empty.
 */
class LandingController extends ControllerBase {

  public function page(): array {
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
        'contexts' => ['user.roles:anonymous'],
        'max-age' => 3600,
      ],
    ];
  }

}
