<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\DigitalPost;

use InvalidArgumentException;

/**
 * Immutable Digital Post recipient (CPR or CVR).
 *
 * CPR numbers are validated by length + format, not the modulus-11 checksum.
 * The mod-11 check was retired by CPR office in October 2007 and many
 * currently-issued numbers no longer satisfy it, so rejecting on mod-11
 * would exclude real citizens. Callers who want a stricter check should
 * layer their own validator via the recipient lookup chain.
 */
final class Recipient {

  public const TYPE_CPR = 'cpr';
  public const TYPE_CVR = 'cvr';

  private function __construct(
    public readonly string $type,
    public readonly string $identifier,
  ) {
  }

  /**
   * Create a CPR recipient. Accepts DDMMYY-XXXX or DDMMYYXXXX.
   */
  public static function cpr(string $cpr): self {
    $digits = preg_replace('/\D+/', '', $cpr) ?? '';
    if (strlen($digits) !== 10) {
      throw new InvalidArgumentException(sprintf('CPR must be exactly 10 digits; got %d.', strlen($digits)));
    }
    return new self(self::TYPE_CPR, $digits);
  }

  /**
   * Create a CVR recipient. Accepts "12 34 56 78" or 12345678.
   */
  public static function cvr(string $cvr): self {
    $digits = preg_replace('/\D+/', '', $cvr) ?? '';
    if (strlen($digits) !== 8) {
      throw new InvalidArgumentException(sprintf('CVR must be exactly 8 digits; got %d.', strlen($digits)));
    }
    return new self(self::TYPE_CVR, $digits);
  }

  /**
   * Return a stable SHA-256 hash of the identifier. Useful for logging
   * without persisting the raw CPR/CVR.
   */
  public function identifierHash(): string {
    return hash('sha256', $this->type . ':' . $this->identifier);
  }

}
