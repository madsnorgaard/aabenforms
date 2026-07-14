<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Model;

/**
 * The outcome of an ESDH journalising call.
 *
 * The transport-agnostic return contract shared by the demo driver and every
 * production connector. A real vendor swap must honour this shape and MUST NOT
 * bake in a synchronous business receipt where the vendor delivers one
 * asynchronously (see the SF2900 lesson in the module README): map an
 * async/queued acknowledgement to status "pending" and reconcile later.
 */
final class EsdhResult {

  public const STATUS_CREATED = 'created';
  public const STATUS_EXISTS = 'exists';
  public const STATUS_PENDING = 'pending';
  public const STATUS_REJECTED = 'rejected';

  /**
   * @param string $esdhSystem
   *   The connector id that produced this result (e.g. "sbsys", "workzone").
   * @param string $reference
   *   The ESDH case reference (sagsnummer / recordId), empty on rejection.
   * @param string $status
   *   One of the STATUS_* constants.
   * @param bool $transient
   *   TRUE when a rejection is retry-able (timeout, 5xx) rather than permanent
   *   (validation, auth). Callers must NOT close a case on a transient failure.
   * @param string $message
   *   A human-readable, PII-free status/receipt message.
   */
  public function __construct(
    public readonly string $esdhSystem,
    public readonly string $reference,
    public readonly string $status,
    public readonly bool $transient = FALSE,
    public readonly string $message = '',
  ) {}

  /**
   * TRUE when the case is journalised (freshly created or already present).
   */
  public function isJournalised(): bool {
    return in_array($this->status, [self::STATUS_CREATED, self::STATUS_EXISTS], TRUE);
  }

  /**
   * A created/exists result carrying an ESDH reference.
   */
  public static function journalised(string $system, string $reference, bool $existed = FALSE): self {
    return new self($system, $reference, $existed ? self::STATUS_EXISTS : self::STATUS_CREATED);
  }

  /**
   * A rejection, flagged transient (retry) or permanent.
   */
  public static function rejected(string $system, string $message, bool $transient = FALSE): self {
    return new self($system, '', self::STATUS_REJECTED, $transient, $message);
  }

}
