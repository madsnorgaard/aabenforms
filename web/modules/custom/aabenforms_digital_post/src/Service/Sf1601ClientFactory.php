<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Service;

use Drupal\aabenforms_digital_post\Exception\DigitalPostException;
use Drupal\aabenforms_digital_post\TestMode\FakeSendDatabaseLogger;
use Drupal\aabenforms_digital_post\TestMode\WireMockSoapClient;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Picks the Sf1601 transport implementation based on config.test_mode.
 *
 * Session 1 supports fake_db and wiremock. Session 2 adds live_test and
 * live (both via itk-dev/serviceplatformen).
 */
final class Sf1601ClientFactory {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly FakeSendDatabaseLogger $fakeDbClient,
    private readonly WireMockSoapClient $wireMockClient,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function create(): Sf1601ClientInterface {
    $mode = (string) $this->configFactory
      ->get('aabenforms_digital_post.settings')
      ->get('test_mode');
    return match ($mode) {
      // Fail closed on empty/unset: a wiped or missing config must NEVER
      // silently fall back to the fake transport (that would turn undelivered
      // official letters into fake successes). An explicit mode is required.
      '' => throw new DigitalPostException('Digital Post test_mode is not configured; refusing to send. Set it explicitly (fake_db in dev).'),
      'fake_db' => $this->fakeDbClient,
      'wiremock' => $this->wireMockClient,
      'live_test', 'live' => throw new DigitalPostException(sprintf(
        'test_mode "%s" is not supported in session 1. Use fake_db or wiremock.',
        $mode
      )),
      default => throw new DigitalPostException(sprintf('Unknown test_mode "%s".', $mode)),
    };
  }

}
