<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\DigitalPost;

/**
 * Immutable Digital Post DTO accepted by DigitalPostSender::send().
 *
 * This DTO carries NO reference to webforms, submissions, or any Drupal
 * entity. Callers (webform handlers, ECA actions, custom code) are
 * responsible for mapping their own data model into this shape.
 */
final class DigitalPost {

  public const TYPE_AUTOMATISK_VALG = 'Automatisk Valg';
  public const TYPE_DIGITAL_POST = 'Digital Post';
  public const TYPE_FYSISK_POST = 'Fysisk Post';
  public const TYPE_NEM_SMS = 'NemSMS';

  public const VALID_TYPES = [
    self::TYPE_AUTOMATISK_VALG,
    self::TYPE_DIGITAL_POST,
    self::TYPE_FYSISK_POST,
    self::TYPE_NEM_SMS,
  ];

  /**
   * Constructs the DigitalPost DTO.
   *
   * @param \Drupal\aabenforms_digital_post\DigitalPost\Recipient $recipient
   *   The Digital Post recipient (CPR or CVR).
   * @param \Drupal\aabenforms_digital_post\DigitalPost\Sender $sender
   *   The sender identity.
   * @param string $subject
   *   Message subject (1..255 chars).
   * @param string $body
   *   Message body. HTML allowed for Digital Post; stripped for NemSMS
   *   by the transport.
   * @param list<\Drupal\aabenforms_digital_post\DigitalPost\Attachment> $attachments
   *   Optional attachment list.
   * @param string $type
   *   One of the TYPE_* constants.
   * @param array<string, string|int|bool> $meta
   *   Additional hints:
   *   - transaction_id (optional; generated if absent).
   *   - memo_version (1.1 or 1.2; defaults to 1.2).
   *   - action_code (MeMo activity code; defaults to INFORMATION).
   */
  public function __construct(
    public readonly Recipient $recipient,
    public readonly Sender $sender,
    public readonly string $subject,
    public readonly string $body,
    public readonly array $attachments = [],
    public readonly string $type = self::TYPE_DIGITAL_POST,
    public readonly array $meta = [],
  ) {
    if (trim($subject) === '') {
      throw new \InvalidArgumentException('DigitalPost subject cannot be empty.');
    }
    if (strlen($subject) > 255) {
      throw new \InvalidArgumentException(sprintf('DigitalPost subject is %d chars, max 255.', strlen($subject)));
    }
    if (trim($body) === '') {
      throw new \InvalidArgumentException('DigitalPost body cannot be empty.');
    }
    if (!in_array($type, self::VALID_TYPES, TRUE)) {
      throw new \InvalidArgumentException(sprintf(
        'DigitalPost type "%s" is invalid. Must be one of: %s',
        $type, implode(', ', self::VALID_TYPES)
      ));
    }
    foreach ($attachments as $i => $a) {
      if (!$a instanceof Attachment) {
        throw new \InvalidArgumentException(sprintf('attachments[%d] must be an Attachment.', $i));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function totalAttachmentBytes(): int {
    $total = 0;
    foreach ($this->attachments as $a) {
      $total += $a->sizeBytes;
    }
    return $total;
  }

}
