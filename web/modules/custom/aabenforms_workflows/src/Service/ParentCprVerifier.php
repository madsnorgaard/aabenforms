<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Service;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;

/**
 * Verifies that the MitID-asserted CPR matches the consenting parent.
 *
 * The parent-approval flow sends a signed token to a parent's email; the
 * parent then logs in with MitID. Without this gate, any holder of the
 * approval URL can authenticate with any MitID account and approve the
 * submission. The gate compares the MitID-asserted CPR against the
 * parent_<N>_cpr field captured on the original submission and rejects
 * the approval when they differ.
 *
 * Three outcomes are surfaced as constants so the controller can render
 * citizen-meaningful UX for each:
 * - RESULT_MATCH: CPRs are equal after digit-only normalisation.
 * - RESULT_MISMATCH: both CPRs present, not equal - security failure.
 * - RESULT_MISSING_MITID_CPR: MitID session lacks a CPR claim entirely.
 *
 * "Token expired/malformed/tampered" is NOT this service's concern; it is
 * already handled by ApprovalTokenService upstream.
 */
class ParentCprVerifier {

  /**
   * The CPRs match (normalised, constant-time compared).
   */
  public const RESULT_MATCH = 'match';

  /**
   * Both CPRs are known but they do not match - security failure.
   */
  public const RESULT_MISMATCH = 'mismatch';

  /**
   * The MitID session does not carry a CPR claim - upstream IdP failure.
   */
  public const RESULT_MISSING_MITID_CPR = 'missing_mitid_cpr';

  /**
   * The submission is missing the parent_<N>_cpr field for this parent.
   *
   * Surfaces as a configuration error - the form schema does not carry the
   * expected CPR so we cannot enforce the gate. Treated as a failure.
   */
  public const RESULT_MISSING_EXPECTED_CPR = 'missing_expected_cpr';

  /**
   * The MitID session manager.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdSessionManager
   */
  protected MitIdSessionManager $sessionManager;

  /**
   * The audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger
   */
  protected AuditLogger $auditLogger;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a ParentCprVerifier.
   *
   * @param \Drupal\aabenforms_mitid\Service\MitIdSessionManager $session_manager
   *   The MitID session manager.
   * @param \Drupal\aabenforms_core\Service\AuditLogger $audit_logger
   *   The audit logger - records mismatch / missing-claim events.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    MitIdSessionManager $session_manager,
    AuditLogger $audit_logger,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->sessionManager = $session_manager;
    $this->auditLogger = $audit_logger;
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Verifies the MitID-asserted CPR against the submission's parent CPR.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission carrying parent_<N>_cpr fields.
   * @param int $parent_number
   *   The parent number (1 or 2).
   * @param string $workflow_id
   *   The MitID workflow ID; used to read the asserted CPR from the session.
   *
   * @return string
   *   One of the RESULT_* constants on this class.
   */
  public function verify(WebformSubmissionInterface $submission, int $parent_number, string $workflow_id): string {
    $expected_raw = (string) ($submission->getElementData("parent{$parent_number}_cpr") ?? '');
    $asserted_raw = (string) ($this->sessionManager->getCprFromSession($workflow_id) ?? '');

    $expected = $this->normaliseCpr($expected_raw);
    $asserted = $this->normaliseCpr($asserted_raw);

    $submission_id = (int) $submission->id();
    $submission_uuid = (string) ($submission->uuid() ?? '');

    if ($asserted === '') {
      $this->logger->warning(
        'Parent approval blocked: MitID session lacks CPR claim (submission @sid, parent @parent, workflow @wid)',
        [
          '@sid' => $submission_id,
          '@parent' => $parent_number,
          '@wid' => $workflow_id,
        ]
      );
      // Audit with a stable identifier (the workflow_id) since we have no
      // CPR to hash. The audit row records the upstream-IdP failure.
      $this->auditLogger->logCprLookup(
        $workflow_id,
        'parent_approval_cpr_missing',
        'failure',
        [
          'submission_uuid' => $submission_uuid,
          'parent_number' => $parent_number,
          'workflow_id' => $workflow_id,
        ]
      );
      return self::RESULT_MISSING_MITID_CPR;
    }

    if ($expected === '') {
      $this->logger->error(
        'Parent approval blocked: submission has no parent@parent_cpr field (submission @sid)',
        [
          '@parent' => $parent_number,
          '@sid' => $submission_id,
        ]
      );
      $this->auditLogger->logCprLookup(
        $asserted,
        'parent_approval_cpr_missing_expected',
        'failure',
        [
          'submission_uuid' => $submission_uuid,
          'parent_number' => $parent_number,
          'workflow_id' => $workflow_id,
        ]
      );
      return self::RESULT_MISSING_EXPECTED_CPR;
    }

    if (!hash_equals($expected, $asserted)) {
      $this->logger->warning(
        'Parent approval blocked: MitID CPR does not match parent@parent on submission @sid',
        [
          '@parent' => $parent_number,
          '@sid' => $submission_id,
        ]
      );
      // Hash both CPRs so the audit row never stores the raw values; the
      // hashes still let an investigator confirm "the same wrong CPR is
      // being tried repeatedly" without exposing either one.
      $this->auditLogger->logCprLookup(
        $asserted,
        'parent_approval_cpr_mismatch',
        'failure',
        [
          'submission_uuid' => $submission_uuid,
          'parent_number' => $parent_number,
          'workflow_id' => $workflow_id,
          'expected_hash' => hash('sha256', $expected),
          'asserted_hash' => hash('sha256', $asserted),
        ]
      );
      return self::RESULT_MISMATCH;
    }

    $this->logger->info(
      'Parent approval CPR verified for submission @sid, parent @parent',
      [
        '@sid' => $submission_id,
        '@parent' => $parent_number,
      ]
    );
    $this->auditLogger->logCprLookup(
      $asserted,
      'parent_approval_cpr_match',
      'success',
      [
        'submission_uuid' => $submission_uuid,
        'parent_number' => $parent_number,
        'workflow_id' => $workflow_id,
      ]
    );
    return self::RESULT_MATCH;
  }

  /**
   * Normalises a CPR string to digits only.
   *
   * Strips hyphens, whitespace and any other separators so a hyphenated
   * "010170-1234" compares equal to a digits-only "0101701234". Leading
   * zeros are preserved (CPRs starting with '0101...' are valid and must
   * not be coerced to integer).
   *
   * @param string $cpr
   *   The raw CPR string.
   *
   * @return string
   *   Digit-only CPR. Empty string if no digits were present.
   */
  public function normaliseCpr(string $cpr): string {
    return (string) preg_replace('/[^0-9]/', '', $cpr);
  }

}
