<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Encrypts CPR values at rest and reveals them at the point of use.
 *
 * A single touchpoint so the rest of the system does not need to know the
 * ciphertext format. Protected values carry a prefix, which makes the
 * encrypt/reveal operations idempotent and lets reveal() pass through any
 * value that is not encrypted (for example a CPR taken from a MitID session
 * rather than a stored webform field).
 */
class CprAccess {

  /**
   * Marker prefixing an encrypted CPR so it can be recognised on read.
   */
  protected const PREFIX = 'AFENC1:';

  /**
   * The encryption service.
   *
   * @var \Drupal\aabenforms_core\Service\EncryptionService
   */
  protected EncryptionService $encryption;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a CprAccess helper.
   *
   * @param \Drupal\aabenforms_core\Service\EncryptionService $encryption
   *   The encryption service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(EncryptionService $encryption, LoggerChannelFactoryInterface $logger_factory) {
    $this->encryption = $encryption;
    $this->logger = $logger_factory->get('aabenforms_core');
  }

  /**
   * Whether a value is already an encrypted CPR.
   *
   * @param string $value
   *   The value to test.
   *
   * @return bool
   *   TRUE if the value carries the encryption prefix.
   */
  public function isProtected(string $value): bool {
    return str_starts_with($value, self::PREFIX);
  }

  /**
   * Returns an encrypted, storage-safe representation of a CPR.
   *
   * Idempotent: an already-encrypted or empty value is returned unchanged.
   *
   * @param string $cpr
   *   The plaintext CPR.
   *
   * @return string
   *   The prefixed ciphertext, or the original value if empty/already
   *   protected.
   *
   * @throws \RuntimeException
   *   When encryption is misconfigured (missing profile/key). Fails hard so a
   *   CPR is NEVER written in plaintext: it is safer to reject the submission
   *   than to store personal data unencrypted (databeskyttelsesloven). The
   *   sole caller (the webform-submission presave hook) lets this abort the
   *   save; the error message carries no CPR.
   */
  public function protect(string $cpr): string {
    if ($cpr === '' || $this->isProtected($cpr)) {
      return $cpr;
    }
    try {
      return self::PREFIX . base64_encode($this->encryption->encrypt($cpr));
    }
    catch (\Throwable $e) {
      $this->logger->error('CPR encryption failed; refusing to store plaintext: {error}', ['error' => $e->getMessage()]);
      throw new \RuntimeException('CPR-kryptering er ikke konfigureret; indsendelsen blev afvist for at undgå at gemme CPR i klartekst.', 0, $e);
    }
  }

  /**
   * Returns the plaintext CPR for a value, decrypting if necessary.
   *
   * A value without the encryption prefix is returned unchanged, so callers
   * can pass session-sourced or already-plaintext CPRs through safely.
   *
   * @param string $value
   *   The stored value.
   *
   * @return string
   *   The plaintext CPR, or '' if decryption fails.
   */
  public function reveal(string $value): string {
    if (!$this->isProtected($value)) {
      return $value;
    }
    try {
      return $this->encryption->decrypt(base64_decode(substr($value, strlen(self::PREFIX))));
    }
    catch (\Throwable $e) {
      $this->logger->error('CPR decryption failed: {error}', ['error' => $e->getMessage()]);
      return '';
    }
  }

}
