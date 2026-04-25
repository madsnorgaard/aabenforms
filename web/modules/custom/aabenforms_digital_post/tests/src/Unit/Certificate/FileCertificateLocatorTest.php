<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\Certificate;

use Drupal\aabenforms_digital_post\Certificate\FileCertificateLocator;
use Drupal\aabenforms_digital_post\Exception\CertificateException;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests FileCertificateLocator config-driven cert resolution.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\Certificate\FileCertificateLocator
 * @group aabenforms_digital_post
 */
class FileCertificateLocatorTest extends UnitTestCase {

  /**
   * Builds a locator with a Config mock returning the given key/value map.
   */
  protected function locatorWithConfig(array $configValues): FileCertificateLocator {
    $config = $this->createMock(Config::class);
    $config->method('get')->willReturnCallback(
      static fn (string $key) => $configValues[$key] ?? NULL
    );
    $factory = $this->createMock(ConfigFactoryInterface::class);
    $factory->method('get')
      ->with('aabenforms_digital_post.settings')
      ->willReturn($config);
    return new FileCertificateLocator($factory);
  }

  /**
   * Empty cert_path config throws CertificateException.
   */
  public function testEmptyCertPathThrows(): void {
    $locator = $this->locatorWithConfig(['cert_path' => '']);
    $this->expectException(CertificateException::class);
    $this->expectExceptionMessage('cert_path');
    $locator->locate();
  }

  /**
   * Missing cert file throws CertificateException with the path in the message.
   */
  public function testMissingCertFileThrows(): void {
    $missing = '/nonexistent/' . uniqid('cert-', TRUE) . '.pem';
    $locator = $this->locatorWithConfig(['cert_path' => $missing]);
    $this->expectException(CertificateException::class);
    $this->expectExceptionMessage($missing);
    $locator->locate();
  }

  /**
   * Valid cert path + env-var passphrase resolve into a Certificate.
   */
  public function testValidPathReturnsCertificateWithPassphrase(): void {
    $tmp = tempnam(sys_get_temp_dir(), 'af-cert-');
    file_put_contents($tmp, "-----BEGIN CERTIFICATE-----\nfake\n-----END CERTIFICATE-----\n");
    $envVar = 'AF_TEST_CERT_PASS_' . bin2hex(random_bytes(4));
    putenv($envVar . '=hunter2');
    try {
      $locator = $this->locatorWithConfig([
        'cert_path' => $tmp,
        'cert_passphrase_state' => $envVar,
      ]);
      $cert = $locator->locate();
      $this->assertSame($tmp, $cert->path);
      $this->assertSame('hunter2', $cert->passphrase);
      $this->assertSame('file', $cert->sourceLabel);
    }
    finally {
      putenv($envVar);
      @unlink($tmp);
    }
  }

  /**
   * Valid cert path with no passphrase env-var yields NULL passphrase.
   */
  public function testValidPathNoPassphraseEnv(): void {
    $tmp = tempnam(sys_get_temp_dir(), 'af-cert-');
    file_put_contents($tmp, "-----BEGIN CERTIFICATE-----\nfake\n-----END CERTIFICATE-----\n");
    try {
      $locator = $this->locatorWithConfig([
        'cert_path' => $tmp,
        'cert_passphrase_state' => '',
      ]);
      $cert = $locator->locate();
      $this->assertNull($cert->passphrase);
    }
    finally {
      @unlink($tmp);
    }
  }

  /**
   * Locator advertises supportsRenewal() as TRUE.
   */
  public function testSupportsRenewal(): void {
    $locator = $this->locatorWithConfig(['cert_path' => '']);
    $this->assertTrue($locator->supportsRenewal());
  }

}
