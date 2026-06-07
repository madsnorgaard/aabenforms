<?php

namespace Drupal\aabenforms_mitid\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Cryptographically verifies MitID/NemLog-in id_tokens against the IdP JWKS.
 *
 * The previous code trusted the id_token after only decoding its payload, with
 * no signature check and no issuer/audience validation - a JWT with arbitrary
 * CPR claims would be accepted. This service closes that hole: it verifies the
 * RS256 signature against the issuer's published JWKS (fetched + cached), then
 * validates iss, aud, exp/iat/nbf. It fails CLOSED - any doubt throws and the
 * caller must reject the login.
 *
 * Dependency-free on purpose: openssl_verify() performs the actual signature
 * check; this class only converts a JWK (modulus/exponent) into a PEM public
 * key via a deterministic DER encoding, so no composer dependency is added to
 * a security-critical path.
 */
class MitIdTokenVerifier {

  /**
   * JWKS cache lifetime (seconds).
   */
  protected const JWKS_TTL = 3600;

  /**
   * Allowed clock skew when checking exp/iat/nbf (seconds).
   */
  protected const LEEWAY = 60;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ClientInterface $httpClient,
    protected CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('aabenforms_mitid');
  }

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Verifies an id_token and returns its validated claims.
   *
   * @param string $idToken
   *   Compact JWS (header.payload.signature).
   *
   * @return array
   *   The validated claim set.
   *
   * @throws \RuntimeException
   *   On any signature, issuer, audience or temporal validation failure.
   */
  public function verify(string $idToken): array {
    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
      throw new \RuntimeException('id_token is not a compact JWS (expected 3 parts).');
    }
    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    $header = json_decode($this->b64UrlDecode($encodedHeader), TRUE);
    $claims = json_decode($this->b64UrlDecode($encodedPayload), TRUE);
    if (!is_array($header) || !is_array($claims)) {
      throw new \RuntimeException('id_token header or payload is not valid JSON.');
    }

    $alg = $header['alg'] ?? '';
    if ($alg !== 'RS256') {
      // Reject anything we do not cryptographically verify - notably "none"
      // and any HMAC alg an attacker might substitute.
      throw new \RuntimeException('Unsupported id_token alg: ' . $alg . ' (only RS256 is accepted).');
    }

    $kid = $header['kid'] ?? '';
    $jwk = $this->signingKey($kid);
    if ($jwk === NULL) {
      throw new \RuntimeException('No matching RS256 signing key for kid: ' . ($kid ?: '(none)'));
    }

    $signature = $this->b64UrlDecode($encodedSignature);
    $signingInput = $encodedHeader . '.' . $encodedPayload;
    $pem = $this->jwkToPem($jwk);

    $ok = openssl_verify($signingInput, $signature, $pem, OPENSSL_ALGO_SHA256);
    if ($ok !== 1) {
      throw new \RuntimeException('id_token signature verification failed.');
    }

    $this->validateClaims($claims);
    return $claims;
  }

  /**
   * Validates issuer, audience and temporal claims.
   *
   * @param array $claims
   *   The decoded claims.
   *
   * @throws \RuntimeException
   *   If any check fails.
   */
  protected function validateClaims(array $claims): void {
    $config = $this->configFactory->get('aabenforms_mitid.settings');
    $now = time();

    // Issuer allowlist: the primary `issuer` plus any `accepted_issuers`. The
    // extra list exists for split-hostname IdP setups where the token's iss
    // (browser-facing host) differs from the backend-reachable host used for
    // the token exchange and JWKS fetch (e.g. a containerised Keycloak).
    $accepted = array_values(array_filter(array_merge(
      [(string) ($config->get('issuer') ?? '')],
      array_map('strval', (array) ($config->get('accepted_issuers') ?? [])),
    ), 'strlen'));
    if ($accepted !== [] && !in_array((string) ($claims['iss'] ?? ''), $accepted, TRUE)) {
      throw new \RuntimeException('id_token issuer mismatch.');
    }

    $clientId = (string) ($config->get('client_id') ?? '');
    $aud = $claims['aud'] ?? [];
    $audList = is_array($aud) ? $aud : [$aud];
    if ($clientId !== '' && !in_array($clientId, $audList, TRUE)) {
      throw new \RuntimeException('id_token audience does not include this client.');
    }

    if (!isset($claims['exp']) || ($claims['exp'] + self::LEEWAY) < $now) {
      throw new \RuntimeException('id_token is expired.');
    }
    if (isset($claims['nbf']) && ($claims['nbf'] - self::LEEWAY) > $now) {
      throw new \RuntimeException('id_token is not yet valid (nbf).');
    }
    if (isset($claims['iat']) && ($claims['iat'] - self::LEEWAY) > $now) {
      throw new \RuntimeException('id_token issued in the future (iat).');
    }
  }

  /**
   * Returns the JWKS signing key for a kid, refreshing once on a miss.
   *
   * @param string $kid
   *   The key id from the token header.
   *
   * @return array|null
   *   The matching JWK, or NULL when none is found.
   */
  protected function signingKey(string $kid): ?array {
    $key = $this->findKey($this->jwks(FALSE), $kid);
    if ($key !== NULL) {
      return $key;
    }
    // Key rotation: the IdP may have published a new kid since we cached.
    // Refetch once, bypassing the cache, before giving up.
    return $this->findKey($this->jwks(TRUE), $kid);
  }

  /**
   * Selects an RSA signing key (use=sig, RS256) matching kid from a key set.
   */
  protected function findKey(array $keys, string $kid): ?array {
    foreach ($keys as $jwk) {
      if (($jwk['kty'] ?? '') !== 'RSA') {
        continue;
      }
      if (($jwk['use'] ?? 'sig') !== 'sig') {
        continue;
      }
      if (isset($jwk['alg']) && $jwk['alg'] !== 'RS256') {
        continue;
      }
      if ($kid === '' || ($jwk['kid'] ?? '') === $kid) {
        return $jwk;
      }
    }
    return NULL;
  }

  /**
   * Fetches the JWKS key array, cached for JWKS_TTL.
   *
   * @param bool $forceRefresh
   *   When TRUE, bypass and overwrite the cache.
   *
   * @return array
   *   The list of JWK entries (possibly empty).
   */
  protected function jwks(bool $forceRefresh): array {
    $uri = $this->jwksUri();
    if ($uri === '') {
      throw new \RuntimeException('No jwks_uri configured or derivable for id_token verification.');
    }
    $cid = 'aabenforms_mitid:jwks:' . md5($uri);

    if (!$forceRefresh) {
      $cached = $this->cache->get($cid);
      if ($cached && is_array($cached->data)) {
        return $cached->data;
      }
    }

    try {
      $response = $this->httpClient->get($uri, ['headers' => ['Accept' => 'application/json']]);
      $data = json_decode((string) $response->getBody(), TRUE);
      $keys = (is_array($data) && isset($data['keys']) && is_array($data['keys'])) ? $data['keys'] : [];
    }
    catch (\Throwable $e) {
      $this->logger->error('JWKS fetch failed from {uri}: {error}', ['uri' => $uri, 'error' => $e->getMessage()]);
      // Fall back to a stale cache if we have one - better than failing every
      // login on a transient IdP blip - but never invent keys.
      $stale = $this->cache->get($cid);
      if ($stale && is_array($stale->data)) {
        return $stale->data;
      }
      throw new \RuntimeException('Unable to retrieve JWKS for id_token verification.');
    }

    $this->cache->set($cid, $keys, time() + self::JWKS_TTL);
    return $keys;
  }

  /**
   * Resolves the JWKS URI from config, OIDC discovery, or Keycloak default.
   */
  protected function jwksUri(): string {
    $config = $this->configFactory->get('aabenforms_mitid.settings');
    $explicit = (string) ($config->get('jwks_uri') ?? '');
    if ($explicit !== '') {
      return $explicit;
    }

    $issuer = rtrim((string) ($config->get('issuer') ?? ''), '/');
    if ($issuer === '') {
      return '';
    }

    // Try OIDC discovery (cached) for the authoritative jwks_uri.
    $cid = 'aabenforms_mitid:jwks_uri:' . md5($issuer);
    $cached = $this->cache->get($cid);
    if ($cached && is_string($cached->data) && $cached->data !== '') {
      return $cached->data;
    }
    try {
      $response = $this->httpClient->get($issuer . '/.well-known/openid-configuration', ['headers' => ['Accept' => 'application/json']]);
      $doc = json_decode((string) $response->getBody(), TRUE);
      if (is_array($doc) && !empty($doc['jwks_uri'])) {
        $this->cache->set($cid, $doc['jwks_uri'], time() + self::JWKS_TTL);
        return $doc['jwks_uri'];
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('OIDC discovery failed for {iss}: {error}', ['iss' => $issuer, 'error' => $e->getMessage()]);
    }

    // Keycloak convention as a last resort.
    return $issuer . '/protocol/openid-connect/certs';
  }

  /**
   * Converts an RSA JWK (n, e) into a PEM SubjectPublicKeyInfo public key.
   *
   * Pure DER encoding (no crypto): builds RSAPublicKey ::= SEQUENCE { n, e }
   * wrapped in a SubjectPublicKeyInfo with the rsaEncryption OID, then PEM.
   */
  protected function jwkToPem(array $jwk): string {
    if (empty($jwk['n']) || empty($jwk['e'])) {
      throw new \RuntimeException('JWK is missing RSA modulus/exponent.');
    }
    $modulus = $this->derUnsignedInteger($this->b64UrlDecode($jwk['n']));
    $exponent = $this->derUnsignedInteger($this->b64UrlDecode($jwk['e']));
    $rsaPublicKey = $this->derSequence($modulus . $exponent);

    // AlgorithmIdentifier: rsaEncryption (1.2.840.113549.1.1.1) + NULL params.
    $rsaOid = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01";
    $algId = $this->derSequence($rsaOid . "\x05\x00");
    // subjectPublicKey BIT STRING wraps the DER RSAPublicKey (0x00 unused bits).
    $bitString = $this->derTlv(0x03, "\x00" . $rsaPublicKey);
    $spki = $this->derSequence($algId . $bitString);

    return "-----BEGIN PUBLIC KEY-----\n"
      . chunk_split(base64_encode($spki), 64, "\n")
      . "-----END PUBLIC KEY-----\n";
  }

  /**
   * DER INTEGER from a big-endian unsigned byte string (adds 0x00 sign pad).
   */
  protected function derUnsignedInteger(string $bytes): string {
    $bytes = ltrim($bytes, "\x00");
    if ($bytes === '') {
      $bytes = "\x00";
    }
    if (ord($bytes[0]) & 0x80) {
      $bytes = "\x00" . $bytes;
    }
    return $this->derTlv(0x02, $bytes);
  }

  /**
   * DER SEQUENCE wrapper.
   */
  protected function derSequence(string $content): string {
    return $this->derTlv(0x30, $content);
  }

  /**
   * Builds a DER tag-length-value with correct short/long form length.
   */
  protected function derTlv(int $tag, string $content): string {
    $len = strlen($content);
    if ($len < 0x80) {
      $length = chr($len);
    }
    else {
      $bytes = '';
      while ($len > 0) {
        $bytes = chr($len & 0xff) . $bytes;
        $len >>= 8;
      }
      $length = chr(0x80 | strlen($bytes)) . $bytes;
    }
    return chr($tag) . $length . $content;
  }

  /**
   * Base64url-decode (RFC 7515) with padding restoration.
   */
  protected function b64UrlDecode(string $data): string {
    $b64 = strtr($data, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad > 0) {
      $b64 .= str_repeat('=', 4 - $pad);
    }
    $decoded = base64_decode($b64, TRUE);
    if ($decoded === FALSE) {
      throw new \RuntimeException('Invalid base64url segment in id_token.');
    }
    return $decoded;
  }

}
