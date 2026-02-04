<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_workflows\Plugin\Action\SendSmsAction;
use Drupal\aabenforms_workflows\Service\SmsService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Psr\Log\LoggerInterface;
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
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $submission;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->smsService = $this->createMock(SmsService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $configuration = [
      'phone_field' => 'phone',
      'message_template' => 'Din ansøgning er modtaget. Sagsnummer: [submission:id]',
      'sender_name' => 'ÅbenForms',
      'store_message_id_in' => 'sms_message_id',
    ];

    $this->action = new SendSmsAction(
      $configuration,
      'aabenforms_send_sms',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );

    $reflection = new \ReflectionClass($this->action);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->smsService);
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

    $this->submission->expects($this->once())
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

    $this->submission->expects($this->exactly(3))
      ->method('setElementData')
      ->withConsecutive(
        ['sms_message_id', 'SMS-123-456'],
        ['sms_status', 'sent'],
        ['sms_segments', 1]
      );

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(SendSmsAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_send_sms',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->expects($this->once())
      ->method('getSubmission')
      ->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

    $actionMock->execute($this->submission);
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

    $this->submission->expects($this->once())
      ->method('id')
      ->willReturn('201');

    $this->smsService->expects($this->never())
      ->method('sendSms');

    $this->logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Phone field'));

    $actionMock = $this->getMockBuilder(SendSmsAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_send_sms',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->expects($this->once())
      ->method('getSubmission')
      ->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

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

    $this->submission->expects($this->once())
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

    $configuration = $this->action->getConfiguration();
    $configuration['message_template'] = 'Application [submission:id] for [submission:license_plate] received. Form: [webform:title]';

    $actionWithTemplate = new SendSmsAction(
      $configuration,
      'aabenforms_send_sms',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );

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

    $actionMock = $this->getMockBuilder(SendSmsAction::class)
      ->setConstructorArgs([
        $configuration,
        'aabenforms_send_sms',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->expects($this->once())
      ->method('getSubmission')
      ->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

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

    foreach ($phones as $index => $phone) {
      $submission = $this->createMock(WebformSubmissionInterface::class);
      $submission->method('getData')->willReturn(['phone' => $phone]);
      $submission->method('id')->willReturn((string) (300 + $index));
      $submission->method('getCreatedTime')->willReturn(time());
      $submission->method('getWebform')->willReturn($webform);

      $this->smsService->expects($this->at($index))
        ->method('sendSms')
        ->with($phone, $this->anything(), $this->anything())
        ->willReturn([
          'status' => 'sent',
          'message_id' => 'SMS-' . (300 + $index),
          'segments' => 1,
        ]);

      $submission->expects($this->atLeastOnce())
        ->method('setElementData');

      $submission->expects($this->once())
        ->method('save');

      $actionMock = $this->getMockBuilder(SendSmsAction::class)
        ->setConstructorArgs([
          $this->action->getConfiguration(),
          'aabenforms_send_sms',
          ['provider' => 'aabenforms_workflows'],
          $this->createMock(EntityTypeManagerInterface::class),
          $this->createMock(TokenInterface::class),
          $this->createMock(AccountInterface::class),
          $this->createMock(TimeInterface::class),
          $this->createMock(EcaState::class),
          $this->logger,
        ])
        ->onlyMethods(['getSubmission'])
        ->getMock();

      $actionMock->expects($this->once())
        ->method('getSubmission')
        ->willReturn($submission);

      $reflection = new \ReflectionClass($actionMock);
      $property = $reflection->getProperty('smsService');
      $property->setAccessible(TRUE);
      $property->setValue($actionMock, $this->smsService);

      $actionMock->execute($submission);
    }

    // All three should be processed.
    $this->assertEquals(3, count($phones));
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

    $this->submission->expects($this->once())
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

    $actionMock = $this->getMockBuilder(SendSmsAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_send_sms',
        ['provider' => 'aabenforms_workflows'],
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(TokenInterface::class),
        $this->createMock(AccountInterface::class),
        $this->createMock(TimeInterface::class),
        $this->createMock(EcaState::class),
        $this->logger,
      ])
      ->onlyMethods(['getSubmission'])
      ->getMock();

    $actionMock->expects($this->once())
      ->method('getSubmission')
      ->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

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
  public function phoneNumberProvider(): array {
    return [
      ['12345678', '+4512345678'],
      ['+4512345678', '+4512345678'],
      ['0012345678', '+4512345678'],
      ['+45 12 34 56 78', '+4512345678'],
      ['12-34-56-78', '+4512345678'],
    ];
  }

}
