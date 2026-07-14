<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Kernel;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the lawful lifecycle is enforced at the storage layer.
 *
 * The ECA actions validate transitions, but the entity itself must be the
 * backstop so no other save path (admin form, JSON:API, VBO, programmatic)
 * can reach an unlawful state or silently overwrite the audited revision.
 *
 * @group aabenforms_case
 */
class CaseLifecycleEnforcementTest extends KernelTestBase {

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
   * Creates and persists a case in the given status (founding save is exempt).
   */
  protected function caseIn(string $status): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create(['title' => 'Sag', 'case_type' => 'friplads', 'status' => $status]);
    $case->save();
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
   * An illegal status transition is rejected at save time.
   */
  public function testIllegalTransitionThrows(): void {
    $case = $this->reload((int) $this->caseIn('modtaget')->id());
    // modtaget may only reach oplyst or lukket - never afgoerelse directly.
    $case->setStatus('afgoerelse');
    $this->expectException(EntityStorageException::class);
    $case->save();
  }

  /**
   * A lawful transition is allowed and mints a new audited revision.
   */
  public function testLawfulTransitionMintsRevision(): void {
    $case = $this->caseIn('modtaget');
    $id = (int) $case->id();
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');

    $before = (int) $storage->getQuery()->accessCheck(FALSE)->allRevisions()->condition('id', $id)->count()->execute();
    $reloaded = $this->reload($id);
    $reloaded->setStatus('oplyst');
    $reloaded->save();
    $after = (int) $storage->getQuery()->accessCheck(FALSE)->allRevisions()->condition('id', $id)->count()->execute();

    $this->assertSame('oplyst', $this->reload($id)->getStatus());
    $this->assertSame($before + 1, $after, 'Every update mints a new revision');
  }

  /**
   * A closed case is immutable - no field may change.
   */
  public function testClosedCaseIsImmutable(): void {
    $case = $this->reload((int) $this->caseIn('lukket')->id());
    $case->set('title', 'Ændret efter lukning');
    $this->expectException(EntityStorageException::class);
    $case->save();
  }

  /**
   * Partshøring cannot be set on a decided case (SetPartshoeringAction guard).
   */
  public function testPartshoeringRejectedOnDecidedCase(): void {
    $case = $this->caseIn('afgoerelse');
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());
    $this->container->get('plugin.manager.action')
      ->createInstance('aabenforms_case_partshoering', ['state' => 'afventer'])
      ->execute();
    $this->assertSame(
      'ikke_paakraevet',
      (string) $this->reload((int) $case->id())->get('partshoering_state')->value,
      'Partshøring state unchanged on a decided case'
    );
  }

}
