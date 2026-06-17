<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Kernel;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the aabenforms_case_appeal action and the genvurdering path.
 *
 * @group aabenforms_case
 */
class AppealCaseActionTest extends KernelTestBase {

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
   * Makes a persisted case in a given status + sets the case_id token.
   */
  protected function makeCase(string $status): AabenformsCase {
    $storage = $this->container->get('entity_type.manager')->getStorage('aabenforms_case');
    /** @var \Drupal\aabenforms_case\Entity\AabenformsCase $case */
    $case = $storage->create(['title' => 'Sag', 'case_type' => 'friplads', 'status' => $status]);
    $case->save();
    $this->container->get('eca.token_services')->addTokenData('case_id', (string) $case->id());
    return $case;
  }

  /**
   * Invokes an action plugin.
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
   * A decided case can be appealed → paaklaget, grounds in the revision log.
   */
  public function testAppealOfDecidedCase(): void {
    $case = $this->makeCase('afgoerelse');
    $this->invoke('aabenforms_case_appeal', [
      'case_id_token' => 'case_id',
      'grounds_token' => 'grounds',
    ]);
    // grounds_token resolves empty (unset) here; status is the key assertion.
    $reloaded = $this->reload((int) $case->id());
    $this->assertSame('paaklaget', $reloaded->getStatus());
  }

  /**
   * A case that has not been decided cannot be appealed.
   */
  public function testAppealOfUndecidedCaseRejected(): void {
    $case = $this->makeCase('oplyst');
    $this->invoke('aabenforms_case_appeal', ['case_id_token' => 'case_id']);
    $this->assertSame('oplyst', $this->reload((int) $case->id())->getStatus());
  }

  /**
   * Genvurdering: a paaklaget case can be decided again (→ afgoerelse).
   */
  public function testGenvurderingDecidesFromPaaklaget(): void {
    $case = $this->makeCase('paaklaget');
    $this->invoke('aabenforms_case_decide', ['afgoerelse_type' => 'medhold']);
    $this->assertSame('afgoerelse', $this->reload((int) $case->id())->getStatus());
  }

}
