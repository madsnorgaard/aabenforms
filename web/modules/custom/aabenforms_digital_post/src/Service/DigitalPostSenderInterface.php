<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Service;

use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Result;

/**
 * Public seam for the Digital Post send pipeline.
 *
 * Extracted so callers (action plugins, drush commands, future REST
 * adapters) can depend on the interface rather than the final
 * DigitalPostSender class. This makes the seam mockable in unit tests
 * and lets sites decorate the service in their own services.yml without
 * subclassing.
 *
 * @api
 */
interface DigitalPostSenderInterface {

  /**
   * Sends a Digital Post message via the configured transport.
   *
   * @param \Drupal\aabenforms_digital_post\DigitalPost\DigitalPost $post
   *   The Digital Post DTO to dispatch.
   *
   * @return \Drupal\aabenforms_digital_post\DigitalPost\Result
   *   Typed result; either success with a transaction id or failure with
   *   a typed reason code.
   */
  public function send(DigitalPost $post): Result;

  /**
   * Returns the active transport mode label.
   *
   * Used by callers (and the dashboard) to surface "fake_db" / "wiremock"
   * / "live" without reaching into config directly.
   *
   * @return string
   *   The mode label. One of: fake_db, wiremock, live.
   */
  public function testMode(): string;

}
