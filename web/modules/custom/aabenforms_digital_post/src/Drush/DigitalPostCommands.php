<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Drush;

use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use Drupal\aabenforms_digital_post\DigitalPost\Sender;
use Drupal\aabenforms_digital_post\Service\DigitalPostSender;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for AabenForms Digital Post.
 *
 * Af:dp:send      smoke-test a send through the configured transport
 * af:dp:log:tail  stream the last N log rows
 * af:dp:status    show current test_mode + sender config.
 */
final class DigitalPostCommands extends DrushCommands {

  public function __construct(
    private readonly DigitalPostSender $sender,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly Connection $database,
  ) {
    parent::__construct();
  }

  /**
   * Send a Digital Post through the currently configured transport.
   *
   * @command aabenforms:digital-post:send
   * @aliases af:dp:send
   * @option to The recipient CPR (10 digits) or CVR (8 digits). Required.
   * @option to-type Either cpr or cvr. Default: cpr.
   * @option subject Subject line.
   * @option body Body (may contain HTML).
   * @option from-cvr Override sender CVR for this send. Defaults to config.
   * @usage drush af:dp:send --to=0101900000 --subject="Test" --body="Hello"
   *   Send via the configured transport (fake_db by default).
   */
  public function send(
    array $options = [
      'to' => NULL,
      'to-type' => 'cpr',
      'subject' => 'Test Digital Post',
      'body' => '<p>Test message from af:dp:send</p>',
      'from-cvr' => NULL,
    ],
  ): int {
    $to = $options['to'];
    if (!is_string($to) || $to === '') {
      $this->logger()->error('--to is required.');
      return self::EXIT_FAILURE;
    }
    $recipient = $options['to-type'] === 'cvr'
      ? Recipient::cvr($to)
      : Recipient::cpr($to);

    $fromCvr = $options['from-cvr'] !== NULL
      ? (string) $options['from-cvr']
      : (string) $this->configFactory->get('aabenforms_digital_post.settings')->get('sender_cvr');
    if ($fromCvr === '') {
      $fromCvr = '00000000';
      $this->logger()->warning('No sender_cvr configured; using 00000000 for fake_db smoke test.');
    }
    $sender = new Sender(
      cvr: $fromCvr,
      name: (string) $this->configFactory->get('aabenforms_digital_post.settings')->get('sender_name'),
    );

    $post = new DigitalPost(
      recipient: $recipient,
      sender: $sender,
      subject: (string) $options['subject'],
      body: (string) $options['body'],
    );

    $result = $this->sender->send($post);
    $this->output()->writeln(sprintf(
      '%s transactionId=%s mode=%s reason=%s message=%s',
      strtoupper($result->status),
      $result->transactionId,
      $this->sender->testMode(),
      $result->reasonCode ?? '-',
      $result->message,
    ));
    return $result->isSuccess() ? self::EXIT_SUCCESS : self::EXIT_FAILURE;
  }

  /**
   * Show the last N rows of the Digital Post log table.
   *
   * @command aabenforms:digital-post:log:tail
   * @aliases af:dp:log:tail
   * @option count Number of rows. Default 10.
   */
  public function logTail(array $options = ['count' => 10]): void {
    $rows = $this->database->select('aabenforms_digital_post_log', 'l')
      ->fields('l', [
        'id',
        'created',
        'mode',
        'status',
        'reason_code',
        'recipient_type',
        'sender_cvr',
        'subject',
        'transaction_id',
      ])
      ->orderBy('id', 'DESC')
      ->range(0, (int) $options['count'])
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
    foreach (array_reverse($rows) as $r) {
      $this->output()->writeln(sprintf(
        '%s  #%d  %s/%s  %s -> %s "%s"  tx=%s',
        date('Y-m-d H:i:s', (int) $r['created']),
        $r['id'],
        $r['mode'],
        $r['status'],
        $r['sender_cvr'],
        $r['recipient_type'],
        mb_substr((string) $r['subject'], 0, 40),
        $r['transaction_id'],
      ));
    }
  }

  /**
   * Report current Digital Post config (for quick inspection).
   *
   * @command aabenforms:digital-post:status
   * @aliases af:dp:status
   */
  public function status(): void {
    $config = $this->configFactory->get('aabenforms_digital_post.settings');
    $this->output()->writeln(sprintf('test_mode       : %s', $config->get('test_mode')));
    $this->output()->writeln(sprintf('sender_cvr      : %s', $config->get('sender_cvr') ?: '(unset)'));
    $this->output()->writeln(sprintf('sender_name     : %s', $config->get('sender_name') ?: '(unset)'));
    $this->output()->writeln(sprintf('cert_source     : %s', $config->get('cert_source')));
    $this->output()->writeln(sprintf('cert_path       : %s', $config->get('cert_path') ?: '(unset)'));
    $this->output()->writeln(sprintf('wiremock_url    : %s', $config->get('wiremock_url')));
    $count = (int) $this->database->select('aabenforms_digital_post_log')->countQuery()->execute()->fetchField();
    $this->output()->writeln(sprintf('log rows        : %d', $count));
  }

}
