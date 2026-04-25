<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\TestMode;

use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use Drupal\aabenforms_digital_post\DigitalPost\Result;
use Drupal\aabenforms_digital_post\DigitalPost\Sender;
use Drupal\aabenforms_digital_post\Service\TransactionIdGenerator;
use Drupal\aabenforms_digital_post\TestMode\FakeSendDatabaseLogger;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the FakeSendDatabaseLogger.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\TestMode\FakeSendDatabaseLogger
 * @group aabenforms_digital_post
 */
class FakeSendDatabaseLoggerTest extends TestCase {

  /**
   * Mock database connection.
   */
  private Connection $database;

  /**
   * Mock transaction ID generator.
   */
  private TransactionIdGenerator $transactionIdGenerator;

  /**
   * Mock time service.
   */
  private TimeInterface $time;

  /**
   * Mock logger.
   */
  private LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->transactionIdGenerator = $this->createMock(TransactionIdGenerator::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->time->method('getRequestTime')->willReturn(1700000000);
  }

  /**
   * Creates the FakeSendDatabaseLogger instance.
   */
  private function createLogger(): FakeSendDatabaseLogger {
    return new FakeSendDatabaseLogger(
      $this->database,
      $this->transactionIdGenerator,
      $this->time,
      $this->logger,
    );
  }

  /**
   * Creates a test DigitalPost.
   */
  private function createDigitalPost(): DigitalPost {
    return new DigitalPost(
      recipient: Recipient::cpr('1234567890'),
      sender: new Sender('12345678', 'Test Kommune'),
      subject: 'Test Subject',
      body: 'Test body content',
    );
  }

  /**
   * Tests that send() inserts correct fields to database.
   *
   * @covers ::send
   */
  public function testSendInsertsCorrectFields(): void {
    $logger = $this->createLogger();
    $post = $this->createDigitalPost();

    $insertQuery = $this->createMock(Insert::class);

    $this->database
      ->expects($this->once())
      ->method('insert')
      ->with('aabenforms_digital_post_log')
      ->willReturn($insertQuery);

    $insertQuery
      ->expects($this->once())
      ->method('fields')
      ->with($this->callback(function ($fields) {
        return isset($fields['transaction_id'])
          && $fields['transaction_id'] === 'tx-test'
          && isset($fields['mode'])
          && $fields['mode'] === 'fake_db'
          && isset($fields['recipient_type'])
          && $fields['recipient_type'] === 'cpr'
          && isset($fields['recipient_identifier_hash'])
          && isset($fields['sender_cvr'])
          && $fields['sender_cvr'] === '12345678'
          && isset($fields['subject'])
          && $fields['subject'] === 'Test Subject'
          && isset($fields['status'])
          && $fields['status'] === Result::SUCCESS
          && $fields['reason_code'] === NULL
          && isset($fields['payload'])
          && isset($fields['response'])
          && isset($fields['created']);
      }))
      ->willReturn($insertQuery);

    $insertQuery->method('execute')->willReturn('1');

    $result = $logger->send($post, 'tx-test');

    $this->assertTrue($result->isSuccess());
  }

  /**
   * Tests that send() returns success Result.
   *
   * @covers ::send
   */
  public function testSendReturnsSuccessResult(): void {
    $logger = $this->createLogger();
    $post = $this->createDigitalPost();

    $insertQuery = $this->createMock(Insert::class);
    $this->database->method('insert')->willReturn($insertQuery);
    $insertQuery->method('fields')->willReturnSelf();
    $insertQuery->method('execute')->willReturn('1');

    $result = $logger->send($post, 'tx-success');

    $this->assertTrue($result->isSuccess());
    $this->assertEquals('tx-success', $result->transactionId);
    $this->assertStringContainsString('fake_db', $result->message);
    $this->assertStringContainsString('fake_db:synthetic-receipt', $result->rawResponse);
  }

  /**
   * Tests that send() returns failure Result on database exception.
   *
   * @covers ::send
   */
  public function testSendReturnsFailureResultOnDbException(): void {
    $logger = $this->createLogger();
    $post = $this->createDigitalPost();

    $this->database
      ->method('insert')
      ->willThrowException(new \Exception('Database connection failed'));

    $this->logger
      ->expects($this->once())
      ->method('error')
      ->with(
        'Digital Post fake-db send failed: @msg',
        $this->callback(fn($ctx) => $ctx['@msg'] === 'Database connection failed'),
      );

    $result = $logger->send($post, 'tx-fail');

    $this->assertFalse($result->isSuccess());
    $this->assertEquals('tx-fail', $result->transactionId);
    $this->assertEquals(Result::REASON_TRANSPORT, $result->reasonCode);
    $this->assertStringContainsString('Database connection failed', $result->message);
  }

  /**
   * Tests modeLabel() returns 'fake_db'.
   *
   * @covers ::modeLabel
   */
  public function testModeLabelReturnsFakeDb(): void {
    $logger = $this->createLogger();

    $this->assertEquals('fake_db', $logger->modeLabel());
  }

  /**
   * Tests that payload JSON includes expected structure.
   *
   * @covers ::send
   */
  public function testPayloadIncludesExpectedStructure(): void {
    $logger = $this->createLogger();
    $post = $this->createDigitalPost();

    $capturedFields = NULL;
    $insertQuery = $this->createMock(Insert::class);

    $this->database->method('insert')->willReturn($insertQuery);

    $insertQuery
      ->method('fields')
      ->willReturnCallback(function ($fields) use ($insertQuery, &$capturedFields) {
        $capturedFields = $fields;
        return $insertQuery;
      });

    $insertQuery->method('execute')->willReturn('1');

    $logger->send($post, 'tx-payload');

    $this->assertNotNull($capturedFields);
    $payload = json_decode($capturedFields['payload'], TRUE);

    $this->assertArrayHasKey('recipient', $payload);
    $this->assertArrayHasKey('sender', $payload);
    $this->assertArrayHasKey('subject', $payload);
    $this->assertArrayHasKey('body', $payload);
    $this->assertArrayHasKey('type', $payload);
    $this->assertArrayHasKey('attachments', $payload);
    $this->assertArrayHasKey('total_attachment_bytes', $payload);

    // Verify recipient has hash, not raw identifier.
    $this->assertArrayHasKey('identifier_hash', $payload['recipient']);
    $this->assertArrayNotHasKey('identifier', $payload['recipient']);
  }

}
