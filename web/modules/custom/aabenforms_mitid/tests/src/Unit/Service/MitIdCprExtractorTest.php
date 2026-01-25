<?php

namespace Drupal\Tests\aabenforms_mitid\Unit\Service;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\aabenforms_mitid\Service\MitIdCprExtractor;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for MitIdCprExtractor service.
 *
 * @coversDefaultClass \Drupal\aabenforms_mitid\Service\MitIdCprExtractor
 * @group aabenforms_mitid
 * @group aabenforms
 */
class MitIdCprExtractorTest extends UnitTestCase {

  /**
   * The MitID CPR extractor service.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdCprExtractor
   */
  protected MitIdCprExtractor $cprExtractor;

  /**
   * Mock audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $auditLogger;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock dependencies.
    $this->auditLogger = $this->createMock(AuditLogger::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->logger);

    // Create service instance.
    $this->cprExtractor = new MitIdCprExtractor($this->auditLogger, $loggerFactory);
  }

  /**
   * Tests CPR extraction from standard 'cpr' claim.
   *
   * @covers ::extractCpr
   * @covers ::parseJwt
   * @covers ::base64UrlDecode
   */
  public function testExtractCprFromStandardClaim(): void {
    $token = $this->createTestJwt([
      'cpr' => '0101904521',
      'name' => 'Freja Nielsen',
      'acr' => 'http://eidas.europa.eu/LoA/substantial',
      'sub' => 'mitid-uuid-123',
    ]);

    // Expect audit log.
    $this->auditLogger->expects($this->once())
      ->method('logCprLookup')
      ->with(
        '0101904521',
        'mitid_token_extraction',
        'success',
        $this->callback(function ($context) {
          return $context['method'] === 'mitid_claims'
            && $context['assurance_level'] === 'http://eidas.europa.eu/LoA/substantial';
        })
      );

    $cpr = $this->cprExtractor->extractCpr($token);
    $this->assertEquals('0101904521', $cpr);
  }

  /**
   * Tests CPR extraction with hyphen formatting (cleaning).
   *
   * @covers ::extractCpr
   */
  public function testExtractCprCleansHyphens(): void {
    $token = $this->createTestJwt([
    // With hyphen.
      'cpr' => '010190-4521',
      'name' => 'Freja Nielsen',
      'acr' => 'http://eidas.europa.eu/LoA/substantial',
      'sub' => 'mitid-uuid-123',
    ]);

    $this->auditLogger->expects($this->once())
      ->method('logCprLookup');

    $cpr = $this->cprExtractor->extractCpr($token);
    // Hyphen removed.
    $this->assertEquals('0101904521', $cpr);
  }

  /**
   * Tests CPR extraction from SAML bridge claim.
   *
   * @covers ::extractCpr
   */
  public function testExtractCprFromSamlBridgeClaim(): void {
    $token = $this->createTestJwt([
      'dk:gov:saml:attribute:CprNumberIdentifier' => '1502856234',
      'name' => 'Mikkel Jensen',
      'acr' => 'http://eidas.europa.eu/LoA/substantial',
      'sub' => 'mitid-uuid-456',
    ]);

    $this->auditLogger->expects($this->once())
      ->method('logCprLookup')
      ->with('1502856234', $this->anything(), $this->anything(), $this->anything());

    $cpr = $this->cprExtractor->extractCpr($token);
    $this->assertEquals('1502856234', $cpr);
  }

  /**
   * Tests CPR extraction from alternative 'mitid.cpr' claim.
   *
   * @covers ::extractCpr
   */
  public function testExtractCprFromAlternativeClaim(): void {
    $token = $this->createTestJwt([
      'mitid.cpr' => '2506924015',
      'name' => 'Sofie Hansen',
      'acr' => 'http://eidas.europa.eu/LoA/high',
      'sub' => 'mitid-uuid-789',
    ]);

    $this->auditLogger->expects($this->once())
      ->method('logCprLookup');

    $cpr = $this->cprExtractor->extractCpr($token);
    $this->assertEquals('2506924015', $cpr);
  }

  /**
   * Tests CPR extraction when no CPR claim is present.
   *
   * @covers ::extractCpr
   */
  public function testExtractCprReturnsNullWhenNotPresent(): void {
    $token = $this->createTestJwt([
      'name' => 'Anonymous User',
      'acr' => 'http://eidas.europa.eu/LoA/substantial',
      'sub' => 'mitid-uuid-000',
    ]);

    // Should log warning.
    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('CPR not found in MitID token'),
        $this->anything()
      );

    $cpr = $this->cprExtractor->extractCpr($token);
    $this->assertNull($cpr);
  }

  /**
   * Tests person data extraction.
   *
   * @covers ::extractPersonData
   */
  public function testExtractPersonData(): void {
    $token = $this->createTestJwt([
      'cpr' => '0101904521',
      'sub' => 'mitid-uuid-123',
      'name' => 'Freja Nielsen',
      'given_name' => 'Freja',
      'family_name' => 'Nielsen',
      'birthdate' => '1990-01-01',
      'email' => 'freja.nielsen@example.dk',
      'acr' => 'http://eidas.europa.eu/LoA/substantial',
      'auth_time' => 1706189000,
      'iss' => 'http://localhost:8080/realms/danish-gov-test',
    ]);

    $personData = $this->cprExtractor->extractPersonData($token);

    $this->assertEquals('0101904521', $personData['cpr']);
    $this->assertEquals('mitid-uuid-123', $personData['mitid_uuid']);
    $this->assertEquals('Freja Nielsen', $personData['name']);
    $this->assertEquals('Freja', $personData['given_name']);
    $this->assertEquals('Nielsen', $personData['family_name']);
    $this->assertEquals('1990-01-01', $personData['birthdate']);
    $this->assertEquals('freja.nielsen@example.dk', $personData['email']);
    $this->assertEquals('http://eidas.europa.eu/LoA/substantial', $personData['assurance_level']);
    $this->assertEquals(1706189000, $personData['auth_time']);
    // Not a business user.
    $this->assertNull($personData['cvr']);
  }

  /**
   * Tests person data extraction for business MitID.
   *
   * @covers ::extractPersonData
   */
  public function testExtractPersonDataForBusinessUser(): void {
    $token = $this->createTestJwt([
      'cpr' => '1205705432',
      'sub' => 'mitid-uuid-business',
      'name' => 'Karen Christensen',
      'given_name' => 'Karen',
      'family_name' => 'Christensen',
      'cvr' => '12345678',
      'organization_name' => 'Christensen ApS',
      'acr' => 'http://eidas.europa.eu/LoA/high',
      'iss' => 'http://localhost:8080/realms/danish-gov-test',
    ]);

    $personData = $this->cprExtractor->extractPersonData($token);

    $this->assertEquals('1205705432', $personData['cpr']);
    $this->assertEquals('12345678', $personData['cvr']);
    $this->assertEquals('Christensen ApS', $personData['business_name']);
    $this->assertEquals('http://eidas.europa.eu/LoA/high', $personData['assurance_level']);
  }

  /**
   * Tests token validation with valid token.
   *
   * @covers ::validateToken
   */
  public function testValidateTokenWithValidToken(): void {
    $now = time();
    $token = $this->createTestJwt([
      'iss' => 'http://localhost:8080/realms/danish-gov-test',
      'sub' => 'mitid-uuid-123',
      'aud' => 'aabenforms-backend',
    // Expires in 1 hour.
      'exp' => $now + 3600,
    // Issued 1 minute ago.
      'iat' => $now - 60,
      'cpr' => '0101904521',
    ]);

    $isValid = $this->cprExtractor->validateToken($token);
    $this->assertTrue($isValid);
  }

  /**
   * Tests token validation with expired token.
   *
   * @covers ::validateToken
   */
  public function testValidateTokenWithExpiredToken(): void {
    $now = time();
    $token = $this->createTestJwt([
      'iss' => 'http://localhost:8080/realms/danish-gov-test',
      'sub' => 'mitid-uuid-123',
      'aud' => 'aabenforms-backend',
    // Expired 1 hour ago.
      'exp' => $now - 3600,
    // Issued 2 hours ago.
      'iat' => $now - 7200,
      'cpr' => '0101904521',
    ]);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('expired'),
        $this->anything()
      );

    $isValid = $this->cprExtractor->validateToken($token);
    $this->assertFalse($isValid);
  }

  /**
   * Tests token validation with missing required claims.
   *
   * @covers ::validateToken
   */
  public function testValidateTokenWithMissingClaims(): void {
    $token = $this->createTestJwt([
      'iss' => 'http://localhost:8080/realms/danish-gov-test',
      // Missing 'sub', 'aud', 'exp', 'iat'.
      'cpr' => '0101904521',
    ]);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Missing required claim'),
        $this->anything()
      );

    $isValid = $this->cprExtractor->validateToken($token);
    $this->assertFalse($isValid);
  }

  /**
   * Tests assurance level extraction and mapping.
   *
   * @covers ::getAssuranceLevel
   */
  public function testGetAssuranceLevelSubstantial(): void {
    $token = $this->createTestJwt([
      'acr' => 'http://eidas.europa.eu/LoA/substantial',
    ]);

    $level = $this->cprExtractor->getAssuranceLevel($token);
    $this->assertEquals('substantial', $level);
  }

  /**
   * Tests assurance level extraction for high level.
   *
   * @covers ::getAssuranceLevel
   */
  public function testGetAssuranceLevelHigh(): void {
    $token = $this->createTestJwt([
      'acr' => 'http://eidas.europa.eu/LoA/high',
    ]);

    $level = $this->cprExtractor->getAssuranceLevel($token);
    $this->assertEquals('high', $level);
  }

  /**
   * Tests assurance level extraction for low level.
   *
   * @covers ::getAssuranceLevel
   */
  public function testGetAssuranceLevelLow(): void {
    $token = $this->createTestJwt([
      'acr' => 'http://eidas.europa.eu/LoA/low',
    ]);

    $level = $this->cprExtractor->getAssuranceLevel($token);
    $this->assertEquals('low', $level);
  }

  /**
   * Tests assurance level with unknown ACR value.
   *
   * @covers ::getAssuranceLevel
   */
  public function testGetAssuranceLevelUnknown(): void {
    $token = $this->createTestJwt([
      // No 'acr' claim.
    ]);

    $level = $this->cprExtractor->getAssuranceLevel($token);
    $this->assertEquals('unknown', $level);
  }

  /**
   * Tests JWT parsing with invalid format.
   *
   * @covers ::extractCpr
   */
  public function testParseJwtWithInvalidFormat(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid JWT format');

    // Invalid JWT (only 2 parts instead of 3)
    $this->cprExtractor->extractCpr('header.payload');
  }

  /**
   * Tests JWT parsing with invalid JSON payload.
   *
   * @covers ::extractCpr
   */
  public function testParseJwtWithInvalidJson(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid JWT payload');

    // Create JWT with invalid JSON in payload.
    $header = base64_encode('{"alg":"RS256"}');
    $payload = base64_encode('{invalid-json}');
    $signature = base64_encode('fake-signature');

    $invalidToken = "$header.$payload.$signature";
    $this->cprExtractor->extractCpr($invalidToken);
  }

  /**
   * Helper method to create a test JWT token.
   *
   * @param array $claims
   *   The claims to include in the token.
   *
   * @return string
   *   A base64url-encoded JWT token.
   */
  protected function createTestJwt(array $claims): string {
    // Create header.
    $header = [
      'alg' => 'RS256',
      'typ' => 'JWT',
    ];

    // Base64url encode header and payload.
    $headerEncoded = $this->base64UrlEncode(json_encode($header));
    $payloadEncoded = $this->base64UrlEncode(json_encode($claims));

    // Create fake signature (unit test doesn't verify signature)
    $signature = $this->base64UrlEncode('fake-signature-for-testing');

    return "$headerEncoded.$payloadEncoded.$signature";
  }

  /**
   * Base64 URL-safe encode helper.
   *
   * @param string $data
   *   The data to encode.
   *
   * @return string
   *   The base64url encoded string.
   */
  protected function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

}
