<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\SendSmsAction;
use Drupal\aabenforms_workflows\Service\SmsService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformInterface;

/**
 * Tests for SendSmsAction plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\SendSmsAction
 */
class SendSmsActionTest extends UnitTestCase {

  /**
   * The action plugin.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\SendSmsAction
   */
  protected $action;

  /**
   * Mock SMS service.
   *
   * @var \Drupal\aabenforms_workflows\Service\SmsService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $smsService;

  /**
   * Mock logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $submission;

  /**
   * Configuration array shared across the suite.
   *
   * @var array
   */
  protected array $configuration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->smsService = $this->createMock(SmsService::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $this->configuration = [
      'phone_field' => 'phone',
      'message_template' => 'Din ansøgning er modtaget. Sagsnummer: [submission:id]',
      'sender_name' => 'ÅbenForms',
      'store_message_id_in' => 'sms_message_id',
    ];

    $this->action = new SendSmsAction(
      $this->configuration,
      'aabenforms_send_sms',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountProxyInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );
    $this->action->setExecutionCollector($this->createMock(WorkflowExecutionCollector::class));

    $reflection = new \ReflectionClass($this->action);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->smsService);
  }

  /**
   * Builds a partial-mock of the action that returns the supplied submission.
   */
  protected function createActionMock(WebformSubmissionInterface $submission, ?array $configuration = NULL): SendSmsAction {
    $actionMock = $this->getMockBuilder(SendSmsAction::class)
      ->setConstructorArgs([
        $configuration ?? $this->configuration,
        'aabenforms_send_sms',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountProxyInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->setExecutionCollector($this->createMock(WorkflowExecutionCollector::class));

    $actionMock->expects($this->once())
      ->method('getSubmission')
      ->willReturn($submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

    return $actionMock;
  }

  /**
   * Tests successful SMS send.
   *
   * @covers ::execute
   */
  public function testSuccessfulSmsSend(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('label')->willReturn('Test Form');

    $submissionData = [
      'phone' => '+4512345678',
      'name' => 'Test User',
    ];

    $this->submission->expects($this->atLeastOnce())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('200');

    $this->submission->expects($this->once())
      ->method('getCreatedTime')
      ->willReturn(time());

    $this->submission->expects($this->once())
      ->method('getWebform')
      ->willReturn($webform);

    $smsResult = [
      'status' => 'sent',
      'message_id' => 'SMS-123-456',
      'segments' => 1,
    ];

    $this->smsService->expects($this->once())
      ->method('sendSms')
      ->with(
        '+4512345678',
        $this->stringContains('Sagsnummer: 200'),
        $this->callback(function ($options) {
          return $options['sender'] === 'ÅbenForms';
        })
      )
      ->willReturn($smsResult);

    $writes = [];
    $this->submission->expects($this->exactly(3))
      ->method('setElementData')
      ->willReturnCallback(function ($key, $value) use (&$writes) {
        $writes[$key] = $value;
      });

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);

    $this->assertSame('SMS-123-456', $writes['sms_message_id']);
    $this->assertSame('sent', $writes['sms_status']);
    $this->assertSame(1, $writes['sms_segments']);
  }

  /**
   * Tests invalid phone number handling.
   *
   * @covers ::execute
   */
  public function testInvalidPhoneNumber(): void {
    $submissionData = [];

    $this->submission->expects($this->once())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('201');

    $this->smsService->expects($this->never())
      ->method('sendSms');

    $this->logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Phone field'));

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);
  }

  /**
   * Tests token replacement in message.
   *
   * @covers ::execute
   * @covers ::processMessageTemplate
   */
  public function testTokenReplacement(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('label')->willReturn('Parking Permit Form');

    $submissionData = [
      'phone' => '12345678',
      'license_plate' => 'AB12345',
      'amount' => '100',
    ];

    $this->submission->expects($this->atLeastOnce())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('202');

    $this->submission->expects($this->once())
      ->method('getCreatedTime')
      ->willReturn(1640000000);

    $this->submission->expects($this->once())
      ->method('getWebform')
      ->willReturn($webform);

    $configuration = $this->configuration;
    $configuration['message_template'] = 'Application [submission:id] for [submission:license_plate] received. Form: [webform:title]';

    $this->smsService->expects($this->once())
      ->method('sendSms')
      ->with(
        '+4512345678',
        'Application 202 for AB12345 received. Form: Parking Permit Form',
        $this->anything()
      )
      ->willReturn([
        'status' => 'sent',
        'message_id' => 'SMS-202',
        'segments' => 1,
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->createActionMock($this->submission, $configuration);
    $actionMock->execute($this->submission);
  }

  /**
   * Tests bulk SMS sending.
   *
   * @covers ::execute
   */
  public function testBulkSms(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('label')->willReturn('Test Form');

    $phones = ['+4511111111', '+4522222222', '+4533333333'];
    $sentPhones = [];

    // Each iteration creates a fresh action mock + submission, so each
    // sendSms() call resolves against its own expectation. Replace the
    // PHPUnit-9-only $this->at() pattern with a willReturnCallback that
    // observes the actual phone passed and returns a per-call result.
    $this->smsService->expects($this->exactly(count($phones)))
      ->method('sendSms')
      ->willReturnCallback(function ($phone) use (&$sentPhones) {
        $sentPhones[] = $phone;
        $index = count($sentPhones) - 1;
        return [
          'status' => 'sent',
          'message_id' => 'SMS-' . (300 + $index),
          'segments' => 1,
        ];
      });

    foreach ($phones as $phone) {
      $submission = $this->createMock(WebformSubmissionInterface::class);
      $submission->method('getData')->willReturn(['phone' => $phone]);
      $submission->method('id')->willReturn((string) (300 + array_search($phone, $phones, TRUE)));
      $submission->method('getCreatedTime')->willReturn(time());
      $submission->method('getWebform')->willReturn($webform);

      $submission->expects($this->atLeastOnce())
        ->method('setElementData');

      $submission->expects($this->once())
        ->method('save');

      $actionMock = $this->createActionMock($submission);
      $actionMock->execute($submission);
    }

    // Confirm every phone was sent in order.
    $this->assertSame($phones, $sentPhones);
  }

  /**
   * Tests SMS service integration.
   *
   * @covers ::execute
   */
  public function testSmsServiceIntegration(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('label')->willReturn('Integration Test');

    $submissionData = [
      'phone' => '+4512345678',
    ];

    $this->submission->expects($this->atLeastOnce())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('400');

    $this->submission->expects($this->once())
      ->method('getCreatedTime')
      ->willReturn(time());

    $this->submission->expects($this->once())
      ->method('getWebform')
      ->willReturn($webform);

    // Test that phone normalization and options are passed correctly.
    $this->smsService->expects($this->once())
      ->method('sendSms')
      ->with(
        $this->matchesRegularExpression('/^\+45\d{8}$/'),
        $this->isType('string'),
        $this->callback(function ($options) {
          $this->assertArrayHasKey('sender', $options);
          $this->assertEquals('ÅbenForms', $options['sender']);
          return TRUE;
        })
      )
      ->willReturn([
        'status' => 'sent',
        'message_id' => 'SMS-INTEGRATION-TEST',
        'segments' => 1,
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);
  }

  /**
   * Tests phone number normalization.
   *
   * @covers ::normalizePhoneNumber
   * @dataProvider phoneNumberProvider
   */
  public function testPhoneNumberNormalization(string $input, string $expected): void {
    $reflection = new \ReflectionClass($this->action);
    $method = $reflection->getMethod('normalizePhoneNumber');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->action, $input);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for phone number normalization tests.
   */
  public static function phoneNumberProvider(): array {
    return [
      ['12345678', '+4512345678'],
      ['+4512345678', '+4512345678'],
      ['0012345678', '+4512345678'],
      ['+45 12 34 56 78', '+4512345678'],
      ['12-34-56-78', '+4512345678'],
    ];
  }

}
