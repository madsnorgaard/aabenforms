<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\Certificate;

use Drupal\aabenforms_digital_post\Certificate\FileCertificateLocator;
use Drupal\aabenforms_digital_post\Exception\CertificateException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests the FileCertificateLocator.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\Certificate\FileCertificateLocator
 * @group aabenforms_digital_post
 */
class FileCertificateLocatorTest extends TestCase {

  /**
   * Mock config factory.
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Mock config.
   */
  private ImmutableConfig $config;

  /**
   * Temporary certificate file path.
   */
  private ?string $tempCertFile = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);

    $this->configFactory
      ->method('get')
      ->with('aabenforms_digital_post.settings')
      ->willReturn($this->config);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->tempCertFile !== NULL && file_exists($this->tempCertFile)) {
      unlink($this->tempCertFile);
    }
    parent::tearDown();
  }

  /**
   * Creates a temporary certificate file.
   */
  private function createTempCertFile(): string {
    $this->tempCertFile = tempnam(sys_get_temp_dir(), 'cert_test_');
    file_put_contents($this->tempCertFile, '-----BEGIN CERTIFICATE-----
MIIBkTCB+wIJAKHBfpEgfM...
-----END CERTIFICATE-----');
    return $this->tempCertFile;
  }

  /**
   * Tests that empty cert_path throws CertificateException.
   *
   * @covers ::locate
   */
  public function testEmptyCertPathThrows(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['cert_path', ''],
        ['cert_passphrase_state', ''],
      ]);

    $locator = new FileCertificateLocator($this->configFactory);

    $this->expectException(CertificateException::class);
    $this->expectExceptionMessage('aabenforms_digital_post.settings:cert_path is empty.');

    $locator->locate();
  }

  /**
   * Tests that non-existent file throws CertificateException.
   *
   * @covers ::locate
   */
  public function testNonExistentFileThrows(): void {
    $nonExistentPath = '/path/to/nonexistent/certificate.pem';

    $this->config->method('get')
      ->willReturnMap([
        ['cert_path', $nonExistentPath],
        ['cert_passphrase_state', ''],
      ]);

    $locator = new FileCertificateLocator($this->configFactory);

    $this->expectException(CertificateException::class);
    $this->expectExceptionMessage(sprintf('Certificate file "%s" is missing or unreadable.', $nonExistentPath));

    $locator->locate();
  }

  /**
   * Tests that valid file returns Certificate.
   *
   * @covers ::locate
   */
  public function testValidFileReturnsCertificate(): void {
    $certPath = $this->createTempCertFile();

    $this->config->method('get')
      ->willReturnMap([
        ['cert_path', $certPath],
        ['cert_passphrase_state', ''],
      ]);

    $locator = new FileCertificateLocator($this->configFactory);

    $certificate = $locator->locate();

    $this->assertEquals($certPath, $certificate->path);
    $this->assertNull($certificate->passphrase);
    $this->assertEquals('file', $certificate->sourceLabel);
  }

  /**
   * Tests that passphrase is read from environment variable.
   *
   * @covers ::locate
   */
  public function testPassphraseFromEnvVar(): void {
    $certPath = $this->createTempCertFile();
    $envVarName = 'TEST_CERT_PASSPHRASE_' . uniqid();
    $passphrase = 'my-secret-passphrase';

    // Set environment variable.
    putenv("{$envVarName}={$passphrase}");

    try {
      $this->config->method('get')
        ->willReturnMap([
          ['cert_path', $certPath],
          ['cert_passphrase_state', $envVarName],
        ]);

      $locator = new FileCertificateLocator($this->configFactory);

      $certificate = $locator->locate();

      $this->assertEquals($passphrase, $certificate->passphrase);
    }
    finally {
      // Clean up environment variable.
      putenv($envVarName);
    }
  }

  /**
   * Tests that missing env var results in NULL passphrase.
   *
   * @covers ::locate
   */
  public function testMissingEnvVarResultsInNullPassphrase(): void {
    $certPath = $this->createTempCertFile();
    $nonExistentEnvVar = 'DEFINITELY_NOT_SET_ENV_VAR_' . uniqid();

    // Make sure the env var is not set.
    putenv($nonExistentEnvVar);

    $this->config->method('get')
      ->willReturnMap([
        ['cert_path', $certPath],
        ['cert_passphrase_state', $nonExistentEnvVar],
      ]);

    $locator = new FileCertificateLocator($this->configFactory);

    $certificate = $locator->locate();

    $this->assertNull($certificate->passphrase);
  }

  /**
   * Tests supportsRenewal() returns TRUE.
   *
   * @covers ::supportsRenewal
   */
  public function testSupportsRenewalReturnsTrue(): void {
    $locator = new FileCertificateLocator($this->configFactory);

    $this->assertTrue($locator->supportsRenewal());
  }

  /**
   * Tests expiresAt() returns NULL when cert_path is empty.
   *
   * @covers ::expiresAt
   */
  public function testExpiresAtReturnsNullWhenCertPathEmpty(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['cert_path', ''],
        ['cert_passphrase_state', ''],
      ]);

    $locator = new FileCertificateLocator($this->configFactory);

    $this->assertNull($locator->expiresAt());
  }

  /**
   * Tests expiresAt() returns NULL when file doesn't exist.
   *
   * @covers ::expiresAt
   */
  public function testExpiresAtReturnsNullWhenFileNotExists(): void {
    $this->config->method('get')
      ->willReturnMap([
        ['cert_path', '/nonexistent/path.pem'],
        ['cert_passphrase_state', ''],
      ]);

    $locator = new FileCertificateLocator($this->configFactory);

    $this->assertNull($locator->expiresAt());
  }

}
