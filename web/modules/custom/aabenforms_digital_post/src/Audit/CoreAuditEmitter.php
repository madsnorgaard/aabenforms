<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Audit;

use Drupal\aabenforms_core\Service\AuditLogger;

/**
 * Default audit emitter. Delegates to aabenforms_core.audit_logger which
 * writes to the {aabenforms_audit_log} table with a typed action/purpose
 * pair. Sites that want os2web_audit or another backend can service-decorate
 * aabenforms_digital_post.audit_emitter.
 */
final class CoreAuditEmitter implements AuditEmitterInterface {

  public function __construct(
    private readonly AuditLogger $auditLogger,
  ) {
  }

  public function emit(string $eventType, string $identifier, string $message, string $status, array $context = []): void {
    $this->auditLogger->log(
      action: $eventType,
      identifier: $identifier,
      purpose: $message,
      status: $status,
      context: $context,
    );
  }

}
