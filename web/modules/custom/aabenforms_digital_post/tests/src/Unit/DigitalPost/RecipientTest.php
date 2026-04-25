<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\DigitalPost;

use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Recipient value object.
 *
 * The contract under test:
 *   - cpr() / cvr() factories normalise digits (strip dashes/spaces) and
 *     reject anything that isn't exactly the right length.
 *   - identifierHash() never echoes the raw identifier in any form a
 *     log-skimmer could match against.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\DigitalPost\Recipient
 * @group aabenforms_digital_post
 */
class RecipientTest extends UnitTestCase {

  /**
   * Bare 10-digit CPR is accepted.
   */
  public function testCprAcceptsBareDigits(): void {
    $r = Recipient::cpr('0101900001');
    $this->assertSame(Recipient::TYPE_CPR, $r->type);
    $this->assertSame('0101900001', $r->identifier);
  }

  /**
   * Dashed CPR (DDMMYY-XXXX) is normalised to bare digits.
   */
  public function testCprAcceptsDashed(): void {
    $r = Recipient::cpr('010190-0001');
    $this->assertSame('0101900001', $r->identifier);
  }

  /**
   * CPR with whitespace is normalised.
   */
  public function testCprAcceptsWhitespace(): void {
    $r = Recipient::cpr(' 010190 0001 ');
    $this->assertSame('0101900001', $r->identifier);
  }

  /**
   * Too-short CPR is rejected.
   */
  public function testCprTooShortRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Recipient::cpr('0101900');
  }

  /**
   * Too-long CPR is rejected.
   */
  public function testCprTooLongRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Recipient::cpr('010190000123');
  }

  /**
   * Non-digit-only CPR after stripping is rejected by length.
   */
  public function testCprAllLettersRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Recipient::cpr('abcdefghij');
  }

  /**
   * Bare 8-digit CVR is accepted.
   */
  public function testCvrAcceptsBareDigits(): void {
    $r = Recipient::cvr('12345678');
    $this->assertSame(Recipient::TYPE_CVR, $r->type);
    $this->assertSame('12345678', $r->identifier);
  }

  /**
   * Spaced CVR ("12 34 56 78") is normalised.
   */
  public function testCvrAcceptsSpaced(): void {
    $r = Recipient::cvr('12 34 56 78');
    $this->assertSame('12345678', $r->identifier);
  }

  /**
   * Wrong-length CVR is rejected.
   */
  public function testCvrWrongLengthRejected(): void {
    $this->expectException(\InvalidArgumentException::class);
    Recipient::cvr('1234567');
  }

  /**
   * IdentifierHash() returns a deterministic SHA-256.
   */
  public function testIdentifierHashIsStable(): void {
    $a = Recipient::cpr('0101900001');
    $b = Recipient::cpr('010190-0001');
    $this->assertSame($a->identifierHash(), $b->identifierHash());
    $this->assertSame(64, strlen($a->identifierHash()));
  }

  /**
   * The hash is a deterministic SHA-256 of the namespaced identifier.
   *
   * This guards against future refactors replacing hashing with some other
   * encoding while avoiding probabilistic substring assertions.
   */
  public function testIdentifierHashDoesNotLeakRaw(): void {
    $cpr = '2506924015';
    $hash = Recipient::cpr($cpr)->identifierHash();
    $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    $this->assertSame(hash('sha256', Recipient::TYPE_CPR . ':' . $cpr), $hash);
  }

  /**
   * CPR vs CVR with the same digit string hash to different values.
   *
   * The hash is namespaced by type, so a CPR "12345678..." won't collide
   * with a CVR "12345678".
   */
  public function testCprAndCvrHashDifferently(): void {
    $cpr = Recipient::cpr('1234567812');
    $cvr = Recipient::cvr('12345678');
    $this->assertNotSame($cpr->identifierHash(), $cvr->identifierHash());
  }

}
