<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Kernel;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the AabenformsCase entity: schema, fields, defaults, revisions.
 *
 * @group aabenforms_case
 */
class CaseEntityTest extends KernelTestBase {

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
  }

  /**
   * Creates a case and asserts defaults + persistence.
   */
  public function testCreateAndDefaults(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create([
      'title' => 'Underretning - indsendelse #1',
      'case_type' => 'underretning',
      'frist_due' => 1704186000,
      'modtagelsesdato' => 1704099600,
    ]);
    $case->save();

    $this->assertNotNull($case->id());
    $this->assertSame('modtaget', $case->getStatus(), 'Default status is modtaget');
    $this->assertSame('underretning', $case->getCaseType());
    $this->assertSame(1704186000, $case->getFristDue());
    $this->assertSame(1704099600, $case->getModtagelsesdato());

    // Reload from storage to prove it persisted.
    $storage->resetCache();
    $reloaded = $storage->load($case->id());
    $this->assertInstanceOf(AabenformsCase::class, $reloaded);
    $this->assertSame('Underretning - indsendelse #1', $reloaded->label());
  }

  /**
   * A status change with a new revision leaves an auditable revision trail.
   */
  public function testTransitionCreatesRevision(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create([
      'title' => 'Sag',
      'case_type' => 'underretning',
    ]);
    $case->save();
    $firstVid = $case->getRevisionId();

    $case->setStatus('oplyst');
    $case->setNewRevision(TRUE);
    $case->setRevisionLogMessage('Oplyst af sagsbehandler.');
    $case->save();

    $this->assertNotSame($firstVid, $case->getRevisionId(), 'A new revision was created');
    $this->assertSame('oplyst', $case->getStatus());
    $this->assertSame('Oplyst af sagsbehandler.', $case->getRevisionLogMessage());
  }

}
