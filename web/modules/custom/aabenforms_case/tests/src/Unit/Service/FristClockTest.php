<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Unit\Service;

use Drupal\aabenforms_case\Service\FristClock;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\aabenforms_case\Service\FristClock
 *
 * @group aabenforms_case
 */
class FristClockTest extends UnitTestCase {

  /**
   * Monday 2024-01-01 09:00:00 UTC.
   */
  protected const MONDAY_0900 = 1704099600;

  /**
   * Builds a FristClock with the given per-area config.
   *
   * @param array<string, array{unit:string, amount:int}> $frister
   *   The per-area deadline config.
   */
  protected function clock(array $frister): FristClock {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('frister')->willReturn($frister);

    $factory = $this->createMock(ConfigFactoryInterface::class);
    $factory->method('get')->with('aabenforms_case.settings')->willReturn($config);

    return new FristClock($factory);
  }

  /**
   * @covers ::computeDue
   */
  public function testComputeDueHours(): void {
    $clock = $this->clock(['underretning' => ['unit' => 'hours', 'amount' => 24]]);
    $this->assertSame(
      self::MONDAY_0900 + (24 * 3600),
      $clock->computeDue(self::MONDAY_0900, 'underretning')
    );
  }

  /**
   * @covers ::computeDue
   * @covers ::addWorkingDays
   */
  public function testComputeDueWorkingDaysSkipsWeekend(): void {
    $clock = $this->clock(['anerkend' => ['unit' => 'hverdage', 'amount' => 6]]);
    // From Monday, 6 working days lands on the next Tuesday (8 calendar days),
    // skipping the intervening Saturday and Sunday.
    $expected = self::MONDAY_0900 + (8 * 86400);
    $this->assertSame($expected, $clock->computeDue(self::MONDAY_0900, 'anerkend'));
  }

  /**
   * @covers ::computeDue
   */
  public function testComputeDueStraksIsImmediate(): void {
    $clock = $this->clock(['friplads' => ['unit' => 'straks', 'amount' => 0]]);
    $this->assertSame(self::MONDAY_0900, $clock->computeDue(self::MONDAY_0900, 'friplads'));
  }

  /**
   * @covers ::computeDue
   */
  public function testUnknownAreaFallsBackToDefault(): void {
    $clock = $this->clock(['default' => ['unit' => 'hours', 'amount' => 1]]);
    $this->assertSame(
      self::MONDAY_0900 + 3600,
      $clock->computeDue(self::MONDAY_0900, 'no_such_area')
    );
  }

  /**
   * @covers ::computeState
   * @dataProvider stateProvider
   */
  public function testComputeState(int $due, int $now, string $expected): void {
    $clock = $this->clock([]);
    $this->assertSame($expected, $clock->computeState($due, $now));
  }

  /**
   * Data provider for ::testComputeState.
   *
   * @return array<string, array{0:int,1:int,2:string}>
   *   Cases: [due, now, expected].
   */
  public static function stateProvider(): array {
    $now = self::MONDAY_0900;
    return [
      'overdue' => [$now - 1, $now, 'roed'],
      'due now' => [$now, $now, 'roed'],
      'within 24h' => [$now + 3600, $now, 'gul'],
      'exactly 24h' => [$now + 86400, $now, 'gul'],
      'more than 24h' => [$now + 86401, $now, 'groen'],
    ];
  }

}
