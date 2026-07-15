<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Model;

/**
 * A document to journalise alongside a case.
 *
 * Document bytes are supplied lazily via a callable so large payloads (and any
 * PII they contain) never sit in a log line or a serialized action config; the
 * connector pulls them only at the moment of upload.
 */
final class EsdhDocument {

  /**
   * Constructs an EsdhDocument.
   *
   * @param string $title
   *   The document title as it should appear in the ESDH.
   * @param string $mimeType
   *   The document MIME type (e.g. "application/pdf").
   * @param callable(): string $bytesProvider
   *   Returns the raw document bytes when invoked.
   * @param bool $finalize
   *   Whether the ESDH should lock/journalise (finalise) the document on upload.
   */
  public function __construct(
    public readonly string $title,
    public readonly string $mimeType,
    private $bytesProvider,
    public readonly bool $finalize = TRUE,
  ) {}

  /**
   * Resolves the document bytes.
   */
  public function bytes(): string {
    return (string) ($this->bytesProvider)();
  }

}
