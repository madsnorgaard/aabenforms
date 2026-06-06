<?php

namespace Drupal\Tests\aabenforms_core\Unit\Service;

use Drupal\aabenforms_core\Service\EncryptionService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
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
   * Mock encrypt service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $encryptService;

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

    $this->profileManager = $this->createMock(EncryptionProfileManagerInterface::class);
    $keyRepository = $this->createMock(KeyRepositoryInterface::class);
    $this->encryptService = $this->createMock(EncryptServiceInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->with('aabenforms_core')->willReturn($this->logger);

    $this->encryptionService = new EncryptionService(
      $this->profileManager,
      $keyRepository,
      $this->encryptService,
      $loggerFactory
    );
  }

  /**
   * Returns a dummy encryption profile.
   *
   * @return \Drupal\encrypt\EncryptionProfileInterface
   *   A mocked profile, only used as a pass-through argument.
   */
  protected function dummyProfile(): EncryptionProfileInterface {
    return $this->createMock(EncryptionProfileInterface::class);
  }

  /**
   * @covers ::encrypt
   */
  public function testEncryptWithDefaultProfile(): void {
    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')->with('aabenforms_aes256')
      ->willReturn($this->dummyProfile());
    $this->encryptService->method('encrypt')->willReturn('base64_encrypted_data');

    $this->assertEquals('base64_encrypted_data', $this->encryptionService->encrypt('sensitive data'));
  }

  /**
   * @covers ::encrypt
   */
  public function testEncryptWithCustomProfile(): void {
    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')->with('custom_aes_profile')
      ->willReturn($this->dummyProfile());
    $this->encryptService->method('encrypt')->willReturn('custom_encrypted');

    $this->assertEquals('custom_encrypted', $this->encryptionService->encrypt('0101001234', 'custom_aes_profile'));
  }

  /**
   * @covers ::encrypt
   */
  public function testEncryptWithProfileNotFound(): void {
    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')->with('nonexistent_profile')->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Encryption profile 'nonexistent_profile' not found");
    $this->encryptionService->encrypt('data', 'nonexistent_profile');
  }

  /**
   * @covers ::encrypt
   */
  public function testEncryptWithEncryptionFailure(): void {
    $this->profileManager->method('getEncryptionProfile')->willReturn($this->dummyProfile());
    $this->encryptService->method('encrypt')->willThrowException(new \Exception('provider error'));

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to encrypt data');
    $this->encryptionService->encrypt('data');
  }

  /**
   * @covers ::decrypt
   */
  public function testDecryptWithDefaultProfile(): void {
    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')->with('aabenforms_aes256')
      ->willReturn($this->dummyProfile());
    $this->encryptService->method('decrypt')->willReturn('sensitive data');

    $this->assertEquals('sensitive data', $this->encryptionService->decrypt('base64_encrypted_data'));
  }

  /**
   * @covers ::decrypt
   */
  public function testDecryptWithProfileNotFound(): void {
    $this->profileManager->expects($this->once())
      ->method('getEncryptionProfile')->with('nonexistent_profile')->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Encryption profile 'nonexistent_profile' not found");
    $this->encryptionService->decrypt('encrypted', 'nonexistent_profile');
  }

  /**
   * @covers ::decrypt
   */
  public function testDecryptWithDecryptionFailure(): void {
    $this->profileManager->method('getEncryptionProfile')->willReturn($this->dummyProfile());
    $this->encryptService->method('decrypt')->willThrowException(new \Exception('provider error'));

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to decrypt data');
    $this->encryptionService->decrypt('corrupted_data');
  }

  /**
   * @covers ::encrypt
   * @covers ::decrypt
   */
  public function testEncryptDecryptRoundTrip(): void {
    $this->profileManager->expects($this->exactly(2))
      ->method('getEncryptionProfile')->with('aabenforms_aes256')
      ->willReturn($this->dummyProfile());
    $this->encryptService->method('encrypt')->willReturn('mock_encrypted_value');
    $this->encryptService->method('decrypt')->willReturn('0101001234');

    $encrypted = $this->encryptionService->encrypt('0101001234');
    $this->assertEquals('mock_encrypted_value', $encrypted);
    $this->assertEquals('0101001234', $this->encryptionService->decrypt($encrypted));
  }

  /**
   * @covers ::encryptCpr
   */
  public function testEncryptCprWithValidCpr(): void {
    $this->profileManager->method('getEncryptionProfile')->willReturn($this->dummyProfile());
    $this->encryptService->method('encrypt')->willReturn('encrypted_cpr');

    $this->assertEquals('encrypted_cpr', $this->encryptionService->encryptCpr('0101001234'));
  }

  /**
   * @covers ::encryptCpr
   */
  public function testEncryptCprWithInvalidCprTooShort(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid CPR number format');
    $this->encryptionService->encryptCpr('010100123');
  }

  /**
   * @covers ::encryptCpr
   */
  public function testEncryptCprWithInvalidCprNonNumeric(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid CPR number format');
    $this->encryptionService->encryptCpr('010100-123');
  }

  /**
   * @covers ::decryptCpr
   */
  public function testDecryptCpr(): void {
    $this->profileManager->method('getEncryptionProfile')->willReturn($this->dummyProfile());
    $this->encryptService->method('decrypt')->willReturn('0101001234');

    $this->assertEquals('0101001234', $this->encryptionService->decryptCpr('encrypted_cpr'));
  }

}
