<?php

namespace Drupal\Tests\aabenforms_mitid\Unit\Service;

use Drupal\aabenforms_mitid\Service\MitIdTokenVerifier;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;

/**
 * Tests cryptographic id_token verification.
 *
 * Generates a throwaway RSA keypair, publishes its public half as a JWK in a
 * primed cache (so no HTTP), and signs real RS256 tokens to prove the verifier
 * accepts genuine tokens and rejects forged/tampered/wrong-audience ones.
 *
 * @coversDefaultClass \Drupal\aabenforms_mitid\Service\MitIdTokenVerifier
 * @group aabenforms_mitid
 */
class MitIdTokenVerifierTest extends UnitTestCase {

  /**
   * The verifier under test.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdTokenVerifier
   */
  protected MitIdTokenVerifier $verifier;

  /**
   * PEM-encoded private key used to sign test tokens.
   *
   * @var \OpenSSLAsymmetricKey
   */
  protected $privateKey;

  /**
   * The kid advertised in the JWKS and token headers.
   */
  protected const KID = 'test-kid-1';

  /**
   * The issuer the verifier is configured to accept.
   */
  protected const ISSUER = 'https://idp.example.test/realms/dk';

  /**
   * The audience (client_id) the verifier is configured for.
   */
  protected const CLIENT_ID = 'aabenforms-backend';

  /**
   * The configured jwks_uri (so no discovery HTTP is attempted).
   */
  protected const JWKS_URI = 'https://idp.example.test/realms/dk/certs';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $res = openssl_pkey_new([
      'private_key_bits' => 2048,
      'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    $this->privateKey = $res;
    $details = openssl_pkey_get_details($res);
    $jwk = [
      'kty' => 'RSA',
      'use' => 'sig',
      'alg' => 'RS256',
      'kid' => self::KID,
      'n' => $this->b64url($details['rsa']['n']),
      'e' => $this->b64url($details['rsa']['e']),
    ];

    // Config: issuer, client_id, explicit jwks_uri (skips discovery).
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['issuer', self::ISSUER],
      ['accepted_issuers', []],
      ['client_id', self::CLIENT_ID],
      ['jwks_uri', self::JWKS_URI],
    ]);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    // Prime the cache so jwks() returns our key set without HTTP.
    $cache = $this->createMock(CacheBackendInterface::class);
    $cid = 'aabenforms_mitid:jwks:' . md5(self::JWKS_URI);
    $cache->method('get')->willReturnCallback(
      static fn ($id) => $id === $cid ? (object) ['data' => [$jwk]] : FALSE
    );

    $httpClient = $this->createMock(ClientInterface::class);
    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $this->verifier = new MitIdTokenVerifier($configFactory, $httpClient, $cache, $loggerFactory);
  }

  /**
   * Tests a genuine, correctly-signed token verifies and returns claims.
   *
   * @covers ::verify
   */
  public function testGenuineTokenVerifies(): void {
    $token = $this->makeToken(['cpr' => '0101904521', 'name' => 'Freja Nielsen']);
    $claims = $this->verifier->verify($token);
    $this->assertSame('0101904521', $claims['cpr']);
    $this->assertSame('Freja Nielsen', $claims['name']);
  }

  /**
   * Tests a tampered payload (forged CPR) is rejected - the core attack.
   *
   * @covers ::verify
   */
  public function testForgedPayloadRejected(): void {
    $token = $this->makeToken(['cpr' => '0101904521']);
    [$h, $p, $s] = explode('.', $token);
    $claims = json_decode($this->b64urlDecode($p), TRUE);
    $claims['cpr'] = '0000000000';
    $forged = $h . '.' . $this->b64url(json_encode($claims)) . '.' . $s;

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('signature verification failed');
    $this->verifier->verify($forged);
  }

  /**
   * Tests an alg=none downgrade is rejected.
   *
   * @covers ::verify
   */
  public function testAlgNoneRejected(): void {
    $header = $this->b64url(json_encode(['alg' => 'none', 'typ' => 'JWT']));
    $payload = $this->b64url(json_encode([
      'cpr' => '1',
      'iss' => self::ISSUER,
      'aud' => self::CLIENT_ID,
      'exp' => time() + 600,
    ]));
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Unsupported id_token alg');
    $this->verifier->verify($header . '.' . $payload . '.');
  }

  /**
   * Tests an unknown kid (no matching signing key) is rejected.
   *
   * @covers ::verify
   */
  public function testUnknownKidRejected(): void {
    $token = $this->makeToken(['cpr' => '1'], 'some-other-kid');
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('No matching RS256 signing key');
    $this->verifier->verify($token);
  }

  /**
   * Tests a wrong audience is rejected even with a valid signature.
   *
   * @covers ::verify
   */
  public function testWrongAudienceRejected(): void {
    $token = $this->makeToken(['cpr' => '1', 'aud' => 'some-other-client']);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('audience');
    $this->verifier->verify($token);
  }

  /**
   * Tests a wrong issuer is rejected even with a valid signature.
   *
   * @covers ::verify
   */
  public function testWrongIssuerRejected(): void {
    $token = $this->makeToken(['cpr' => '1', 'iss' => 'https://evil.example/realms/dk']);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('issuer');
    $this->verifier->verify($token);
  }

  /**
   * Tests an expired token is rejected even with a valid signature.
   *
   * @covers ::verify
   */
  public function testExpiredTokenRejected(): void {
    $token = $this->makeToken(['cpr' => '1', 'exp' => time() - 600]);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('expired');
    $this->verifier->verify($token);
  }

  /**
   * Builds and signs an RS256 token with sensible default claims.
   *
   * @param array $claimOverrides
   *   Claims to merge over the defaults (iss/aud/exp/iat).
   * @param string|null $kid
   *   The kid header; defaults to the published signing kid.
   *
   * @return string
   *   The compact JWS.
   */
  protected function makeToken(array $claimOverrides = [], ?string $kid = NULL): string {
    $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => $kid ?? self::KID];
    $claims = $claimOverrides + [
      'iss' => self::ISSUER,
      'aud' => self::CLIENT_ID,
      'exp' => time() + 600,
      'iat' => time(),
      'sub' => 'uuid-123',
    ];
    $signingInput = $this->b64url(json_encode($header)) . '.' . $this->b64url(json_encode($claims));
    openssl_sign($signingInput, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
    return $signingInput . '.' . $this->b64url($signature);
  }

  /**
   * Base64url-encode.
   */
  protected function b64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  /**
   * Base64url-decode.
   */
  protected function b64urlDecode(string $data): string {
    $b64 = strtr($data, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad) {
      $b64 .= str_repeat('=', 4 - $pad);
    }
    return base64_decode($b64);
  }

}
