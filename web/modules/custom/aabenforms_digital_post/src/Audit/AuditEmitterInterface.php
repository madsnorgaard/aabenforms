<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Audit;

/**
 * Emits audit events about Digital Post sends.
 *
 * Implementations:
 * - CoreAuditEmitter (default) wraps aabenforms_core.audit_logger.
 * - NullAuditEmitter for installs without aabenforms_core.
 * - Sites can decorate this service to route to os2web_audit or similar.
 */
interface AuditEmitterInterface {

  public function emit(string $eventType, string $identifier, string $message, string $status, array $context = []): void;

}
