<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Certificate;

/**
 * Locates the certificate used to sign SF1601 SOAP requests.
 *
 * Implementations:
 * - FileCertificateLocator (default): path from config, passphrase from env var.
 * - KeyModuleCertificateLocator: uses drupal:key contrib.
 * - Os2WebKeyCertificateLocator: optional bridge, only when os2web_key is present.
 */
interface CertificateLocatorInterface {

  /**
   * Locate the certificate. Throws when misconfigured or unreadable.
   *
   * @throws \Drupal\aabenforms_digital_post\Exception\CertificateException
   */
  public function locate(): Certificate;

  /**
   * Whether the locator can report an expiry date.
   *
   * File-based locators can parse the cert; opaque sources may not.
   */
  public function supportsRenewal(): bool;

  /**
   * Expiry date of the current certificate, or NULL if not computable.
   */
  public function expiresAt(): ?\DateTimeImmutable;

}
