<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\TestMode;

use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Result;
use Drupal\aabenforms_digital_post\Service\Sf1601ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Posts a Digital Post to a WireMock endpoint for integration tests.
 *
 * This is NOT a real MeMo XML build. Session 1 ships a JSON body that
 * WireMock matches on URL + method and responds to with a canned receipt.
 * Session 2 replaces this with a real SOAP+MeMo payload via
 * itk-dev/serviceplatformen's SF1601 class. Keeping the JSON shape
 * simple lets us prove the end-to-end wiring tonight without getting
 * bogged down in MeMo XSD-compliance.
 *
 * WireMock URL defaults to http://wiremock:8080 which matches the DDEV
 * container alias. Set aabenforms_digital_post.settings.wiremock_url to
 * point elsewhere when running outside DDEV.
 */
final class WireMockSoapClient implements Sf1601ClientInterface {

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function send(DigitalPost $post, string $transactionId): Result {
    $base = (string) $this->configFactory
      ->get('aabenforms_digital_post.settings')
      ->get('wiremock_url');
    if ($base === '') {
      return Result::failure(
        transactionId: $transactionId,
        reasonCode: Result::REASON_VALIDATION,
        message: 'aabenforms_digital_post.settings:wiremock_url is empty.',
      );
    }
    $url = rtrim($base, '/') . '/service/KombiPostAfsend_1/kombi';
    $body = [
      'transaction_id' => $transactionId,
      'type' => $post->type,
      'recipient' => [
        'type' => $post->recipient->type,
        'identifier' => $post->recipient->identifier,
      ],
      'sender_cvr' => $post->sender->cvr,
      'sender_name' => $post->sender->name,
      'subject' => $post->subject,
      'body' => $post->body,
      'attachments' => array_map(static fn ($a) => [
        'filename' => $a->filename,
        'mime' => $a->mimeType,
        'size' => $a->sizeBytes,
      ], $post->attachments),
    ];
    try {
      $response = $this->httpClient->request('POST', $url, [
        'json' => $body,
        'headers' => [
          'X-Transaction-Id' => $transactionId,
          'X-SF1601-Type' => $post->type,
        ],
        'http_errors' => FALSE,
        'timeout' => 10,
      ]);
      $status = $response->getStatusCode();
      $raw = (string) $response->getBody();
      if ($status >= 200 && $status < 300) {
        $this->logger->info('Digital Post WireMock send: tx=@tx status=@s', ['@tx' => $transactionId, '@s' => $status]);
        return Result::success(
          transactionId: $transactionId,
          message: sprintf('wiremock: HTTP %d', $status),
          rawResponse: $raw,
        );
      }
      return Result::failure(
        transactionId: $transactionId,
        reasonCode: $status >= 500 ? Result::REASON_TRANSPORT : Result::REASON_VALIDATION,
        message: sprintf('wiremock returned HTTP %d', $status),
        rawResponse: $raw,
      );
    }
    catch (GuzzleException $e) {
      $this->logger->error('Digital Post WireMock transport error: @msg', ['@msg' => $e->getMessage()]);
      return Result::failure(
        transactionId: $transactionId,
        reasonCode: Result::REASON_TRANSPORT,
        message: 'wiremock transport error: ' . $e->getMessage(),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function modeLabel(): string {
    return 'wiremock';
  }

}
