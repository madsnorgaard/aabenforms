<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Computes statutory deadlines (frister) and their traffic-light state.
 *
 * Deadlines are driven by per-area configuration in aabenforms_case.settings
 * (key "frister"), so a new casework area only needs config, not code. Each
 * area defines a duration as either calendar hours or working days
 * (hverdage), the latter skipping weekends per RSL/forvaltningslov practice.
 *
 * NOTE: Danish public holidays are not yet subtracted from working-day math
 * (TODO: add a holiday calendar). Weekends are handled.
 */
class FristClock {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Computes the deadline timestamp for a case area.
   *
   * @param int $receiptTs
   *   The receipt (modtagelse) Unix timestamp the clock starts from.
   * @param string $area
   *   The casework area key (e.g. "underretning"). Falls back to the
   *   "default" area when the key is not configured.
   *
   * @return int
   *   The due Unix timestamp.
   */
  public function computeDue(int $receiptTs, string $area): int {
    [$unit, $amount] = $this->resolveArea($area);

    return match ($unit) {
      'hours' => $receiptTs + ($amount * 3600),
      'hverdage' => $this->addWorkingDays($receiptTs, $amount),
      // "straks" (immediate) and any unknown unit: due is the receipt itself.
      default => $receiptTs,
    };
  }

  /**
   * Returns the traffic-light state for a deadline.
   *
   * @param int $dueTs
   *   The due Unix timestamp.
   * @param int $nowTs
   *   The current Unix timestamp.
   *
   * @return string
   *   One of "groen", "gul", "roed".
   */
  public function computeState(int $dueTs, int $nowTs): string {
    if ($nowTs >= $dueTs) {
      return 'roed';
    }
    // Amber when the deadline is less than 24 hours away.
    if (($dueTs - $nowTs) <= 86400) {
      return 'gul';
    }
    return 'groen';
  }

  /**
   * Resolves the configured [unit, amount] for an area.
   *
   * @return array{0:string,1:int}
   *   The unit ("hours", "hverdage", "straks", ...) and integer amount.
   */
  protected function resolveArea(string $area): array {
    $frister = $this->configFactory->get('aabenforms_case.settings')->get('frister') ?? [];
    $config = $frister[$area] ?? $frister['default'] ?? ['unit' => 'hverdage', 'amount' => 28];
    $unit = (string) ($config['unit'] ?? 'hverdage');
    $amount = (int) ($config['amount'] ?? 0);
    return [$unit, $amount];
  }

  /**
   * Adds N working days (Mon-Fri) to a timestamp.
   *
   * The time-of-day of the receipt is preserved; only whole working days are
   * counted forward, skipping Saturdays and Sundays.
   */
  protected function addWorkingDays(int $receiptTs, int $days): int {
    if ($days <= 0) {
      return $receiptTs;
    }
    $ts = $receiptTs;
    $added = 0;
    while ($added < $days) {
      $ts += 86400;
      $weekday = (int) gmdate('N', $ts);
      // N: 1 (Mon) .. 7 (Sun); count only 1-5.
      if ($weekday <= 5) {
        $added++;
      }
    }
    return $ts;
  }

}
