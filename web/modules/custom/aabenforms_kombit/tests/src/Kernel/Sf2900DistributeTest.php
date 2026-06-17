<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_kombit\Kernel;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the SF2900 distribution service and the distribute action.
 *
 * @group aabenforms_kombit
 * @group aabenforms_case
 */
class Sf2900DistributeTest extends KernelTestBase {

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
    'aabenforms_kombit',
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
   * Makes a persisted case in a status + sets the [case_id] token.
   */
  protected function makeCase(string $status): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create([
      'title' => 'Sag',
      'case_type' => 'friplads',
      'status' => $status,
      'journal_ref' => 'SDI-DEMO-ABCD1234',
    ]);
    $case->save();
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());
    return $case;
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
   * The demo service returns an ACCEPTERET receipt + builds the object.
   */
  public function testServiceAcceptsAndBuildsObject(): void {
    $case = $this->makeCase('afgoerelse');
    $service = $this->container->get('aabenforms_kombit.sf2900_distribution');

    $object = $service->buildDistributionObject($case);
    $this->assertSame('friplads', $object['case_type']);
    $this->assertSame('SDI-DEMO-ABCD1234', $object['journal_ref']);

    $result = $service->distribute($case);
    $this->assertTrue($result->isAccepted());
    $this->assertStringStartsWith('SF2900-DEMO-', $result->transactionId);
  }

  /**
   * Distributing a decided case closes it on ACCEPTERET.
   */
  public function testDistributeClosesDecidedCase(): void {
    $case = $this->makeCase('afgoerelse');
    $this->container->get('plugin.manager.action')
      ->createInstance('aabenforms_case_sf2900_distribute', ['case_id_token' => '[case_id]'])
      ->execute();
    $this->assertSame('lukket', $this->reload((int) $case->id())->getStatus());
  }

  /**
   * An undecided case is not distributed (and not closed).
   */
  public function testUndecidedCaseNotDistributed(): void {
    $case = $this->makeCase('oplyst');
    $this->container->get('plugin.manager.action')
      ->createInstance('aabenforms_case_sf2900_distribute', ['case_id_token' => '[case_id]'])
      ->execute();
    $this->assertSame('oplyst', $this->reload((int) $case->id())->getStatus());
  }

}
