<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\SendReminderAction;
use Drupal\aabenforms_workflows\Service\SmsService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Drupal\Core\Logger\LoggerChannelInterface;
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
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The webform submission.
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
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $this->configuration = [
      'reminder_type' => 'email',
      'delay_days' => '7',
      'event_date_field' => 'ceremony_date',
      'recipient_email_field' => 'email',
      'recipient_phone_field' => 'phone',
      'subject' => 'Påmindelse: Din aftale nærmer sig',
      'message' => 'Dette er en påmindelse om din aftale den [event_date].',
    ];

    $this->action = new SendReminderAction(
      $this->configuration,
      'aabenforms_send_reminder',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountProxyInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );
    $this->action->setExecutionCollector($this->createMock(WorkflowExecutionCollector::class));

    $this->injectServices($this->action);
  }

  /**
   * Injects the SMS service, mail manager and queue factory via reflection.
   */
  protected function injectServices(SendReminderAction $action): void {
    $reflection = new \ReflectionClass($action);

    $smsServiceProperty = $reflection->getProperty('smsService');
    $smsServiceProperty->setAccessible(TRUE);
    $smsServiceProperty->setValue($action, $this->smsService);

    $mailManagerProperty = $reflection->getProperty('mailManager');
    $mailManagerProperty->setAccessible(TRUE);
    $mailManagerProperty->setValue($action, $this->mailManager);

    $queueFactoryProperty = $reflection->getProperty('queueFactory');
    $queueFactoryProperty->setAccessible(TRUE);
    $queueFactoryProperty->setValue($action, $this->queueFactory);
  }

  /**
   * Builds a partial-mock of the action that returns the supplied submission.
   */
  protected function createActionMock(WebformSubmissionInterface $submission, ?array $configuration = NULL): SendReminderAction {
    $actionMock = $this->getMockBuilder(SendReminderAction::class)
      ->setConstructorArgs([
        $configuration ?? $this->configuration,
        'aabenforms_send_reminder',
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
    $actionMock->method('getSubmission')->willReturn($submission);
    $this->injectServices($actionMock);

    return $actionMock;
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

    $writes = [];
    $this->submission->expects($this->exactly(3))
      ->method('setElementData')
      ->willReturnCallback(function ($key, $value) use (&$writes) {
        $writes[$key] = $value;
      });

    $this->submission->expects($this->once())
      ->method('save');

    $this->logger->expects($this->once())
      ->method('info')
      ->with($this->stringContains('Reminder scheduled'));

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);

    $this->assertTrue($writes['reminder_scheduled']);
    $this->assertArrayHasKey('reminder_send_date', $writes);
    $this->assertSame('email', $writes['reminder_type']);
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

    $writes = [];
    $this->submission->expects($this->exactly(2))
      ->method('setElementData')
      ->willReturnCallback(function ($key, $value) use (&$writes) {
        $writes[$key] = $value;
      });

    $this->submission->expects($this->once())
      ->method('save');

    $this->logger->expects($this->once())
      ->method('info')
      ->with($this->stringContains('Email reminder sent'));

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);

    $this->assertTrue($writes['reminder_sent']);
    $this->assertArrayHasKey('reminder_sent_at', $writes);
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

    $configuration = $this->configuration;
    $configuration['reminder_type'] = 'sms';

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

    $actionMock = $this->createActionMock($this->submission, $configuration);
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

    $configuration = $this->configuration;
    $configuration['delay_days'] = '3';

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->createActionMock($this->submission, $configuration);
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

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);
  }

}
