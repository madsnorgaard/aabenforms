<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Audit;

/**
 * No-op audit emitter for installs without aabenforms_core.
 *
 * Wired by the services.yml factory when aabenforms_core's audit logger
 * service isn't available, so the Digital Post send path keeps working
 * without an audit destination.
 */
final class NullAuditEmitter implements AuditEmitterInterface {

  /**
   * {@inheritdoc}
   */
  public function emit(string $eventType, string $identifier, string $message, string $status, array $context = []): void {
    // Intentional no-op.
  }

}
