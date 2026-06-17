<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests the aabenforms_case_open ECA action end-to-end.
 *
 * Proves a submission opens a case with the deadline (frist) computed from the
 * per-area config, references the submission, and starts in "modtaget".
 *
 * @group aabenforms_case
 */
class OpenCaseActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'key',
    'encrypt',
    'real_aes',
    'domain',
    'modeler_api',
    'eca',
    'webform',
    'aabenforms_core',
    'aabenforms_case',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('aabenforms_case');
    $this->installSchema('webform', ['webform']);
    $this->installSchema('aabenforms_core', ['aabenforms_audit_log']);

    // Set the per-area deadlines directly rather than installing the module's
    // config/install (which also contains the ECA flow + webform that pull in
    // eca_content/modeler_api not enabled here).
    $this->config('aabenforms_case.settings')
      ->set('frister', [
        'underretning' => ['unit' => 'hours', 'amount' => 24],
        'default' => ['unit' => 'hverdage', 'amount' => 28],
      ])
      ->save();
  }

  /**
   * Invokes the action with a submission token and asserts the case.
   */
  public function testOpenCaseFromSubmission(): void {
    // A minimal webform + submission (no CPR field, so the core presave
    // encryption hook is a no-op and needs no encryption profile).
    Webform::create(['id' => 'underretning', 'title' => 'Underretning'])->save();
    $submission = WebformSubmission::create([
      'webform_id' => 'underretning',
      'data' => ['concern_details' => 'Bekymring for et barn.'],
    ]);
    $submission->save();

    // Put the submission on the token environment, as a content_entity:insert
    // event would, then run the action.
    $tokenService = $this->container->get('eca.token_services');
    $tokenService->addTokenData('webform_submission', $submission);

    /** @var \Drupal\Core\Action\ActionManager $actionManager */
    $actionManager = $this->container->get('plugin.manager.action');
    $action = $actionManager->createInstance('aabenforms_case_open', [
      'case_type' => 'underretning',
      'case_id_token' => 'case_id',
    ]);
    $action->execute();

    // Exactly one case was created from the submission.
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    $cases = $storage->loadMultiple();
    $this->assertCount(1, $cases, 'One case was created');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = reset($cases);

    // Its id was written back to the token (eca wraps scalars, so cast).
    $this->assertSame(
      (string) $case->id(),
      (string) $tokenService->getTokenData('case_id'),
      'case_id token holds the new case id'
    );
    $this->assertSame('underretning', $case->getCaseType());
    $this->assertSame('modtaget', $case->getStatus());
    $this->assertSame((int) $submission->id(), (int) $case->getSubmissionId());

    // The underretning area is configured as 24 hours, so the deadline is
    // exactly 24h after the receipt date.
    $this->assertSame(
      $case->getModtagelsesdato() + (24 * 3600),
      $case->getFristDue(),
      'Frist is 24h after receipt for the underretning area'
    );
  }

}
