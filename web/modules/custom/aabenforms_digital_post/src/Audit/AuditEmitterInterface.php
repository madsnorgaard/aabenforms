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

  /**
   * Emits a single audit-log event.
   *
   * @param string $eventType
   *   Stable event slug, e.g. "digital_post_sent".
   * @param string $identifier
   *   Identifier value associated with the event (CPR, CVR, transaction id).
   * @param string $message
   *   Human-readable summary stored against the event.
   * @param string $status
   *   The string "success" or "failure".
   * @param array $context
   *   Optional structured context, JSON-encoded by the underlying logger.
   */
  public function emit(string $eventType, string $identifier, string $message, string $status, array $context = []): void;

}
