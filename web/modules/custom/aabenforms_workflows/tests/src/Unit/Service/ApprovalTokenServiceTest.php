<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_workflows\Unit\Service;

use Drupal\aabenforms_workflows\Service\ApprovalTokenService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\PrivateKey;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the ApprovalTokenService.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Service\ApprovalTokenService
 * @group aabenforms_workflows
 */
class ApprovalTokenServiceTest extends TestCase {

  /**
   * Mock private key service.
   */
  private PrivateKey $privateKey;

  /**
   * Mock logger.
   */
  private LoggerInterface $logger;

  /**
   * Mock logger factory.
   */
  private LoggerChannelFactoryInterface $loggerFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->privateKey = $this->createMock(PrivateKey::class);
    $this->privateKey->method('get')->willReturn('test-secret-key-for-hmac');

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->loggerFactory
      ->method('get')
      ->with('aabenforms_workflows')
      ->willReturn($this->logger);
  }

  /**
   * Creates the ApprovalTokenService.
   */
  private function createService(): ApprovalTokenService {
    return new ApprovalTokenService($this->privateKey, $this->loggerFactory);
  }

  /**
   * Tests generate and validate round trip.
   *
   * @covers ::generateToken
   * @covers ::validateToken
   */
  public function testGenerateAndValidateRoundTrip(): void {
    $service = $this->createService();

    $submissionId = 123;
    $parentNumber = 1;

    $token = $service->generateToken($submissionId, $parentNumber);
    $isValid = $service->validateToken($submissionId, $parentNumber, $token);

    $this->assertTrue($isValid);
  }

  /**
   * Tests that expired token returns false.
   *
   * @covers ::validateToken
   */
  public function testExpiredTokenReturnsFalse(): void {
    $service = $this->createService();

    $submissionId = 456;
    $parentNumber = 2;

    // Generate token with old timestamp (8 days ago, beyond 7 day expiration).
    $oldTimestamp = time() - (8 * 24 * 60 * 60);
    $token = $service->generateToken($submissionId, $parentNumber, $oldTimestamp);

    $isValid = $service->validateToken($submissionId, $parentNumber, $token);

    $this->assertFalse($isValid);
  }

  /**
   * Tests that tampered hash returns false.
   *
   * @covers ::validateToken
   */
  public function testTamperedHashReturnsFalse(): void {
    $service = $this->createService();

    $submissionId = 789;
    $parentNumber = 1;

    $token = $service->generateToken($submissionId, $parentNumber);

    // Decode, tamper with hash, re-encode.
    $decoded = base64_decode($token);
    [$hash, $timestamp] = explode(':', $decoded, 2);
    $tamperedHash = str_repeat('x', strlen($hash));
    $tamperedToken = base64_encode("{$tamperedHash}:{$timestamp}");

    $isValid = $service->validateToken($submissionId, $parentNumber, $tamperedToken);

    $this->assertFalse($isValid);
  }

  /**
   * Tests that malformed base64 returns false.
   *
   * @covers ::validateToken
   */
  public function testMalformedBase64ReturnsFalse(): void {
    $service = $this->createService();

    $malformedToken = '!!!not-valid-base64!!!';

    $isValid = $service->validateToken(123, 1, $malformedToken);

    $this->assertFalse($isValid);
  }

  /**
   * Tests that token with missing colon separator returns false.
   *
   * @covers ::validateToken
   */
  public function testTokenMissingColonReturnsFalse(): void {
    $service = $this->createService();

    // Valid base64 but no colon separator
    $malformedToken = base64_encode('noseparatorhere');

    $isValid = $service->validateToken(123, 1, $malformedToken);

    $this->assertFalse($isValid);
  }

  /**
   * Tests that non-numeric timestamp returns false.
   *
   * @covers ::validateToken
   */
  public function testNonNumericTimestampReturnsFalse(): void {
    $service = $this->createService();

    // Valid format but timestamp is not numeric.
    $invalidToken = base64_encode('somehash:not-a-number');

    $isValid = $service->validateToken(123, 1, $invalidToken);

    $this->assertFalse($isValid);
  }

  /**
   * Tests that negative timestamp returns false.
   *
   * @covers ::validateToken
   */
  public function testNegativeTimestampReturnsFalse(): void {
    $service = $this->createService();

    // Negative timestamp should be rejected.
    $invalidToken = base64_encode('somehash:-12345');

    $isValid = $service->validateToken(123, 1, $invalidToken);

    $this->assertFalse($isValid);
  }

  /**
   * Tests that future timestamp (too far in future) returns false.
   *
   * @covers ::validateToken
   */
  public function testFarFutureTimestampReturnsFalse(): void {
    $service = $this->createService();

    // Timestamp way in the future (1 year from now).
    $futureTimestamp = time() + (365 * 24 * 60 * 60);
    $token = base64_encode("somehash:{$futureTimestamp}");

    $isValid = $service->validateToken(123, 1, $token);

    $this->assertFalse($isValid);
  }

  /**
   * Tests getTokenTimestamp() extracts correctly.
   *
   * @covers ::getTokenTimestamp
   */
  public function testGetTokenTimestampExtractsCorrectly(): void {
    $service = $this->createService();

    $timestamp = time();
    $token = $service->generateToken(123, 1, $timestamp);

    $extracted = $service->getTokenTimestamp($token);

    $this->assertEquals($timestamp, $extracted);
  }

  /**
   * Tests getTokenTimestamp() returns NULL for invalid token.
   *
   * @covers ::getTokenTimestamp
   */
  public function testGetTokenTimestampReturnsNullForInvalidToken(): void {
    $service = $this->createService();

    $this->assertNull($service->getTokenTimestamp('invalid'));
    $this->assertNull($service->getTokenTimestamp(''));
    $this->assertNull($service->getTokenTimestamp(base64_encode('no-colon')));
  }

  /**
   * Tests isTokenExpired() returns TRUE for expired token.
   *
   * @covers ::isTokenExpired
   */
  public function testIsTokenExpiredReturnsTrueForExpired(): void {
    $service = $this->createService();

    $oldTimestamp = time() - ApprovalTokenService::TOKEN_EXPIRATION - 1;
    $token = $service->generateToken(123, 1, $oldTimestamp);

    $this->assertTrue($service->isTokenExpired($token));
  }

  /**
   * Tests isTokenExpired() returns FALSE for valid token.
   *
   * @covers ::isTokenExpired
   */
  public function testIsTokenExpiredReturnsFalseForValid(): void {
    $service = $this->createService();

    $token = $service->generateToken(123, 1);

    $this->assertFalse($service->isTokenExpired($token));
  }

  /**
   * Tests that different submission IDs produce different tokens.
   *
   * @covers ::generateToken
   */
  public function testDifferentSubmissionIdsDifferentTokens(): void {
    $service = $this->createService();

    $timestamp = time();
    $token1 = $service->generateToken(1, 1, $timestamp);
    $token2 = $service->generateToken(2, 1, $timestamp);

    $this->assertNotEquals($token1, $token2);
  }

  /**
   * Tests that different parent numbers produce different tokens.
   *
   * @covers ::generateToken
   */
  public function testDifferentParentNumbersDifferentTokens(): void {
    $service = $this->createService();

    $timestamp = time();
    $token1 = $service->generateToken(123, 1, $timestamp);
    $token2 = $service->generateToken(123, 2, $timestamp);

    $this->assertNotEquals($token1, $token2);
  }

  /**
   * Tests that token for wrong submission ID is invalid.
   *
   * @covers ::validateToken
   */
  public function testTokenInvalidForWrongSubmissionId(): void {
    $service = $this->createService();

    $token = $service->generateToken(123, 1);

    // Try to validate with different submission ID
    $isValid = $service->validateToken(456, 1, $token);

    $this->assertFalse($isValid);
  }

  /**
   * Tests that token for wrong parent number is invalid.
   *
   * @covers ::validateToken
   */
  public function testTokenInvalidForWrongParentNumber(): void {
    $service = $this->createService();

    $token = $service->generateToken(123, 1);

    // Try to validate with different parent number.
    $isValid = $service->validateToken(123, 2, $token);

    $this->assertFalse($isValid);
  }

  /**
   * Tests that empty token returns false.
   *
   * @covers ::validateToken
   */
  public function testEmptyTokenReturnsFalse(): void {
    $service = $this->createService();

    $isValid = $service->validateToken(123, 1, '');

    $this->assertFalse($isValid);
  }

}
