<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_esdh\Model\EsdhResult;

/**
 * Contract every ESDH connector (SBSYS, WorkZone, Acadre, GetOrganized, ...).
 *
 * An ESDH connector hands a case (and optionally its documents) to a
 * municipality's electronic records system - the system of record. This is
 * distinct from SF1470 Sags- og Dokumentindeks, which is only the
 * fælleskommunale metadata *index* (so SAPA/Borgerblikket can see a case
 * exists). A complete flow does BOTH: journalise to the ESDH via a connector,
 * and register in SF1470 via aabenforms_case_journal.
 */
interface EsdhConnectorInterface {

  /**
   * The connector machine id (e.g. "sbsys", "workzone", "getorganized").
   */
  public function id(): string;

  /**
   * A human-readable label for the settings UI.
   */
  public function label(): string;

  /**
   * TRUE for the demo connector (synthesised references, no live transport).
   */
  public function isDemo(): bool;

  /**
   * Journalises a case into the ESDH and returns the reference.
   *
   * Implementations MUST be idempotent-friendly (the caller also guards on
   * esdh_ref) and MUST distinguish transient from permanent failure on the
   * returned EsdhResult so a flow never closes a case on a retry-able error.
   *
   * @param \Drupal\aabenforms_case\Entity\AabenformsCase $case
   *   The case to journalise.
   * @param \Drupal\aabenforms_esdh\Model\EsdhDocument[] $documents
   *   Documents to attach (lazy byte providers).
   *
   * @return \Drupal\aabenforms_esdh\Model\EsdhResult
   *   The outcome, carrying the ESDH reference on success.
   */
  public function journaliseCase(AabenformsCase $case, array $documents = []): EsdhResult;

}
