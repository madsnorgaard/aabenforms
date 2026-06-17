<?php

declare(strict_types=1);

namespace Drupal\aabenforms_kombit\Sf2900;

/**
 * The outcome of an SF2900 distribution: a transaction id + business receipt.
 *
 * Mirrors the Fordelingskomponent's ForretningsValideringsKode: a distribution
 * is only complete once the receiving fagsystem returns ACCEPTERET (or AFVIST).
 */
final class DistributionResult {

  public function __construct(
    public readonly string $transactionId,
    public readonly string $receipt,
  ) {}

  /**
   * Whether the receiving system accepted the distribution (ACCEPTERET).
   */
  public function isAccepted(): bool {
    return $this->receipt === 'ACCEPTERET';
  }

}
