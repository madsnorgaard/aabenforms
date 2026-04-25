<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\DigitalPost;

/**
 * Immutable attachment. Carries either a file path or raw bytes plus
 * metadata (filename, mime type). Lazy-loads bytes from path only when
 * the transport asks for them via bytes().
 */
final class Attachment {

  /**
   * Default per-attachment size cap, per SF1601 MeMo limits.
   */
  public const DEFAULT_MAX_SIZE_BYTES = 10 * 1024 * 1024;

  /**
   * Constructs an Attachment.
   *
   * @param string $filename
   *   Attachment filename.
   * @param string $mimeType
   *   MIME type of the attachment.
   * @param string|null $path
   *   File path on disk, or NULL when inline bytes are provided.
   * @param string|null $inlineBytes
   *   Raw file bytes, or NULL when a path is provided.
   * @param int $sizeBytes
   *   File size in bytes (must be positive and within the default cap).
   */
  private function __construct(
    public readonly string $filename,
    public readonly string $mimeType,
    public readonly ?string $path,
    private readonly ?string $inlineBytes,
    public readonly int $sizeBytes,
  ) {
    if ($filename === '') {
      throw new \InvalidArgumentException('Attachment filename cannot be empty.');
    }
    if ($mimeType === '') {
      throw new \InvalidArgumentException('Attachment mimeType cannot be empty.');
    }
    if ($path === NULL && $inlineBytes === NULL) {
      throw new \InvalidArgumentException('Attachment must carry either a file path or inline bytes.');
    }
    if ($sizeBytes <= 0) {
      throw new \InvalidArgumentException(sprintf('Attachment size must be positive; got %d.', $sizeBytes));
    }
    if ($sizeBytes > self::DEFAULT_MAX_SIZE_BYTES) {
      throw new \InvalidArgumentException(sprintf(
        'Attachment "%s" is %d bytes, exceeds default cap of %d. Pass a higher limit via the sender validator if your tenant has negotiated a larger SF1601 envelope.',
        $filename, $sizeBytes, self::DEFAULT_MAX_SIZE_BYTES
      ));
    }
  }

  /**
   * Creates an Attachment from a file path.
   *
   * @param string $path
   *   Absolute path to a readable file on disk.
   * @param string|null $filename
   *   Filename override; defaults to basename($path).
   * @param string|null $mimeType
   *   MIME type override; guessed via finfo when NULL.
   *
   * @return self
   *   The constructed attachment.
   */
  public static function fromFile(string $path, ?string $filename = NULL, ?string $mimeType = NULL): self {
    if (!is_file($path) || !is_readable($path)) {
      throw new \InvalidArgumentException(sprintf('Attachment path "%s" is not a readable file.', $path));
    }
    $size = filesize($path);
    if ($size === FALSE) {
      throw new \InvalidArgumentException(sprintf('Could not stat "%s".', $path));
    }
    return new self(
      filename: $filename ?? basename($path),
      mimeType: $mimeType ?? self::guessMime($path),
      path: $path,
      inlineBytes: NULL,
      sizeBytes: $size,
    );
  }

  /**
   * Creates an Attachment from raw bytes.
   *
   * @param string $bytes
   *   Raw file content.
   * @param string $filename
   *   Filename for the attachment.
   * @param string $mimeType
   *   MIME type of the content.
   *
   * @return self
   *   The constructed attachment.
   */
  public static function fromBytes(string $bytes, string $filename, string $mimeType): self {
    return new self(
      filename: $filename,
      mimeType: $mimeType,
      path: NULL,
      inlineBytes: $bytes,
      sizeBytes: strlen($bytes),
    );
  }

  /**
   * Returns the raw bytes of the attachment.
   *
   * Lazy-loads from disk when built via fromFile().
   *
   * @return string
   *   The file content.
   */
  public function bytes(): string {
    if ($this->inlineBytes !== NULL) {
      return $this->inlineBytes;
    }
    $raw = file_get_contents($this->path);
    if ($raw === FALSE) {
      throw new \InvalidArgumentException(sprintf('Could not read "%s".', $this->path));
    }
    return $raw;
  }

  /**
   * Guesses the MIME type of a file using finfo.
   *
   * @param string $path
   *   Path to the file.
   *
   * @return string
   *   Detected MIME type, or 'application/octet-stream' as fallback.
   */
  private static function guessMime(string $path): string {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === FALSE) {
      return 'application/octet-stream';
    }
    $guess = finfo_file($finfo, $path);
    finfo_close($finfo);
    return $guess !== FALSE ? $guess : 'application/octet-stream';
  }

}
