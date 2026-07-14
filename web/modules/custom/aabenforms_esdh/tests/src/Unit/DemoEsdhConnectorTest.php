<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_esdh\Unit;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_esdh\Model\EsdhResult;
use Drupal\aabenforms_esdh\Plugin\AabenformsEsdh\DemoEsdhConnector;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\aabenforms_esdh\Plugin\AabenformsEsdh\DemoEsdhConnector
 *
 * @group aabenforms_esdh
 */
class DemoEsdhConnectorTest extends UnitTestCase {

  /**
   * The demo connector journalises to a deterministic reference.
   *
   * @covers ::journaliseCase
   */
  public function testJournaliseIsDeterministic(): void {
    $connector = new DemoEsdhConnector([], 'demo', ['id' => 'demo', 'label' => 'Demo', 'demo' => TRUE]);

    $case = $this->createMock(AabenformsCase::class);
    $case->method('uuid')->willReturn('6b5d4c3e-2a1f-4e8d-9c7b-3f2e1d0c9b8a');

    $result = $connector->journaliseCase($case);

    $this->assertInstanceOf(EsdhResult::class, $result);
    $this->assertTrue($result->isJournalised());
    $this->assertSame('demo', $result->esdhSystem);
    $this->assertSame('ESDH-DEMO-6B5D4C3E', $result->reference);
    $this->assertTrue($connector->isDemo());
  }

}
