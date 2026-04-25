<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Audit;

/**
 * No-op audit emitter. Used when aabenforms_core is not installed.
 */
final class NullAuditEmitter implements AuditEmitterInterface {

  /**
   * {@inheritdoc}
   */
  public function emit(string $eventType, string $identifier, string $message, string $status, array $context = []): void {
    // Intentional no-op. Wired by the services.yml factory when aabenforms_core
    // isn't installed.
  }

}
