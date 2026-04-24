<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Certificate;

/**
 * Immutable certificate material returned by a CertificateLocator.
 *
 * Carries the cert as a file path and an optional passphrase. Raw bytes
 * are intentionally NOT in this DTO - SOAP clients read from the path,
 * and log emitters should never have a reason to see the bytes.
 */
final class Certificate {

  public function __construct(
    public readonly string $path,
    public readonly ?string $passphrase,
    public readonly string $sourceLabel,
  ) {
  }

}
