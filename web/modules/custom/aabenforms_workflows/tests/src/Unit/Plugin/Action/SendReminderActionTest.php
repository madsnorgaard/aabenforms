<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_workflows\Plugin\Action\SendReminderAction;
use Drupal\aabenforms_workflows\Service\SmsService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Psr\Log\LoggerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Tests for SendReminderAction plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\SendReminderAction
 */
class SendReminderActionTest extends UnitTestCase {

  /**
   * The action plugin instance.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\SendReminderAction
   */
  protected $action;

  /**
   * The SMS service.
   *
   * @var \Drupal\aabenforms_workflows\Service\SmsService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $smsService;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mailManager;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $queueFactory;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The webform submission.
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
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $configuration = [
      'reminder_type' => 'email',
      'delay_days' => '7',
      'event_date_field' => 'ceremony_date',
      'recipient_email_field' => 'email',
      'recipient_phone_field' => 'phone',
      'subject' => 'Påmindelse: Din aftale nærmer sig',
      'message' => 'Dette er en påmindelse om din aftale den [event_date].',
    ];

    $this->action = new SendReminderAction(
      $configuration,
      'aabenforms_send_reminder',
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

    $property = $reflection->getProperty('mailManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->mailManager);

    $property = $reflection->getProperty('queueFactory');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->queueFactory);
  }

  /**
   * @covers ::execute
   */
  public function testReminderScheduling(): void {
    $futureDate = date('Y-m-d', strtotime('+14 days'));

    $submissionData = [
      'ceremony_date' => $futureDate,
      'email' => 'test@example.com',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('800');

    $this->submission->expects($this->exactly(3))
      ->method('setElementData')
      ->withConsecutive(
        ['reminder_scheduled', TRUE],
        ['reminder_send_date', $this->anything()],
        ['reminder_type', 'email']
      );

    $this->submission->expects($this->once())
      ->method('save');

    $this->logger->expects($this->once())
      ->method('info')
      ->with($this->stringContains('Reminder scheduled'));

    $actionMock = $this->getMockBuilder(SendReminderAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_send_reminder',
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

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

    $property = $reflection->getProperty('mailManager');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->mailManager);

    $property = $reflection->getProperty('queueFactory');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->queueFactory);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testEmailReminder(): void {
    $pastDate = date('Y-m-d', strtotime('-1 day'));

    $submissionData = [
      'ceremony_date' => $pastDate,
      'email' => 'test@example.com',
      'phone' => '+4512345678',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('801');

    $this->submission->expects($this->exactly(2))
      ->method('setElementData')
      ->withConsecutive(
        ['reminder_sent', TRUE],
        ['reminder_sent_at', $this->anything()]
      );

    $this->submission->expects($this->once())
      ->method('save');

    $this->logger->expects($this->once())
      ->method('info')
      ->with($this->stringContains('Email reminder sent'));

    $actionMock = $this->getMockBuilder(SendReminderAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_send_reminder',
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

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

    $property = $reflection->getProperty('mailManager');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->mailManager);

    $property = $reflection->getProperty('queueFactory');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->queueFactory);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testSmsReminder(): void {
    $pastDate = date('Y-m-d', strtotime('-1 day'));

    $submissionData = [
      'ceremony_date' => $pastDate,
      'email' => 'test@example.com',
      'phone' => '+4512345678',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('802');

    $configuration = $this->action->getConfiguration();
    $configuration['reminder_type'] = 'sms';

    $actionWithSms = new SendReminderAction(
      $configuration,
      'aabenforms_send_reminder',
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
      ->willReturn([
        'status' => 'sent',
        'message_id' => 'SMS-REMINDER-123',
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(SendReminderAction::class)
      ->setConstructorArgs([
        $configuration,
        'aabenforms_send_reminder',
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

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

    $property = $reflection->getProperty('mailManager');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->mailManager);

    $property = $reflection->getProperty('queueFactory');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->queueFactory);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testDelayCalculation(): void {
    $eventDate = date('Y-m-d', strtotime('+10 days'));

    $submissionData = [
      'ceremony_date' => $eventDate,
      'email' => 'test@example.com',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('803');

    $configuration = $this->action->getConfiguration();
    $configuration['delay_days'] = '3';

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(SendReminderAction::class)
      ->setConstructorArgs([
        $configuration,
        'aabenforms_send_reminder',
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

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

    $property = $reflection->getProperty('mailManager');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->mailManager);

    $property = $reflection->getProperty('queueFactory');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->queueFactory);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testQueueIntegration(): void {
    $futureDate = date('Y-m-d', strtotime('+30 days'));

    $submissionData = [
      'ceremony_date' => $futureDate,
      'email' => 'queue@example.com',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('804');

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(SendReminderAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_send_reminder',
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

    $actionMock->method('getSubmission')->willReturn($this->submission);

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('smsService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->smsService);

    $property = $reflection->getProperty('mailManager');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->mailManager);

    $property = $reflection->getProperty('queueFactory');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->queueFactory);

    $actionMock->execute($this->submission);
  }

}
