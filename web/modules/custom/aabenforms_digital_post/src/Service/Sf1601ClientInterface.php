<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Service;

use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Result;

/**
 * Transport contract for sending a DigitalPost.
 *
 * Concrete implementations decide how the bytes travel:
 *
 * - FakeSendDatabaseLogger: writes to {aabenforms_digital_post_log}
 *   and returns a fake receipt. Zero external dependencies.
 *
 * - WireMockSoapClient: POSTs a JSON-bodied request to a WireMock
 *   endpoint. Used for CI and DDEV dev-loop integration tests.
 *
 * - Session-2+ real client: wraps itk-dev/serviceplatformen's SF1601
 *   class, constructs a MeMo Message from the DigitalPost DTO, calls
 *   kombiPostAfsend().
 */
interface Sf1601ClientInterface {

  /**
   * Sends a Digital Post.
   *
   * Returns a Result carrying the transaction id, status, and (on
   * failure) a typed reason code. Never throws - all failure modes are
   * returned as Result::failure().
   */
  public function send(DigitalPost $post, string $transactionId): Result;

  /**
   * Label used in audit logs and the log table's `mode` column.
   */
  public function modeLabel(): string;

}
