<?php

declare(strict_types=1);

namespace Drupal\aabenforms_kombit\Service;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_kombit\Sf2900\DistributionResult;

/**
 * Distributes a finished case to a fagsystem via SF2900 (Fordelingskomponent).
 *
 * DEMO: builds the distribution object from the case and synthesises a
 * transaction id + an ACCEPTERET business receipt, so the case lifecycle can
 * be demonstrated end to end without live infrastructure.
 *
 * Production replaces the body of distribute() with the real chain behind the
 * same return contract: an STS token (SF1512/SF1514) signed with the OCES3
 * system certificate, a DistributionFormular/Dokument built from the case, a
 * SOAP call to the DistributionService, documents via SFTP, and the business
 * receipt (ACCEPTERET/AFVIST) relayed back from the receiving fagsystem.
 */
class Sf2900DistributionService {

  /**
   * Builds the distribution object for a case (the payload sent to FKO).
   *
   * Kept separate so the demo and a future real transport share one mapping.
   *
   * @return array<string, string>
   *   The distribution object fields.
   */
  public function buildDistributionObject(AabenformsCase $case): array {
    return [
      'objekt_type' => 'FORMULAR',
      'case_type' => $case->getCaseType(),
      'journal_ref' => (string) ($case->get('journal_ref')->value ?? ''),
      'kle_emne' => (string) ($case->get('kle_emne')->value ?? ''),
    ];
  }

  /**
   * Distributes a case and returns the business receipt.
   */
  public function distribute(AabenformsCase $case): DistributionResult {
    // Build the payload (used by both demo and real transport).
    $this->buildDistributionObject($case);

    // DEMO transaction id derived from the case UUID; real transport returns
    // the FordelingsobjektAfsend transaction id and the relayed receipt.
    $transactionId = 'SF2900-DEMO-' . strtoupper(substr(str_replace('-', '', (string) $case->uuid()), 0, 8));

    return new DistributionResult($transactionId, 'ACCEPTERET');
  }

}
