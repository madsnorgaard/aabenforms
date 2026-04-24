<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\DigitalPost;

use InvalidArgumentException;

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

  private function __construct(
    public readonly string $filename,
    public readonly string $mimeType,
    public readonly ?string $path,
    private readonly ?string $inlineBytes,
    public readonly int $sizeBytes,
  ) {
    if ($filename === '') {
      throw new InvalidArgumentException('Attachment filename cannot be empty.');
    }
    if ($mimeType === '') {
      throw new InvalidArgumentException('Attachment mimeType cannot be empty.');
    }
    if ($path === NULL && $inlineBytes === NULL) {
      throw new InvalidArgumentException('Attachment must carry either a file path or inline bytes.');
    }
    if ($sizeBytes <= 0) {
      throw new InvalidArgumentException(sprintf('Attachment size must be positive; got %d.', $sizeBytes));
    }
    if ($sizeBytes > self::DEFAULT_MAX_SIZE_BYTES) {
      throw new InvalidArgumentException(sprintf(
        'Attachment "%s" is %d bytes, exceeds default cap of %d. Pass a higher limit via the sender validator if your tenant has negotiated a larger SF1601 envelope.',
        $filename, $sizeBytes, self::DEFAULT_MAX_SIZE_BYTES
      ));
    }
  }

  public static function fromFile(string $path, ?string $filename = NULL, ?string $mimeType = NULL): self {
    if (!is_file($path) || !is_readable($path)) {
      throw new InvalidArgumentException(sprintf('Attachment path "%s" is not a readable file.', $path));
    }
    $size = filesize($path);
    if ($size === FALSE) {
      throw new InvalidArgumentException(sprintf('Could not stat "%s".', $path));
    }
    return new self(
      filename: $filename ?? basename($path),
      mimeType: $mimeType ?? self::guessMime($path),
      path: $path,
      inlineBytes: NULL,
      sizeBytes: $size,
    );
  }

  public static function fromBytes(string $bytes, string $filename, string $mimeType): self {
    return new self(
      filename: $filename,
      mimeType: $mimeType,
      path: NULL,
      inlineBytes: $bytes,
      sizeBytes: strlen($bytes),
    );
  }

  public function bytes(): string {
    if ($this->inlineBytes !== NULL) {
      return $this->inlineBytes;
    }
    $raw = file_get_contents($this->path);
    if ($raw === FALSE) {
      throw new InvalidArgumentException(sprintf('Could not read "%s".', $this->path));
    }
    return $raw;
  }

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
