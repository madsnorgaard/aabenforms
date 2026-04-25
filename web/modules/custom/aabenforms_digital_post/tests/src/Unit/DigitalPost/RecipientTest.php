<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\DigitalPost;

use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Recipient DTO.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\DigitalPost\Recipient
 * @group aabenforms_digital_post
 */
class RecipientTest extends TestCase {

  /**
   * Tests that CPR with 10 digits is accepted.
   *
   * @covers ::cpr
   */
  public function testCprAccepts10Digits(): void {
    $recipient = Recipient::cpr('1234567890');

    $this->assertEquals(Recipient::TYPE_CPR, $recipient->type);
    $this->assertEquals('1234567890', $recipient->identifier);
  }

  /**
   * Tests that CPR with dash format (DDMMYY-XXXX) is accepted.
   *
   * @covers ::cpr
   */
  public function testCprAcceptsDashFormat(): void {
    $recipient = Recipient::cpr('010190-1234');

    $this->assertEquals(Recipient::TYPE_CPR, $recipient->type);
    $this->assertEquals('0101901234', $recipient->identifier);
  }

  /**
   * Tests that CPR with 9 digits throws InvalidArgumentException.
   *
   * @covers ::cpr
   */
  public function testCprRejects9Digits(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('CPR must be exactly 10 digits; got 9.');

    Recipient::cpr('123456789');
  }

  /**
   * Tests that CPR with 11 digits throws InvalidArgumentException.
   *
   * @covers ::cpr
   */
  public function testCprRejects11Digits(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('CPR must be exactly 10 digits; got 11.');

    Recipient::cpr('12345678901');
  }

  /**
   * Tests that CPR strips non-digit characters.
   *
   * @covers ::cpr
   */
  public function testCprStripsNonDigits(): void {
    $recipient = Recipient::cpr('01 01 90-1234');

    $this->assertEquals('0101901234', $recipient->identifier);
  }

  /**
   * Tests that CVR with 8 digits is accepted.
   *
   * @covers ::cvr
   */
  public function testCvrAccepts8Digits(): void {
    $recipient = Recipient::cvr('12345678');

    $this->assertEquals(Recipient::TYPE_CVR, $recipient->type);
    $this->assertEquals('12345678', $recipient->identifier);
  }

  /**
   * Tests that CVR with spaces (12 34 56 78 format) is accepted.
   *
   * @covers ::cvr
   */
  public function testCvrAcceptsSpaceFormat(): void {
    $recipient = Recipient::cvr('12 34 56 78');

    $this->assertEquals(Recipient::TYPE_CVR, $recipient->type);
    $this->assertEquals('12345678', $recipient->identifier);
  }

  /**
   * Tests that CVR with 7 digits throws InvalidArgumentException.
   *
   * @covers ::cvr
   */
  public function testCvrRejects7Digits(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('CVR must be exactly 8 digits; got 7.');

    Recipient::cvr('1234567');
  }

  /**
   * Tests that CVR with 9 digits throws InvalidArgumentException.
   *
   * @covers ::cvr
   */
  public function testCvrRejects9Digits(): void {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('CVR must be exactly 8 digits; got 9.');

    Recipient::cvr('123456789');
  }

  /**
   * Tests that identifierHash() returns stable SHA-256 hash.
   *
   * @covers ::identifierHash
   */
  public function testIdentifierHashIsStable(): void {
    $recipient1 = Recipient::cpr('1234567890');
    $recipient2 = Recipient::cpr('1234567890');

    $hash1 = $recipient1->identifierHash();
    $hash2 = $recipient2->identifierHash();

    $this->assertEquals($hash1, $hash2);
    $this->assertEquals(64, strlen($hash1), 'SHA-256 hash should be 64 hex characters');
  }

  /**
   * Tests that identifierHash() does not expose raw identifier.
   *
   * @covers ::identifierHash
   */
  public function testIdentifierHashDoesNotExposeRawIdentifier(): void {
    $cpr = '1234567890';
    $recipient = Recipient::cpr($cpr);

    $hash = $recipient->identifierHash();

    $this->assertStringNotContainsString($cpr, $hash);
  }

  /**
   * Tests that different identifiers produce different hashes.
   *
   * @covers ::identifierHash
   */
  public function testDifferentIdentifiersProduceDifferentHashes(): void {
    $recipient1 = Recipient::cpr('1234567890');
    $recipient2 = Recipient::cpr('0987654321');

    $this->assertNotEquals($recipient1->identifierHash(), $recipient2->identifierHash());
  }

  /**
   * Tests that same identifier with different types produce different hashes.
   *
   * @covers ::identifierHash
   */
  public function testDifferentTypesProduceDifferentHashes(): void {
    // Use same digits for both CPR and CVR (where possible).
    $recipient1 = Recipient::cpr('1234567890');
    $recipient2 = Recipient::cvr('12345678');

    $this->assertNotEquals($recipient1->identifierHash(), $recipient2->identifierHash());
  }

}
