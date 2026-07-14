<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_workflows\Unit\Service;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\aabenforms_core\Service\CprAccess;
use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\aabenforms_workflows\Service\AppealOwnershipVerifier;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\aabenforms_workflows\Service\AppealOwnershipVerifier
 *
 * @group aabenforms_workflows
 */
class AppealOwnershipVerifierTest extends UnitTestCase {

  /**
   * Builds a verifier where the session returns $sessionCpr and the case's
   * submission holds $caseCpr (already "decrypted" by the CprAccess stub).
   *
   * @param string|null $sessionCpr
   *   CPR the MitID session returns (NULL = no session).
   * @param string|null $caseCpr
   *   Applicant CPR on the case's submission (NULL = no submission/case).
   */
  protected function verifier(?string $sessionCpr, ?string $caseCpr): AppealOwnershipVerifier {
    $session = $this->createMock(MitIdSessionManager::class);
    $session->method('getCprFromSession')->willReturn($sessionCpr);

    $cprAccess = $this->createMock(CprAccess::class);
    // reveal() is a pass-through here: the stored value already IS the cpr.
    $cprAccess->method('reveal')->willReturnArgument(0);

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    if ($caseCpr === NULL) {
      $caseStorage = $this->createMock(EntityStorageInterface::class);
      $caseStorage->method('load')->willReturn(NULL);
      $etm->method('getStorage')->willReturn($caseStorage);
    }
    else {
      $submission = $this->createMock(WebformSubmissionInterface::class);
      $submission->method('getElementData')->willReturnMap([
        ['applicant_cpr', $caseCpr],
        ['cpr', NULL],
      ]);
      $case = $this->createMock(AabenformsCase::class);
      $case->method('getSubmissionId')->willReturn('7');
      $caseStorage = $this->createMock(EntityStorageInterface::class);
      $caseStorage->method('load')->willReturn($case);
      $submissionStorage = $this->createMock(EntityStorageInterface::class);
      $submissionStorage->method('load')->willReturn($submission);
      $etm->method('getStorage')->willReturnMap([
        ['aabenforms_case', $caseStorage],
        ['webform_submission', $submissionStorage],
      ]);
    }

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->createMock(LoggerInterface::class));

    return new AppealOwnershipVerifier(
      $session,
      $this->createMock(AuditLogger::class),
      $loggerFactory,
      $cprAccess,
      $etm,
    );
  }

  /**
   * @covers ::verify
   */
  public function testMatch(): void {
    $this->assertSame(
      AppealOwnershipVerifier::RESULT_MATCH,
      $this->verifier('0101901234', '0101901234')->verify('1', 'wf_abc'),
    );
  }

  /**
   * A different session CPR is a mismatch (the core bypass is closed).
   *
   * @covers ::verify
   */
  public function testMismatch(): void {
    $this->assertSame(
      AppealOwnershipVerifier::RESULT_MISMATCH,
      $this->verifier('0101901234', '2202800000')->verify('1', 'wf_abc'),
    );
  }

  /**
   * A hyphenated session CPR still matches the digit-only stored CPR.
   *
   * @covers ::verify
   */
  public function testNormalisedMatch(): void {
    $this->assertSame(
      AppealOwnershipVerifier::RESULT_MATCH,
      $this->verifier('010190-1234', '0101901234')->verify('1', 'wf_abc'),
    );
  }

  /**
   * No MitID session CPR fails closed.
   *
   * @covers ::verify
   */
  public function testMissingSessionFailsClosed(): void {
    $this->assertSame(
      AppealOwnershipVerifier::RESULT_MISSING_MITID_CPR,
      $this->verifier(NULL, '0101901234')->verify('1', 'wf_abc'),
    );
  }

  /**
   * An unresolvable case fails closed.
   *
   * @covers ::verify
   */
  public function testMissingCaseFailsClosed(): void {
    $this->assertSame(
      AppealOwnershipVerifier::RESULT_MISSING_EXPECTED_CPR,
      $this->verifier('0101901234', NULL)->verify('999', 'wf_abc'),
    );
  }

}
