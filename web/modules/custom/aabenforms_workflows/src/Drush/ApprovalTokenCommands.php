<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Drush;

use Drupal\aabenforms_workflows\Service\ApprovalTokenService;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for parent-approval token testing.
 *
 * Provides `af:approval-token:mint` which prints a freshly-signed token
 * to stdout. Production-guarded: refuses to run if the DRUPAL_ENV
 * environment variable is "production". The Playwright runner uses this
 * to mint a valid token for the parent-approval E2E happy-path spec
 * without embedding the site's PRIVATE_KEY in test code.
 */
final class ApprovalTokenCommands extends DrushCommands {

  /**
   * The environment value that disables minting.
   */
  private const PRODUCTION_ENV = 'production';

  public function __construct(
    private readonly ApprovalTokenService $tokenService,
  ) {
    parent::__construct();
  }

  /**
   * Mints a parent-approval token and prints it to stdout.
   *
   * @command aabenforms:approval-token:mint
   * @aliases af:approval-token:mint
   * @option sid The submission ID to bind the token to. Required.
   * @option parent The parent number (1 or 2). Required.
   * @usage drush af:approval-token:mint --sid=42 --parent=1
   *   Print a fresh signed token bound to submission 42 / parent 1.
   *
   * @bootstrap full
   */
  public function mint(
    array $options = [
      'sid' => NULL,
      'parent' => NULL,
    ],
  ): int {
    if (getenv('DRUPAL_ENV') === self::PRODUCTION_ENV) {
      $this->logger()->error('Approval-token minting is disabled when DRUPAL_ENV=production.');
      return self::EXIT_FAILURE;
    }

    $sid = $options['sid'];
    $parent = $options['parent'];
    if (!is_numeric($sid) || !is_numeric($parent)) {
      $this->logger()->error('--sid and --parent are both required and must be numeric.');
      return self::EXIT_FAILURE;
    }
    $parent = (int) $parent;
    if ($parent !== 1 && $parent !== 2) {
      $this->logger()->error('--parent must be 1 or 2.');
      return self::EXIT_FAILURE;
    }

    $token = $this->tokenService->generateToken((int) $sid, $parent);
    // The output: ONLY the token on stdout, no decoration. Lets test
    // runners consume it via $(drush af:approval-token:mint ...).
    $this->output()->write($token);
    return self::EXIT_SUCCESS;
  }

}
