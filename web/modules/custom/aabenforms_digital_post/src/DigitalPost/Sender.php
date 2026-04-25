<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\DigitalPost;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Immutable Digital Post sender (the authority sending the message).
 *
 * CVR is normalised to 8 digits; formatted input like "12 34 56 78" is
 * accepted via the constructor.
 */
final class Sender {

  /**
   * Sender CVR, normalised to 8 digits.
   */
  public readonly string $cvr;

  /**
   * Optional human-readable sender name shown to recipients.
   */
  public readonly string $name;

  /**
   * Optional fjernprint return address for fysisk-post fallback.
   */
  public readonly ?string $returnAddress;

  public function __construct(
    string $cvr,
    string $name = '',
    ?string $returnAddress = NULL,
  ) {
    $digits = preg_replace('/\D+/', '', $cvr) ?? '';
    if (strlen($digits) !== 8) {
      throw new \InvalidArgumentException(sprintf('Sender CVR must be 8 digits; got "%s".', $cvr));
    }
    $this->cvr = $digits;
    $this->name = $name;
    $this->returnAddress = $returnAddress;
  }

  /**
   * Build from the module's config. Throws if sender_cvr is unset.
   */
  public static function fromConfig(ConfigFactoryInterface $configFactory): self {
    $config = $configFactory->get('aabenforms_digital_post.settings');
    $cvr = (string) $config->get('sender_cvr');
    if ($cvr === '') {
      throw new \InvalidArgumentException('aabenforms_digital_post.settings:sender_cvr is empty. Configure it at /admin/config/aabenforms/digital-post.');
    }
    return new self(
      cvr: $cvr,
      name: (string) $config->get('sender_name'),
    );
  }

}
