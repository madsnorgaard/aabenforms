<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\Service;

use Drupal\aabenforms_digital_post\Audit\AuditEmitterInterface;
use Drupal\aabenforms_digital_post\DigitalPost\Attachment;
use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use Drupal\aabenforms_digital_post\DigitalPost\Result;
use Drupal\aabenforms_digital_post\DigitalPost\Sender;
use Drupal\aabenforms_digital_post\Service\DigitalPostSender;
use Drupal\aabenforms_digital_post\Service\Sf1601ClientInterface;
use Drupal\aabenforms_digital_post\Service\TransactionIdGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the DigitalPostSender service.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\Service\DigitalPostSender
 * @group aabenforms_digital_post
 */
class DigitalPostSenderTest extends TestCase {

  /**
   * Mock SF1601 client.
   */
  private Sf1601ClientInterface $client;

  /**
   * Mock transaction ID generator.
   */
  private TransactionIdGenerator $transactionIdGenerator;

  /**
   * Mock audit emitter.
   */
  private AuditEmitterInterface $audit;

  /**
   * Mock config factory.
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Mock logger.
   */
  private LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->client = $this->createMock(Sf1601ClientInterface::class);
    $this->transactionIdGenerator = $this->createMock(TransactionIdGenerator::class);
    $this->audit = $this->createMock(AuditEmitterInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->client->method('modeLabel')->willReturn('test_mode');
  }

  /**
   * Creates the sender service with mocked dependencies.
   */
  private function createSender(): DigitalPostSender {
    return new DigitalPostSender(
      $this->client,
      $this->transactionIdGenerator,
      $this->audit,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Creates a test DigitalPost.
   */
  private function createDigitalPost(array $meta = []): DigitalPost {
    return new DigitalPost(
      recipient: Recipient::cpr('1234567890'),
      sender: new Sender('12345678', 'Test Kommune'),
      subject: 'Test Subject',
      body: 'Test body content',
      meta: $meta,
    );
  }

  /**
   * Tests that audit emits 'digital_post_sent' event on success.
   *
   * @covers ::send
   */
  public function testAuditEmitsSuccessEventType(): void {
    $sender = $this->createSender();
    $post = $this->createDigitalPost(['transaction_id' => 'tx-123']);

    $this->client
      ->expects($this->once())
      ->method('send')
      ->willReturn(Result::success('tx-123', 'Sent successfully'));

    $this->audit
      ->expects($this->once())
      ->method('emit')
      ->with(
        $this->equalTo('digital_post_sent'),
        $this->anything(),
        $this->anything(),
        $this->equalTo('success'),
        $this->anything(),
      );

    $result = $sender->send($post);

    $this->assertTrue($result->isSuccess());
  }

  /**
   * Tests that audit emits 'digital_post_failed' event on failure.
   *
   * @covers ::send
   */
  public function testAuditEmitsFailureEventType(): void {
    $sender = $this->createSender();
    $post = $this->createDigitalPost(['transaction_id' => 'tx-456']);

    $this->client
      ->expects($this->once())
      ->method('send')
      ->willReturn(Result::failure('tx-456', Result::REASON_TRANSPORT, 'Connection failed'));

    $this->audit
      ->expects($this->once())
      ->method('emit')
      ->with(
        $this->equalTo('digital_post_failed'),
        $this->anything(),
        $this->anything(),
        $this->equalTo('failure'),
        $this->anything(),
      );

    $result = $sender->send($post);

    $this->assertFalse($result->isSuccess());
  }

  /**
   * Tests that transactionId falls back to generator when missing from meta.
   *
   * @covers ::send
   */
  public function testTransactionIdFallbackToGenerator(): void {
    $sender = $this->createSender();
    $post = $this->createDigitalPost(); // No transaction_id in meta

    $this->transactionIdGenerator
      ->expects($this->once())
      ->method('generate')
      ->willReturn('generated-tx-id');

    $this->client
      ->expects($this->once())
      ->method('send')
      ->with($post, 'generated-tx-id')
      ->willReturn(Result::success('generated-tx-id'));

    $sender->send($post);
  }

  /**
   * Tests that transactionId from meta is used when provided.
   *
   * @covers ::send
   */
  public function testTransactionIdFromMetaIsUsed(): void {
    $sender = $this->createSender();
    $post = $this->createDigitalPost(['transaction_id' => 'meta-tx-id']);

    $this->transactionIdGenerator
      ->expects($this->never())
      ->method('generate');

    $this->client
      ->expects($this->once())
      ->method('send')
      ->with($post, 'meta-tx-id')
      ->willReturn(Result::success('meta-tx-id'));

    $sender->send($post);
  }

  /**
   * Tests that empty string transaction_id falls back to generator.
   *
   * @covers ::send
   */
  public function testEmptyStringTransactionIdFallbackToGenerator(): void {
    $sender = $this->createSender();
    $post = $this->createDigitalPost(['transaction_id' => '']);

    $this->transactionIdGenerator
      ->expects($this->once())
      ->method('generate')
      ->willReturn('fallback-tx-id');

    $this->client
      ->expects($this->once())
      ->method('send')
      ->with($post, 'fallback-tx-id')
      ->willReturn(Result::success('fallback-tx-id'));

    $sender->send($post);
  }

  /**
   * Tests that non-string transaction_id falls back to generator.
   *
   * @covers ::send
   */
  public function testNonStringTransactionIdFallbackToGenerator(): void {
    $sender = $this->createSender();
    // Integer in meta instead of string
    $post = $this->createDigitalPost(['transaction_id' => 12345]);

    $this->transactionIdGenerator
      ->expects($this->once())
      ->method('generate')
      ->willReturn('generated-for-int');

    $this->client
      ->expects($this->once())
      ->method('send')
      ->with($post, 'generated-for-int')
      ->willReturn(Result::success('generated-for-int'));

    $sender->send($post);
  }

  /**
   * Tests that the result is returned from the send operation.
   *
   * @covers ::send
   */
  public function testSendReturnsResult(): void {
    $sender = $this->createSender();
    $post = $this->createDigitalPost(['transaction_id' => 'tx-return']);

    $expectedResult = Result::success('tx-return', 'Success message');

    $this->client
      ->method('send')
      ->willReturn($expectedResult);

    $result = $sender->send($post);

    $this->assertSame($expectedResult, $result);
  }

  /**
   * Tests testMode() returns the client's mode label.
   *
   * @covers ::testMode
   */
  public function testTestModeReturnsClientModeLabel(): void {
    $sender = $this->createSender();

    $this->assertEquals('test_mode', $sender->testMode());
  }

  /**
   * Tests that audit context includes expected fields.
   *
   * @covers ::send
   */
  public function testAuditContextIncludesExpectedFields(): void {
    $sender = $this->createSender();
    $post = $this->createDigitalPost(['transaction_id' => 'tx-context']);

    $this->client
      ->method('send')
      ->willReturn(Result::success('tx-context'));

    $this->audit
      ->expects($this->once())
      ->method('emit')
      ->with(
        $this->anything(),
        $this->anything(),
        $this->anything(),
        $this->anything(),
        $this->callback(function ($context) {
          return isset($context['subject'])
            && isset($context['recipient_type'])
            && isset($context['sender_cvr'])
            && isset($context['attachment_count'])
            && isset($context['mode']);
        }),
      );

    $sender->send($post);
  }

}
