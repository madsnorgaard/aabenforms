<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post\Unit\DigitalPost;

use Drupal\aabenforms_digital_post\DigitalPost\Result;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Result DTO.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post\DigitalPost\Result
 * @group aabenforms_digital_post
 */
class ResultTest extends TestCase {

  /**
   * Tests the success() factory method.
   *
   * @covers ::success
   * @covers ::isSuccess
   */
  public function testSuccessFactory(): void {
    $result = Result::success(
      transactionId: 'tx-12345',
      message: 'Digital Post sent successfully',
      rawResponse: '<soap:Envelope>...</soap:Envelope>',
    );

    $this->assertEquals(Result::SUCCESS, $result->status);
    $this->assertEquals('tx-12345', $result->transactionId);
    $this->assertNull($result->reasonCode);
    $this->assertEquals('Digital Post sent successfully', $result->message);
    $this->assertEquals('<soap:Envelope>...</soap:Envelope>', $result->rawResponse);
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Tests success() with minimal parameters.
   *
   * @covers ::success
   */
  public function testSuccessFactoryMinimal(): void {
    $result = Result::success('tx-minimal');

    $this->assertEquals(Result::SUCCESS, $result->status);
    $this->assertEquals('tx-minimal', $result->transactionId);
    $this->assertEquals('', $result->message);
    $this->assertNull($result->rawResponse);
    $this->assertTrue($result->isSuccess());
  }

  /**
   * Tests the failure() factory method.
   *
   * @covers ::failure
   * @covers ::isSuccess
   */
  public function testFailureFactory(): void {
    $result = Result::failure(
      transactionId: 'tx-failed-123',
      reasonCode: Result::REASON_RECIPIENT_UNKNOWN,
      message: 'Recipient not found in Digital Post registry',
      rawResponse: '<soap:Fault>...</soap:Fault>',
    );

    $this->assertEquals(Result::FAILURE, $result->status);
    $this->assertEquals('tx-failed-123', $result->transactionId);
    $this->assertEquals(Result::REASON_RECIPIENT_UNKNOWN, $result->reasonCode);
    $this->assertEquals('Recipient not found in Digital Post registry', $result->message);
    $this->assertEquals('<soap:Fault>...</soap:Fault>', $result->rawResponse);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Tests failure() with various reason codes.
   *
   * @covers ::failure
   * @dataProvider reasonCodesProvider
   */
  public function testFailureWithReasonCodes(string $reasonCode): void {
    $result = Result::failure(
      transactionId: 'tx-test',
      reasonCode: $reasonCode,
      message: 'Test failure message',
    );

    $this->assertEquals($reasonCode, $result->reasonCode);
    $this->assertFalse($result->isSuccess());
  }

  /**
   * Data provider for reason codes.
   */
  public static function reasonCodesProvider(): array {
    return [
      'CERT_INVALID' => [Result::REASON_CERT_INVALID],
      'RECIPIENT_UNKNOWN' => [Result::REASON_RECIPIENT_UNKNOWN],
      'RECIPIENT_NOT_REACHABLE' => [Result::REASON_RECIPIENT_NOT_REACHABLE],
      'QUOTA' => [Result::REASON_QUOTA],
      'TRANSPORT' => [Result::REASON_TRANSPORT],
      'VALIDATION' => [Result::REASON_VALIDATION],
      'UNKNOWN' => [Result::REASON_UNKNOWN],
    ];
  }

  /**
   * Tests that auditContext() does not include rawResponse.
   *
   * @covers ::auditContext
   */
  public function testAuditContextDoesNotIncludeRawResponse(): void {
    $sensitiveResponse = '<soap:Envelope><cpr>1234567890</cpr></soap:Envelope>';

    $result = Result::success(
      transactionId: 'tx-audit',
      message: 'Sent',
      rawResponse: $sensitiveResponse,
    );

    $context = $result->auditContext();

    $this->assertArrayNotHasKey('rawResponse', $context);
    $this->assertArrayNotHasKey('raw_response', $context);

    // Verify expected keys are present.
    $this->assertArrayHasKey('status', $context);
    $this->assertArrayHasKey('transaction_id', $context);
    $this->assertArrayHasKey('reason_code', $context);
    $this->assertArrayHasKey('message', $context);

    // Verify values.
    $this->assertEquals(Result::SUCCESS, $context['status']);
    $this->assertEquals('tx-audit', $context['transaction_id']);
    $this->assertNull($context['reason_code']);
    $this->assertEquals('Sent', $context['message']);
  }

  /**
   * Tests auditContext() for failure result.
   *
   * @covers ::auditContext
   */
  public function testAuditContextForFailure(): void {
    $result = Result::failure(
      transactionId: 'tx-fail',
      reasonCode: Result::REASON_TRANSPORT,
      message: 'Connection timeout',
      rawResponse: '<error>...</error>',
    );

    $context = $result->auditContext();

    $this->assertEquals(Result::FAILURE, $context['status']);
    $this->assertEquals('tx-fail', $context['transaction_id']);
    $this->assertEquals(Result::REASON_TRANSPORT, $context['reason_code']);
    $this->assertEquals('Connection timeout', $context['message']);

    // Confirm rawResponse is not included.
    $this->assertStringNotContainsString('error', json_encode($context));
  }

  /**
   * Tests isSuccess() returns correct boolean values.
   *
   * @covers ::isSuccess
   */
  public function testIsSuccessReturnsBool(): void {
    $success = Result::success('tx-1');
    $failure = Result::failure('tx-2', Result::REASON_UNKNOWN, 'Error');

    $this->assertIsBool($success->isSuccess());
    $this->assertIsBool($failure->isSuccess());
    $this->assertTrue($success->isSuccess());
    $this->assertFalse($failure->isSuccess());
  }

}
