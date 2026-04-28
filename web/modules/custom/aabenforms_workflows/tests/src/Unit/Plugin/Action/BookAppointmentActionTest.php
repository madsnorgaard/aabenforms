<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\BookAppointmentAction;
use Drupal\aabenforms_workflows\Service\CalendarService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformInterface;

/**
 * Tests for BookAppointmentAction plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\BookAppointmentAction
 */
class BookAppointmentActionTest extends UnitTestCase {

  /**
   * The action plugin instance.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\BookAppointmentAction
   */
  protected $action;

  /**
   * The calendar service.
   *
   * @var \Drupal\aabenforms_workflows\Service\CalendarService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $calendarService;

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

    $this->calendarService = $this->createMock(CalendarService::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $this->configuration = [
      'slot_id_field' => 'selected_slot_id',
      'attendee_name_field' => 'name',
      'attendee_email_field' => 'email',
      'attendee_phone_field' => 'phone',
      'attendee_cpr_field' => 'cpr',
      'store_booking_id_in' => 'booking_id',
    ];

    $this->action = new BookAppointmentAction(
      $this->configuration,
      'aabenforms_book_appointment',
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
    $property = $reflection->getProperty('calendarService');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->calendarService);
  }

  /**
   * Builds a partial-mock of the action that returns the supplied submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission the mock's getSubmission() should return.
   *
   * @return \Drupal\aabenforms_workflows\Plugin\Action\BookAppointmentAction
   *   The configured partial mock.
   */
  protected function createActionMock(WebformSubmissionInterface $submission): BookAppointmentAction {
    $actionMock = $this->getMockBuilder(BookAppointmentAction::class)
      ->setConstructorArgs([
        $this->configuration,
        'aabenforms_book_appointment',
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

    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('calendarService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->calendarService);

    return $actionMock;
  }

  /**
   * @covers ::execute
   */
  public function testSuccessfulBooking(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('id')->willReturn('marriage_booking');

    $submissionData = [
      'selected_slot_id' => 'SLOT-20260315-1400',
      'name' => 'John Doe',
      'email' => 'john@example.com',
      'phone' => '+4512345678',
      'cpr' => '1234567890',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('700');
    $this->submission->method('getWebform')->willReturn($webform);

    $bookingResult = [
      'status' => 'success',
      'booking_id' => 'BOOK-ABC-123',
      'slot_id' => 'SLOT-20260315-1400',
      'attendees' => [],
      'booked_at' => time(),
    ];

    $this->calendarService->expects($this->once())
      ->method('bookSlot')
      ->with(
        'SLOT-20260315-1400',
        $this->callback(function ($attendees) {
          return count($attendees) === 1
            && $attendees[0]['name'] === 'John Doe'
            && $attendees[0]['email'] === 'john@example.com';
        }),
        $this->callback(function ($details) {
          return $details['submission_id'] === '700'
            && $details['webform_id'] === 'marriage_booking';
        })
      )
      ->willReturn($bookingResult);

    $writes = [];
    $this->submission->expects($this->exactly(4))
      ->method('setElementData')
      ->willReturnCallback(function ($key, $value) use (&$writes) {
        $writes[$key] = $value;
      });

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);

    $this->assertSame('BOOK-ABC-123', $writes['booking_id']);
    $this->assertSame('SLOT-20260315-1400', $writes['booking_slot_id']);
    $this->assertSame('confirmed', $writes['booking_status']);
    $this->assertArrayHasKey('booked_at', $writes);
  }

  /**
   * @covers ::execute
   */
  public function testDoubleBookingPrevention(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('id')->willReturn('test_form');

    $submissionData = [
      'selected_slot_id' => 'SLOT-20260315-1500',
      'name' => 'Jane Smith',
      'email' => 'jane@example.com',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('701');
    $this->submission->method('getWebform')->willReturn($webform);

    $bookingResult = [
      'status' => 'failed',
      'error' => 'Slot already booked (double booking prevented)',
      'slot_id' => 'SLOT-20260315-1500',
    ];

    $this->calendarService->expects($this->once())
      ->method('bookSlot')
      ->willReturn($bookingResult);

    $writes = [];
    $this->submission->expects($this->exactly(2))
      ->method('setElementData')
      ->willReturnCallback(function ($key, $value) use (&$writes) {
        $writes[$key] = $value;
      });

    $this->submission->expects($this->once())
      ->method('save');

    $this->logger->expects($this->once())
      ->method('warning')
      ->with($this->stringContains('Appointment booking failed'));

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);

    $this->assertSame('failed', $writes['booking_status']);
    $this->assertSame('Slot already booked (double booking prevented)', $writes['booking_error']);
  }

  /**
   * @covers ::execute
   */
  public function testMultipleAttendees(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('id')->willReturn('marriage_booking');

    $submissionData = [
      'selected_slot_id' => 'SLOT-20260320-1000',
      'name' => 'Partner One',
      'email' => 'partner1@example.com',
      'phone' => '+4511111111',
      'partner2_name' => 'Partner Two',
      'partner2_email' => 'partner2@example.com',
      'partner2_phone' => '+4522222222',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('702');
    $this->submission->method('getWebform')->willReturn($webform);

    $bookingResult = [
      'status' => 'success',
      'booking_id' => 'BOOK-DUAL-456',
      'slot_id' => 'SLOT-20260320-1000',
      'attendees' => [],
      'booked_at' => time(),
    ];

    $this->calendarService->expects($this->once())
      ->method('bookSlot')
      ->with(
        'SLOT-20260320-1000',
        $this->callback(function ($attendees) {
          return count($attendees) === 2
            && $attendees[0]['name'] === 'Partner One'
            && $attendees[1]['name'] === 'Partner Two';
        }),
        $this->anything()
      )
      ->willReturn($bookingResult);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testInvalidSlot(): void {
    $submissionData = [];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('703');

    $this->calendarService->expects($this->never())
      ->method('bookSlot');

    $this->logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Slot ID field'));

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testBookingServiceIntegration(): void {
    $webform = $this->createMock(WebformInterface::class);
    $webform->method('id')->willReturn('test_form');

    $submissionData = [
      'selected_slot_id' => 'SLOT-TEST-999',
      'name' => 'Integration Test',
      'email' => 'test@example.com',
      'phone' => '+4599999999',
      'booking_type' => 'ceremony',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('704');
    $this->submission->method('getWebform')->willReturn($webform);

    $this->calendarService->expects($this->once())
      ->method('bookSlot')
      ->with(
        $this->equalTo('SLOT-TEST-999'),
        $this->isType('array'),
        $this->callback(function ($details) {
          $this->assertArrayHasKey('submission_id', $details);
          $this->assertArrayHasKey('webform_id', $details);
          $this->assertArrayHasKey('booking_type', $details);
          $this->assertEquals('ceremony', $details['booking_type']);
          return TRUE;
        })
      )
      ->willReturn([
        'status' => 'success',
        'booking_id' => 'BOOK-INT-TEST',
        'slot_id' => 'SLOT-TEST-999',
        'attendees' => [],
        'booked_at' => time(),
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);
  }

}
