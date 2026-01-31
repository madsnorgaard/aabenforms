<?php

namespace Drupal\Tests\aabenforms_webform\Unit\Service;

use Drupal\aabenforms_webform\Service\CvrValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the CvrValidator service.
 *
 * @group aabenforms_webform
 * @coversDefaultClass \Drupal\aabenforms_webform\Service\CvrValidator
 */
class CvrValidatorTest extends UnitTestCase {

  /**
   * The CVR validator service.
   *
   * @var \Drupal\aabenforms_webform\Service\CvrValidator
   */
  protected CvrValidator $validator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->validator = new CvrValidator();
  }

  /**
   * Tests validation of valid CVR numbers.
   *
   * @covers ::isValid
   * @covers ::isValidModulus11
   *
   * @dataProvider validCvrProvider
   */
  public function testValidCvrNumbers(string $cvr): void {
    $this->assertTrue($this->validator->isValid($cvr), "CVR {$cvr} should be valid");
  }

  /**
   * Provides valid CVR numbers for testing.
   *
   * @return array
   *   Array of valid CVR numbers.
   */
  public static function validCvrProvider(): array {
    return [
      // Real Danish companies (public data) - verified modulus-11.
      'Nets Denmark' => ['20016175'],
      'TDC' => ['14773908'],
      'Danske Bank' => ['61126228'],
      'DSB' => ['28291035'],
      'Arla Foods' => ['25313763'],
    ];
  }

  /**
   * Tests validation with whitespace and hyphens.
   *
   * @covers ::isValid
   */
  public function testValidCvrWithFormatting(): void {
    // CVR with spaces (using valid CVR numbers).
    $this->assertTrue($this->validator->isValid('20 01 61 75'), 'CVR with spaces should be valid');
    $this->assertTrue($this->validator->isValid('14 77 39 08'), 'CVR with spaces should be valid');

    // CVR with hyphens.
    $this->assertTrue($this->validator->isValid('61-12-62-28'), 'CVR with hyphens should be valid');
  }

  /**
   * Tests validation of invalid CVR format.
   *
   * @covers ::isValid
   *
   * @dataProvider invalidFormatProvider
   */
  public function testInvalidCvrFormat(string $cvr, string $reason): void {
    $this->assertFalse($this->validator->isValid($cvr), "CVR {$cvr} should be invalid ({$reason})");
  }

  /**
   * Provides invalid CVR formats for testing.
   *
   * @return array
   *   Array of invalid CVR numbers with reasons.
   */
  public static function invalidFormatProvider(): array {
    return [
      'too short' => ['1234567', 'only 7 digits'],
      'too long' => ['123456789', '9 digits'],
      'contains letters' => ['1234567A', 'contains letter'],
      'empty string' => ['', 'empty'],
      'special characters' => ['12@34567', 'special character'],
      'only whitespace' => ['        ', 'only spaces'],
    ];
  }

  /**
   * Tests validation of invalid CVR checksum.
   *
   * @covers ::isValid
   * @covers ::isValidModulus11
   *
   * @dataProvider invalidChecksumProvider
   */
  public function testInvalidCvrChecksum(string $cvr): void {
    $this->assertFalse($this->validator->isValid($cvr), "CVR {$cvr} should fail modulus-11 check");
  }

  /**
   * Provides CVR numbers with invalid checksums.
   *
   * @return array
   *   Array of CVR numbers with bad checksums.
   */
  public static function invalidChecksumProvider(): array {
    return [
      'wrong last digit' => ['20016176'],
      'all ones' => ['11111111'],
      'sequential digits' => ['12345678'],
      'modified valid' => ['25053504'],
    ];
  }

  /**
   * Tests CVR number formatting.
   *
   * @covers ::format
   *
   * @dataProvider formatProvider
   */
  public function testCvrFormatting(string $input, string $expected): void {
    $this->assertEquals($expected, $this->validator->format($input));
  }

  /**
   * Provides CVR numbers for formatting tests.
   *
   * @return array
   *   Array of input/expected pairs.
   */
  public static function formatProvider(): array {
    return [
      'plain number' => ['20016175', '20 01 61 75'],
      'already formatted' => ['20 01 61 75', '20 01 61 75'],
      'with hyphens' => ['20-01-61-75', '20 01 61 75'],
      'mixed formatting' => ['20 016175', '20 01 61 75'],
      'invalid length preserved' => ['1234567', '1234567'],
      'letters preserved' => ['ABC12345', 'ABC12345'],
    ];
  }

}
