<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\Service;

use Drupal\aabenforms_digital_post\Service\TransactionIdGenerator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests TransactionIdGenerator emits time-ordered RFC 4122 UUIDv7s.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\Service\TransactionIdGenerator
 * @group aabenforms_digital_post
 */
class TransactionIdGeneratorTest extends UnitTestCase {

  /**
   * Generate() returns a string matching the RFC 4122 v7 layout.
   *
   * Pattern: 8-4-4-4-12 hex digits, version nibble = 7, variant nibble
   * in {8, 9, a, b}.
   */
  public function testGenerateMatchesUuidV7Regex(): void {
    $gen = new TransactionIdGenerator();
    $id = $gen->generate();
    $this->assertSame(36, strlen($id));
    $this->assertMatchesRegularExpression(
      '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
      $id
    );
  }

  /**
   * Two consecutive generate() calls produce distinct ids.
   */
  public function testGenerateProducesUniqueIds(): void {
    $gen = new TransactionIdGenerator();
    $a = $gen->generate();
    $b = $gen->generate();
    $this->assertNotSame($a, $b);
  }

  /**
   * Sequential ids sort lexicographically (the v7 time-ordering guarantee).
   */
  public function testGenerateIsTimeOrdered(): void {
    $gen = new TransactionIdGenerator();
    $first = $gen->generate();
    // Tiny sleep to guarantee a tick of the v7 millisecond clock so the
    // ordering is observable; v7 has 48-bit ms precision.
    usleep(2000);
    $second = $gen->generate();
    $this->assertLessThan(0, strcmp($first, $second));
  }

}
