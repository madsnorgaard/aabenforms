<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_esdh\Kernel;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the aabenforms_case_esdh_journal ECA action against the demo connector.
 *
 * @group aabenforms_esdh
 */
class EsdhJournalActionTest extends KernelTestBase {

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
    'aabenforms_esdh',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('aabenforms_case');
    $this->installSchema('aabenforms_core', ['aabenforms_audit_log']);
    $this->config('aabenforms_esdh.settings')->set('active_connector', 'demo')->save();
  }

  /**
   * Creates a persisted case and puts its id on the token environment.
   */
  protected function openCase(): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create(['title' => 'Sag', 'case_type' => 'merudgifter', 'status' => 'modtaget']);
    $case->save();
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());
    return $case;
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
   * Runs the ESDH journal action.
   */
  protected function journal(): void {
    $this->container->get('plugin.manager.action')
      ->createInstance('aabenforms_case_esdh_journal', [])
      ->execute();
  }

  /**
   * The demo connector journalises and stamps esdh_ref + esdh_system.
   */
  public function testJournalisesToDemoEsdh(): void {
    $case = $this->openCase();
    $this->journal();
    $reloaded = $this->reload((int) $case->id());
    $this->assertSame('demo', (string) $reloaded->get('esdh_system')->value);
    $this->assertStringStartsWith('ESDH-DEMO-', (string) $reloaded->get('esdh_ref')->value);
  }

  /**
   * A second run is idempotent - the reference is not overwritten.
   */
  public function testJournaliseIsIdempotent(): void {
    $case = $this->openCase();
    $this->journal();
    $first = (string) $this->reload((int) $case->id())->get('esdh_ref')->value;
    $revStorage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    $before = (int) $revStorage->getQuery()->accessCheck(FALSE)->allRevisions()->condition('id', $case->id())->count()->execute();

    $this->journal();
    $second = (string) $this->reload((int) $case->id())->get('esdh_ref')->value;
    $after = (int) $revStorage->getQuery()->accessCheck(FALSE)->allRevisions()->condition('id', $case->id())->count()->execute();

    $this->assertSame($first, $second, 'ESDH reference unchanged on re-run');
    $this->assertSame($before, $after, 'No extra revision minted on idempotent re-run');
  }

}
