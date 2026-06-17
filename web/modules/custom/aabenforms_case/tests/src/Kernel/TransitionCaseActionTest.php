<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Kernel;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the aabenforms_case_transition ECA action.
 *
 * Covers the lawful transition chain used by the fripladstilskud auto-decision
 * branch (modtaget -> oplyst -> afgoerelse), that the case id is read back from
 * the ECA token (incl. the bare-token DataTransferObject path), and that an
 * unlawful transition is rejected rather than silently written.
 *
 * @group aabenforms_case
 */
class TransitionCaseActionTest extends KernelTestBase {

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
    $this->installEntitySchema('aabenforms_case');
    $this->installSchema('aabenforms_core', ['aabenforms_audit_log']);
  }

  /**
   * Creates a persisted case in the given status.
   */
  protected function makeCase(string $status = 'modtaget'): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create([
      'title' => 'Friplads',
      'case_type' => 'friplads',
      'status' => $status,
    ]);
    $case->save();
    return $case;
  }

  /**
   * Runs the transition action against a case id token.
   */
  protected function transition(string $caseIdToken, string $target, string $log): void {
    /** @var \Drupal\Core\Action\ActionManager $manager */
    $manager = $this->container->get('plugin.manager.action');
    $action = $manager->createInstance('aabenforms_case_transition', [
      'case_id_token' => $caseIdToken,
      'target_status' => $target,
      'log_message' => $log,
    ]);
    $action->execute();
  }

  /**
   * Reloads a case fresh from storage.
   */
  protected function reload(int $id): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    $storage->resetCache([$id]);
    return $storage->load($id);
  }

  /**
   * The lawful auto-decision chain modtaget -> oplyst -> afgoerelse.
   */
  public function testLawfulTransitionChain(): void {
    $case = $this->makeCase('modtaget');
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());

    $this->transition('[case_id]', 'oplyst', 'Straksbehandling.');
    $reloaded = $this->reload((int) $case->id());
    $this->assertSame('oplyst', $reloaded->getStatus());
    $this->assertSame('Straksbehandling.', $reloaded->getRevisionLogMessage());

    $this->transition('[case_id]', 'afgoerelse', 'Automatisk afgørelse.');
    $reloaded = $this->reload((int) $case->id());
    $this->assertSame('afgoerelse', $reloaded->getStatus());
  }

  /**
   * A bare token name (eca DataTransferObject) resolves to the case id.
   *
   * Guards the CaseActionBase::getTokenValue object-handling fix: without it a
   * bare token returns empty and the transition is wrongly rejected.
   */
  public function testBareTokenNameResolves(): void {
    $case = $this->makeCase('modtaget');
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());

    $this->transition('case_id', 'oplyst', 'Via bart token.');
    $this->assertSame('oplyst', $this->reload((int) $case->id())->getStatus());
  }

  /**
   * An unlawful transition (afgoerelse -> oplyst) is rejected, not written.
   */
  public function testUnlawfulTransitionRejected(): void {
    $case = $this->makeCase('afgoerelse');
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());

    $this->transition('[case_id]', 'oplyst', 'Forsøg på ulovlig overgang.');
    $this->assertSame('afgoerelse', $this->reload((int) $case->id())->getStatus(), 'Status is unchanged after an illegal transition');
  }

}
