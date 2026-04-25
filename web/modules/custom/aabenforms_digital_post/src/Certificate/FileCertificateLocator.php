<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Certificate;

use Drupal\aabenforms_digital_post\Exception\CertificateException;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * File-based certificate locator. Reads cert_path from module config and
 * resolves the passphrase from an environment variable named by
 * cert_passphrase_state.
 *
 * Rationale for env-var passphrase instead of config: passphrases are
 * environment-specific secrets and should never sit in version-controlled
 * config. A future KeyModuleCertificateLocator can replace this with the
 * drupal:key contrib's encrypted storage for sites that want it.
 */
final class FileCertificateLocator implements CertificateLocatorInterface {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   *
   */
  public function locate(): Certificate {
    $config = $this->configFactory->get('aabenforms_digital_post.settings');
    $path = (string) $config->get('cert_path');
    if ($path === '') {
      throw new CertificateException('aabenforms_digital_post.settings:cert_path is empty.');
    }
    if (!is_file($path) || !is_readable($path)) {
      throw new CertificateException(sprintf('Certificate file "%s" is missing or unreadable.', $path));
    }
    $envVar = (string) $config->get('cert_passphrase_state');
    $passphrase = NULL;
    if ($envVar !== '') {
      $value = getenv($envVar);
      $passphrase = $value === FALSE ? NULL : $value;
    }
    return new Certificate(
      path: $path,
      passphrase: $passphrase,
      sourceLabel: 'file',
    );
  }

  /**
   *
   */
  public function supportsRenewal(): bool {
    return TRUE;
  }

  /**
   *
   */
  public function expiresAt(): ?\DateTimeImmutable {
    try {
      $cert = $this->locate();
    }
    catch (CertificateException) {
      return NULL;
    }
    $raw = @file_get_contents($cert->path);
    if ($raw === FALSE) {
      return NULL;
    }
    // Works for PEM. For PKCS#12 (.p12, .pfx) we'd need openssl_pkcs12_read
    // which requires the passphrase; defer that path to a later iteration.
    $parsed = @openssl_x509_parse($raw);
    if ($parsed === FALSE || !isset($parsed['validTo_time_t'])) {
      return NULL;
    }
    return (new \DateTimeImmutable())->setTimestamp((int) $parsed['validTo_time_t']);
  }

}
