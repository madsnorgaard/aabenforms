<?php

namespace Drupal\Tests\aabenforms_core\Unit\Service;

use Drupal\aabenforms_core\Service\EncryptionService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests EncryptionService.
 *
 * @coversDefaultClass \Drupal\aabenforms_core\Service\EncryptionService
 * @group aabenforms_core
 * @group encryption
 */
class EncryptionServiceTest extends UnitTestCase {

  /**
   * The encryption service.
   *
   * @var \Drupal\aabenforms_core\Service\EncryptionService
   */
  protected EncryptionService $encryptionService;

  /**
   * Mock encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $profileManager;

  /**
   * Mock key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyRepository;

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
    $this->profileManager = $this->createMock(EncryptionProfileManagerInterface::class);
    $this->keyRepository = $this->createMock(KeyRepositoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')
      ->with('aabenforms_core')
      ->willReturn($this->logger);

    // Create service.
    $this->encryptionService = new EncryptionService(
      $this->profileManager,
      $this->keyRepository,
      $loggerFactory
    );
  }

  /**
   * Helper to create simple mock profile with encrypt/decrypt.
   *
   * @param string $encryptReturn
   *   Value to return from encrypt().
   * @param string $decryptReturn
   *   Value to return from decrypt().
   * @param bool $throwOnEncrypt
   *   Whether to throw exception on encrypt().
   * @param bool $throwOnDecrypt
   *   Whether to throw exception on decrypt().
   *
   * @return object
   *   Mock profile object.
   */
  protected function createSimpleMockProfile(
    string $encryptReturn = '',
    string $decryptReturn = '',
    bool $throwOnEncrypt = FALSE,
    bool $throwOnDecrypt = FALSE
  ) {
    return new class($encryptReturn, $decryptReturn, $throwOnEncrypt, $throwOnDecrypt) {

      /**
       * Constructor.
       */
      public function __construct(
        public string $encryptReturn,
        public string $decryptReturn,
        public bool $throwOnEncrypt,
        public bool $throwOnDecrypt,
      ) {}

      /**
       * Mock encrypt method.
       */
      public function encrypt(string $text): string {
        if ($this->throwOnEncrypt) {
          throw new \Exception('Encryption provider error');
        }
        return $this->encryptReturn;
      }

      /**
       * Mock decrypt method.
       */
      public function decrypt(string $text): string {
        if ($this->throwOnDecrypt) {
          throw new \Exception('Decryption provider error');
        }
        return $this->decryptReturn;
      }

    };
  }

  /**
   * Tests encrypt with default profile.
   *
   * @covers ::encrypt
   */
  public function testEncryptWithDefaultProfile(): void {
    $plaintext = 'sensitive data';
    $encrypted = 'base64_encrypted_data';

    $profile = $this->createSimpleMockProfile($encrypted);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->with('aabenforms_aes256')
      ->willReturn($profile);

    $result = $this->encryptionService->encrypt($plaintext);

    $this->assertEquals($encrypted, $result);
  }

  /**
   * Tests encrypt with custom profile.
   *
   * @covers ::encrypt
   */
  public function testEncryptWithCustomProfile(): void {
    $plaintext = '0101001234';
    $encrypted = 'custom_encrypted';
    $customProfile = 'custom_aes_profile';

    $profile = $this->createSimpleMockProfile($encrypted);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->with($customProfile)
      ->willReturn($profile);

    $result = $this->encryptionService->encrypt($plaintext, $customProfile);

    $this->assertEquals($encrypted, $result);
  }

  /**
   * Tests encrypt with profile not found.
   *
   * @covers ::encrypt
   */
  public function testEncryptWithProfileNotFound(): void {
    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->with('nonexistent_profile')
      ->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Encryption profile 'nonexistent_profile' not found");

    $this->encryptionService->encrypt('data', 'nonexistent_profile');
  }

  /**
   * Tests encrypt with encryption failure.
   *
   * @covers ::encrypt
   */
  public function testEncryptWithEncryptionFailure(): void {
    $profile = $this->createSimpleMockProfile('', '', TRUE, FALSE);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->willReturn($profile);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to encrypt data');

    $this->encryptionService->encrypt('data');
  }

  /**
   * Tests decrypt with default profile.
   *
   * @covers ::decrypt
   */
  public function testDecryptWithDefaultProfile(): void {
    $encrypted = 'base64_encrypted_data';
    $plaintext = 'sensitive data';

    $profile = $this->createSimpleMockProfile('', $plaintext);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->with('aabenforms_aes256')
      ->willReturn($profile);

    $result = $this->encryptionService->decrypt($encrypted);

    $this->assertEquals($plaintext, $result);
  }

  /**
   * Tests decrypt with custom profile.
   *
   * @covers ::decrypt
   */
  public function testDecryptWithCustomProfile(): void {
    $encrypted = 'custom_encrypted';
    $plaintext = '0101001234';
    $customProfile = 'custom_aes_profile';

    $profile = $this->createSimpleMockProfile('', $plaintext);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->with($customProfile)
      ->willReturn($profile);

    $result = $this->encryptionService->decrypt($encrypted, $customProfile);

    $this->assertEquals($plaintext, $result);
  }

  /**
   * Tests decrypt with profile not found.
   *
   * @covers ::decrypt
   */
  public function testDecryptWithProfileNotFound(): void {
    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->with('nonexistent_profile')
      ->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Encryption profile 'nonexistent_profile' not found");

    $this->encryptionService->decrypt('encrypted', 'nonexistent_profile');
  }

  /**
   * Tests decrypt with decryption failure.
   *
   * @covers ::decrypt
   */
  public function testDecryptWithDecryptionFailure(): void {
    $profile = $this->createSimpleMockProfile('', '', FALSE, TRUE);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->willReturn($profile);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to decrypt data');

    $this->encryptionService->decrypt('corrupted_data');
  }

  /**
   * Tests encryption/decryption round-trip.
   *
   * @covers ::encrypt
   * @covers ::decrypt
   */
  public function testEncryptDecryptRoundTrip(): void {
    $plaintext = '0101001234';
    $encrypted = 'mock_encrypted_value';

    // Use same profile for both operations.
    $profile = $this->createSimpleMockProfile($encrypted, $plaintext);

    $this->profileManager->expects($this->exactly(2))
      ->method('getEncryptionProfile')
      ->with('aabenforms_aes256')
      ->willReturn($profile);

    // Encrypt.
    $encryptedResult = $this->encryptionService->encrypt($plaintext);
    $this->assertEquals($encrypted, $encryptedResult);

    // Decrypt.
    $decryptedResult = $this->encryptionService->decrypt($encryptedResult);
    $this->assertEquals($plaintext, $decryptedResult);
  }

  /**
   * Tests encryptCpr with valid CPR.
   *
   * @covers ::encryptCpr
   */
  public function testEncryptCprWithValidCpr(): void {
    $cpr = '0101001234';
    $encrypted = 'encrypted_cpr';

    $profile = $this->createSimpleMockProfile($encrypted);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->willReturn($profile);

    $result = $this->encryptionService->encryptCpr($cpr);

    $this->assertEquals($encrypted, $result);
  }

  /**
   * Tests encryptCpr with invalid CPR (too short).
   *
   * @covers ::encryptCpr
   */
  public function testEncryptCprWithInvalidCprTooShort(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid CPR number format');

    $this->encryptionService->encryptCpr('010100123');
  }

  /**
   * Tests encryptCpr with invalid CPR (too long).
   *
   * @covers ::encryptCpr
   */
  public function testEncryptCprWithInvalidCprTooLong(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid CPR number format');

    $this->encryptionService->encryptCpr('01010012345');
  }

  /**
   * Tests encryptCpr with invalid CPR (non-numeric).
   *
   * @covers ::encryptCpr
   */
  public function testEncryptCprWithInvalidCprNonNumeric(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid CPR number format');

    $this->encryptionService->encryptCpr('010100-123');
  }

  /**
   * Tests decryptCpr.
   *
   * @covers ::decryptCpr
   */
  public function testDecryptCpr(): void {
    $encrypted = 'encrypted_cpr';
    $cpr = '0101001234';

    $profile = $this->createSimpleMockProfile('', $cpr);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->willReturn($profile);

    $result = $this->encryptionService->decryptCpr($encrypted);

    $this->assertEquals($cpr, $result);
  }

  /**
   * Tests encrypt with empty string.
   *
   * @covers ::encrypt
   */
  public function testEncryptEmptyString(): void {
    $plaintext = '';
    $encrypted = 'encrypted_empty';

    $profile = $this->createSimpleMockProfile($encrypted);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->willReturn($profile);

    $result = $this->encryptionService->encrypt($plaintext);

    $this->assertEquals($encrypted, $result);
  }

  /**
   * Tests encrypt with long string.
   *
   * @covers ::encrypt
   */
  public function testEncryptLongString(): void {
    $plaintext = str_repeat('Lorem ipsum dolor sit amet, ', 100);
    $encrypted = 'encrypted_long_text';

    $profile = $this->createSimpleMockProfile($encrypted);

    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')
      ->willReturn($profile);

    $result = $this->encryptionService->encrypt($plaintext);

    $this->assertEquals($encrypted, $result);
  }

}
