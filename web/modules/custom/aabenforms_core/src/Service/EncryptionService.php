<?php

namespace Drupal\aabenforms_core\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\key\KeyRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for field-level AES-256 encryption of sensitive data.
 *
 * Provides GDPR-compliant encryption for:
 * - CPR numbers (Danish social security numbers)
 * - Personal data in workflow instances
 * - Sensitive form submissions
 *
 * Uses Drupal's Encrypt module with Real AES encryption provider.
 *
 * @see https://www.drupal.org/project/encrypt
 * @see https://www.drupal.org/project/real_aes
 */
class EncryptionService {

  /**
   * Default encryption profile ID.
   */
  protected const DEFAULT_PROFILE = 'aabenforms_aes256';

  /**
   * The encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected EncryptionProfileManagerInterface $profileManager;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected KeyRepositoryInterface $keyRepository;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs an EncryptionService.
   *
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $profile_manager
   *   The encryption profile manager.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EncryptionProfileManagerInterface $profile_manager,
    KeyRepositoryInterface $key_repository,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->profileManager = $profile_manager;
    $this->keyRepository = $key_repository;
    $this->logger = $logger_factory->get('aabenforms_core');
  }

  /**
   * Encrypts sensitive data.
   *
   * @param string $plaintext
   *   The data to encrypt.
   * @param string|null $profile_id
   *   Optional encryption profile ID (defaults to 'aabenforms_aes256').
   *
   * @return string
   *   The encrypted data (base64 encoded).
   *
   * @throws \RuntimeException
   *   If encryption fails.
   */
  public function encrypt(string $plaintext, ?string $profile_id = NULL): string {
    $profile_id = $profile_id ?? self::DEFAULT_PROFILE;

    try {
      $profile = $this->profileManager->getEncryptionProfile($profile_id);

      if (!$profile) {
        throw new \RuntimeException("Encryption profile '{$profile_id}' not found.");
      }

      $encrypted = $profile->encrypt($plaintext);

      $this->logger->debug('Encrypted data using profile: {profile}', [
        'profile' => $profile_id,
        'length' => strlen($plaintext),
      ]);

      return $encrypted;

    }
    catch (\Exception $e) {
      $this->logger->error('Encryption failed: {error}', [
        'error' => $e->getMessage(),
        'profile' => $profile_id,
      ]);

      throw new \RuntimeException('Failed to encrypt data: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Decrypts encrypted data.
   *
   * @param string $encrypted
   *   The encrypted data (base64 encoded).
   * @param string|null $profile_id
   *   Optional encryption profile ID (defaults to 'aabenforms_aes256').
   *
   * @return string
   *   The decrypted plaintext.
   *
   * @throws \RuntimeException
   *   If decryption fails.
   */
  public function decrypt(string $encrypted, ?string $profile_id = NULL): string {
    $profile_id = $profile_id ?? self::DEFAULT_PROFILE;

    try {
      $profile = $this->profileManager->getEncryptionProfile($profile_id);

      if (!$profile) {
        throw new \RuntimeException("Encryption profile '{$profile_id}' not found.");
      }

      $plaintext = $profile->decrypt($encrypted);

      $this->logger->debug('Decrypted data using profile: {profile}', [
        'profile' => $profile_id,
      ]);

      return $plaintext;

    }
    catch (\Exception $e) {
      $this->logger->error('Decryption failed: {error}', [
        'error' => $e->getMessage(),
        'profile' => $profile_id,
      ]);

      throw new \RuntimeException('Failed to decrypt data: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Encrypts a CPR number.
   *
   * Convenience method with validation for CPR-specific encryption.
   *
   * @param string $cpr
   *   The CPR number (10 digits).
   *
   * @return string
   *   The encrypted CPR number.
   *
   * @throws \InvalidArgumentException
   *   If CPR format is invalid.
   * @throws \RuntimeException
   *   If encryption fails.
   */
  public function encryptCpr(string $cpr): string {
    if (!preg_match('/^\d{10}$/', $cpr)) {
      throw new \InvalidArgumentException('Invalid CPR number format. Expected 10 digits.');
    }

    return $this->encrypt($cpr);
  }

  /**
   * Decrypts a CPR number.
   *
   * Convenience method for CPR-specific decryption.
   *
   * @param string $encrypted_cpr
   *   The encrypted CPR number.
   *
   * @return string
   *   The decrypted CPR number (10 digits).
   *
   * @throws \RuntimeException
   *   If decryption fails.
   */
  public function decryptCpr(string $encrypted_cpr): string {
    return $this->decrypt($encrypted_cpr);
  }

}
