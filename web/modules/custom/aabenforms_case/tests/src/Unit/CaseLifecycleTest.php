<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_case\Unit;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the case lifecycle invariants (pure static maps, no bootstrap).
 *
 * @group aabenforms_case
 */
class CaseLifecycleTest extends UnitTestCase {

  /**
   * Every transition target must be a defined status.
   */
  public function testTransitionsReferenceKnownStatuses(): void {
    $statuses = array_keys(AabenformsCase::statusOptions());
    foreach (AabenformsCase::allowedTransitions() as $from => $targets) {
      $this->assertContains($from, $statuses, "Source state '$from' is a known status");
      foreach ($targets as $to) {
        $this->assertContains($to, $statuses, "Target state '$to' is a known status");
        $this->assertNotSame($from, $to, "Transition '$from' does not loop to itself");
      }
    }
  }

  /**
   * Every status must appear in the transition table (no orphan states).
   */
  public function testEveryStatusHasTransitionEntry(): void {
    $transitions = AabenformsCase::allowedTransitions();
    foreach (array_keys(AabenformsCase::statusOptions()) as $status) {
      $this->assertArrayHasKey($status, $transitions, "Status '$status' has a transition entry");
    }
  }

  /**
   * Closed ("lukket") is terminal and every case can always be closed.
   */
  public function testClosedIsTerminalAndAlwaysReachable(): void {
    $transitions = AabenformsCase::allowedTransitions();
    $this->assertSame([], $transitions['lukket'], '"lukket" is terminal');

    foreach ($transitions as $from => $targets) {
      if ($from === 'lukket') {
        continue;
      }
      $this->assertContains('lukket', $targets, "State '$from' can transition to lukket");
    }
  }

}
