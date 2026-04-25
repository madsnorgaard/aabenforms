<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\Service;

use Drupal\aabenforms_digital_post\Audit\AuditEmitterInterface;
use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Result;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * The public entry point of aabenforms_digital_post.
 *
 * Callers build a DigitalPost DTO and call ->send(). The orchestrator
 * generates a transaction id, hands the DTO to the configured transport,
 * and emits an audit event with the typed Result.
 *
 * This class intentionally knows nothing about MeMo XML, certificates,
 * or SOAP. Those concerns live below the Sf1601ClientInterface boundary.
 */
final class DigitalPostSender {

  public function __construct(
    private readonly Sf1601ClientInterface $client,
    private readonly TransactionIdGenerator $transactionIdGenerator,
    private readonly AuditEmitterInterface $audit,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function send(DigitalPost $post): Result {
    $transactionId = $post->meta['transaction_id'] ?? $this->transactionIdGenerator->generate();
    if (!is_string($transactionId) || $transactionId === '') {
      $transactionId = $this->transactionIdGenerator->generate();
    }
    $this->logger->debug('Digital Post send starting: tx=@tx mode=@m', [
      '@tx' => $transactionId,
      '@m' => $this->client->modeLabel(),
    ]);
    $result = $this->client->send($post, $transactionId);
    $this->audit->emit(
      eventType: $result->isSuccess() ? 'digital_post_sent' : 'digital_post_failed',
      identifier: $post->recipient->identifierHash(),
      message: sprintf(
        '%s via %s: %s',
        $result->isSuccess() ? 'Digital Post sent' : 'Digital Post failed',
        $this->client->modeLabel(),
        $result->message,
      ),
      status: $result->isSuccess() ? 'success' : 'failure',
      context: array_merge($result->auditContext(), [
        'subject' => $post->subject,
        'recipient_type' => $post->recipient->type,
        'sender_cvr' => $post->sender->cvr,
        'attachment_count' => count($post->attachments),
        'mode' => $this->client->modeLabel(),
      ]),
    );
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function testMode(): string {
    return $this->client->modeLabel();
  }

}
