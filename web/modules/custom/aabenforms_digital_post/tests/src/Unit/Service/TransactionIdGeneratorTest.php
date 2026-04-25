<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\Service;

use Drupal\aabenforms_digital_post\Service\TransactionIdGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Tests the TransactionIdGenerator service.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\Service\TransactionIdGenerator
 * @group aabenforms_digital_post
 */
class TransactionIdGeneratorTest extends TestCase {

  /**
   * Tests that generate() returns valid UUID format.
   *
   * @covers ::generate
   */
  public function testGenerateReturnsValidUuidFormat(): void {
    $generator = new TransactionIdGenerator();

    $uuid = $generator->generate();

    // RFC4122 UUID format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
    // UUIDv7 has version 7 in the 13th character and variant bits in position 17.
    $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    $this->assertMatchesRegularExpression(
      $uuidPattern,
      $uuid,
      'Generated ID should be a valid UUID v7 format'
    );
  }

  /**
   * Tests that generate() returns RFC4122 formatted string.
   *
   * @covers ::generate
   */
  public function testGenerateReturnsRfc4122String(): void {
    $generator = new TransactionIdGenerator();

    $uuid = $generator->generate();

    // Should have exactly 36 characters (32 hex + 4 dashes)
    $this->assertEquals(36, strlen($uuid));

    // Should have dashes in correct positions (8-4-4-4-12)
    $parts = explode('-', $uuid);
    $this->assertCount(5, $parts);
    $this->assertEquals(8, strlen($parts[0]));
    $this->assertEquals(4, strlen($parts[1]));
    $this->assertEquals(4, strlen($parts[2]));
    $this->assertEquals(4, strlen($parts[3]));
    $this->assertEquals(12, strlen($parts[4]));
  }

  /**
   * Tests that generate() produces unique IDs.
   *
   * @covers ::generate
   */
  public function testGenerateProducesUniqueIds(): void {
    $generator = new TransactionIdGenerator();

    $ids = [];
    for ($i = 0; $i < 100; $i++) {
      $ids[] = $generator->generate();
    }

    $uniqueIds = array_unique($ids);

    $this->assertCount(100, $uniqueIds, 'All generated IDs should be unique');
  }

  /**
   * Tests that generate() returns lowercase hex characters.
   *
   * @covers ::generate
   */
  public function testGenerateReturnsLowercaseHex(): void {
    $generator = new TransactionIdGenerator();

    $uuid = $generator->generate();

    // Remove dashes and check that all characters are lowercase hex
    $hexOnly = str_replace('-', '', $uuid);

    $this->assertMatchesRegularExpression(
      '/^[0-9a-f]+$/',
      $hexOnly,
      'UUID should only contain lowercase hex characters'
    );
  }

  /**
   * Tests that UUIDv7 has version 7 indicator.
   *
   * @covers ::generate
   */
  public function testGenerateHasVersion7Indicator(): void {
    $generator = new TransactionIdGenerator();

    $uuid = $generator->generate();

    // The 13th character (after first dash) should be '7' for UUID v7
    $parts = explode('-', $uuid);
    $version = $parts[2][0];

    $this->assertEquals('7', $version, 'UUID should be version 7');
  }

  /**
   * Tests that UUIDv7 IDs are time-ordered.
   *
   * @covers ::generate
   */
  public function testGenerateProducesTimeOrderedIds(): void {
    $generator = new TransactionIdGenerator();

    $id1 = $generator->generate();
    usleep(1000); // Wait 1ms to ensure different timestamp.
    $id2 = $generator->generate();

    // For UUID v7, lexicographic sorting should approximate time ordering.
    // The first 8 characters encode the timestamp.
    $timestamp1 = substr(str_replace('-', '', $id1), 0, 12);
    $timestamp2 = substr(str_replace('-', '', $id2), 0, 12);

    $this->assertLessThanOrEqual(
      $timestamp2,
      $timestamp1,
      'Earlier generated UUID should have smaller or equal timestamp prefix'
    );
  }

}
