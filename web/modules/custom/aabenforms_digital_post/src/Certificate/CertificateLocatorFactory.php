<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Certificate;

use Drupal\aabenforms_digital_post\Exception\CertificateException;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Selects the concrete CertificateLocator based on config.cert_source.
 *
 * Today only "file" is shipped. Future session: register "key" (drupal:key
 * contrib) and "os2web_key" (via optional bridge submodule) services and
 * pick them here by string match.
 */
final class CertificateLocatorFactory {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly FileCertificateLocator $fileLocator,
  ) {
  }

  /**
   *
   */
  public function create(): CertificateLocatorInterface {
    $source = (string) $this->configFactory
      ->get('aabenforms_digital_post.settings')
      ->get('cert_source');
    return match ($source) {
      'file', '' => $this->fileLocator,
      default => throw new CertificateException(sprintf(
        'cert_source "%s" is not supported in this build. Available: file.',
        $source
      )),
    };
  }

}
