<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Kernel;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the aabenforms_case_decide action and its FVL §19/§25 enforcement.
 *
 * @group aabenforms_case
 */
class MakeDecisionActionTest extends KernelTestBase {

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
   * Creates a persisted case in "oplyst" (ready for a decision) + sets token.
   */
  protected function caseInOplyst(): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create([
      'title' => 'Friplads',
      'case_type' => 'friplads',
      'status' => 'oplyst',
    ]);
    $case->save();
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());
    return $case;
  }

  /**
   * Invokes an aabenforms_case action plugin.
   */
  protected function invoke(string $pluginId, array $config): void {
    $this->container->get('plugin.manager.action')->createInstance($pluginId, $config)->execute();
  }

  /**
   * Reloads a case fresh.
   */
  protected function reload(int $id): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    $storage->resetCache([$id]);
    return $storage->load($id);
  }

  /**
   * Full medhold needs no klagevejledning and sets no klagefrist.
   */
  public function testMedholdGranted(): void {
    $case = $this->caseInOplyst();
    $this->invoke('aabenforms_case_decide', [
      'afgoerelse_type' => 'medhold',
    ]);
    $reloaded = $this->reload((int) $case->id());
    $this->assertSame('afgoerelse', $reloaded->getStatus());
    $this->assertSame('medhold', (string) $reloaded->get('afgoerelse_type')->value);
    $this->assertNull($reloaded->get('klagefrist')->value, 'Medhold sets no klagefrist');
  }

  /**
   * FVL §25: an adverse decision without a klagevejledning is rejected.
   */
  public function testAdverseWithoutKlagevejledningBlocked(): void {
    $case = $this->caseInOplyst();
    $this->invoke('aabenforms_case_decide', [
      'afgoerelse_type' => 'afslag',
      'klagevejledning' => '',
    ]);
    $reloaded = $this->reload((int) $case->id());
    $this->assertSame('oplyst', $reloaded->getStatus(), 'Status unchanged - decision blocked');
    $this->assertNull($reloaded->get('afgoerelse_type')->value);
  }

  /**
   * An adverse decision records the outcome but does NOT start the klagefrist.
   *
   * The klagefrist runs from notification (FVL meddelelseskrav), so the decide
   * step leaves it unset; aabenforms_case_set_klagefrist starts it once the
   * letter is confirmed sent. A §19 stk. 2 exemption is supplied here because
   * no partshøring was held.
   */
  public function testAdverseDecisionDefersKlagefristToDispatch(): void {
    $case = $this->caseInOplyst();
    $now = $this->container->get('datetime.time')->getRequestTime();
    $this->invoke('aabenforms_case_decide', [
      'afgoerelse_type' => 'afslag',
      'klagevejledning' => 'Du kan klage til Ankestyrelsen inden for 4 uger.',
      'klagefrist_uger' => 4,
      'partshoering_exemption' => 'Afgørelsen bygger alene på borgerens egne oplysninger (FVL §19 stk. 2).',
    ]);
    $decided = $this->reload((int) $case->id());
    $this->assertSame('afgoerelse', $decided->getStatus());
    $this->assertSame('afslag', (string) $decided->get('afgoerelse_type')->value);
    $this->assertNull($decided->get('klagefrist')->value, 'Klagefrist is not started at decide time');

    // The dispatch step starts the clock.
    $this->invoke('aabenforms_case_set_klagefrist', ['klagefrist_uger' => 4]);
    $this->assertSame($now + (4 * 7 * 86400), (int) $this->reload((int) $case->id())->get('klagefrist')->value);
  }

  /**
   * FVL §19: an adverse decision needs a hearing or a recorded exemption.
   *
   * The silent-skip path (no hearing, no exemption) is closed.
   */
  public function testAdverseWithoutHearingOrExemptionBlocked(): void {
    $case = $this->caseInOplyst();
    // Default partshoering_state is "ikke_paakraevet"; no exemption supplied.
    $this->invoke('aabenforms_case_decide', [
      'afgoerelse_type' => 'afslag',
      'klagevejledning' => 'Klagevejledning til Ankestyrelsen.',
    ]);
    $reloaded = $this->reload((int) $case->id());
    $this->assertSame('oplyst', $reloaded->getStatus(), 'Adverse decision blocked without hearing or §19 stk. 2 exemption');
    $this->assertNull($reloaded->get('afgoerelse_type')->value);
  }

  /**
   * An adverse decision succeeds once partshøring is concluded (afsluttet).
   */
  public function testAdverseAfterHearingConcluded(): void {
    $case = $this->caseInOplyst();
    $this->invoke('aabenforms_case_partshoering', ['state' => 'afventer']);
    $this->invoke('aabenforms_case_partshoering', ['state' => 'afsluttet']);
    $this->assertSame('afsluttet', (string) $this->reload((int) $case->id())->get('partshoering_state')->value);

    $this->invoke('aabenforms_case_decide', [
      'afgoerelse_type' => 'afslag',
      'klagevejledning' => 'Klagevejledning til Ankestyrelsen.',
    ]);
    $this->assertSame('afgoerelse', $this->reload((int) $case->id())->getStatus(), 'Adverse decision allowed after concluded hearing');
  }

  /**
   * FVL §19: an adverse decision is blocked while partshøring is "afventer".
   */
  public function testPartshoeringGateBlocksAdverse(): void {
    $case = $this->caseInOplyst();
    // Open a hearing via the partshøring action, then attempt an adverse decision.
    $this->invoke('aabenforms_case_partshoering', ['state' => 'afventer']);
    $this->assertSame('afventer', (string) $this->reload((int) $case->id())->get('partshoering_state')->value);

    $this->invoke('aabenforms_case_decide', [
      'afgoerelse_type' => 'afslag',
      'klagevejledning' => 'Klagevejledning til Ankestyrelsen.',
    ]);
    $this->assertSame('oplyst', $this->reload((int) $case->id())->getStatus(), 'Decision blocked until partshøring is concluded');
  }

}
