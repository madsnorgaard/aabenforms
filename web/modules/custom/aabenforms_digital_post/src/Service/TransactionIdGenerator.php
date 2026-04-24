<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Service;

use Symfony\Component\Uid\Uuid;

/**
 * Produces UUIDs for SF1601 transactionId fields.
 *
 * Drupal ships symfony/uid transitively. Uuid::v7() gives us
 * time-ordered UUIDs, which are easier to page through in the log table
 * than fully random v4s.
 */
final class TransactionIdGenerator {

  public function generate(): string {
    return Uuid::v7()->toRfc4122();
  }

}
