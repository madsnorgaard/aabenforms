<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Kernel;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the aabenforms_case_journal (SF1470 demo) action.
 *
 * @group aabenforms_case
 */
class JournalCaseActionTest extends KernelTestBase {

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
   * Makes a persisted case + sets the [case_id] token.
   */
  protected function makeCase(): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create(['title' => 'Sag', 'case_type' => 'friplads', 'status' => 'modtaget']);
    $case->save();
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());
    return $case;
  }

  /**
   * Runs the journal action.
   */
  protected function journal(): void {
    $this->container->get('plugin.manager.action')
      ->createInstance('aabenforms_case_journal', ['case_id_token' => '[case_id]'])
      ->execute();
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
   * Journaling sets a reference and records a revision; it is idempotent.
   */
  public function testJournalSetsReferenceIdempotently(): void {
    $case = $this->makeCase();
    $this->journal();
    $reloaded = $this->reload((int) $case->id());
    $ref = (string) $reloaded->get('journal_ref')->value;
    $this->assertNotEmpty($ref, 'A journal reference was set');
    $this->assertStringStartsWith('SDI-DEMO-', $ref);

    // Re-journaling keeps the same reference.
    $this->journal();
    $this->assertSame($ref, (string) $this->reload((int) $case->id())->get('journal_ref')->value);
  }

}
