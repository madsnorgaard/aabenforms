<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the aabenforms_case_income_lookup (demo eIndkomst) action.
 *
 * @group aabenforms_case
 */
class FetchIncomeActionTest extends KernelTestBase {

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
    $this->installSchema('aabenforms_core', ['aabenforms_audit_log']);
  }

  /**
   * Runs the income lookup for a CPR and returns the resulting income token.
   */
  protected function incomeFor(string $cpr): ?string {
    $tokens = $this->container->get('eca.token_services');
    $tokens->addTokenData('cpr', $cpr);
    $this->container->get('plugin.manager.action')
      ->createInstance('aabenforms_case_income_lookup', [
        'cpr_token' => 'cpr',
        'result_token' => 'friplads_income',
      ])
      ->execute();
    $value = $tokens->getTokenData('friplads_income');
    return $value === NULL ? NULL : (string) $value;
  }

  /**
   * The demo derivation is deterministic: last digit < 5 → low, else high.
   */
  public function testIncomeDerivedFromCpr(): void {
    // Last digit 4 → low income (eligible band).
    $this->assertSame('150000', $this->incomeFor('0101801234'));
    // Last digit 7 → high income (manual band).
    $this->assertSame('400000', $this->incomeFor('0101801237'));
  }

  /**
   * An empty CPR records a failed step and sets no income.
   */
  public function testEmptyCprIsHandled(): void {
    $this->assertNull($this->incomeFor(''));
  }

}
