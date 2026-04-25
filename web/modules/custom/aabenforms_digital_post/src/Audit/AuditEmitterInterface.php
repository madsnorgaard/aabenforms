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
   * Emits an audit event about a Digital Post send attempt.
   *
   * @param string $eventType
   *   The type of event (e.g. 'digital_post_sent', 'digital_post_failed').
   * @param string $identifier
   *   A stable, non-reversible hash of the recipient identifier.
   * @param string $message
   *   Human-readable description of the event.
   * @param string $status
   *   Either 'success' or 'failure'.
   * @param array $context
   *   Additional key/value context for the log entry.
   */
  public function emit(string $eventType, string $identifier, string $message, string $status, array $context = []): void;

}
