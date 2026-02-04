<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_workflows\Plugin\Action\FetchAvailableSlotsAction;
use Drupal\aabenforms_workflows\Service\CalendarService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Psr\Log\LoggerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for FetchAvailableSlotsAction plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\FetchAvailableSlotsAction
 */
class FetchAvailableSlotsActionTest extends UnitTestCase {

  protected $action;
  protected $calendarService;
  protected $logger;
  protected $submission;

  protected function setUp(): void {
    parent::setUp();

    $this->calendarService = $this->createMock(CalendarService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $configuration = [
      'start_date_field' => 'preferred_date',
      'date_range_days' => '30',
      'slot_duration' => '60',
      'location' => 'Borgerservice',
      'store_slots_in' => 'available_slots',
    ];

    $this->action = new FetchAvailableSlotsAction(
      $configuration,
      'aabenforms_fetch_available_slots',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );

    $reflection = new \ReflectionClass($this->action);
    $property = $reflection->getProperty('calendarService');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->calendarService);
  }

  /**
   * @covers ::execute
   */
  public function testSlotsFetch(): void {
    $submissionData = [
      'preferred_date' => '2026-03-01',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('600');

    $mockSlots = [
      [
        'slot_id' => 'SLOT-20260301-1000',
        'date' => '2026-03-01',
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration' => 60,
        'location' => 'Borgerservice',
        'available' => TRUE,
      ],
      [
        'slot_id' => 'SLOT-20260301-1100',
        'date' => '2026-03-01',
        'start_time' => '11:00',
        'end_time' => '12:00',
        'duration' => 60,
        'location' => 'Borgerservice',
        'available' => TRUE,
      ],
    ];

    $slotsResult = [
      'status' => 'success',
      'slots' => $mockSlots,
      'total_slots' => 2,
    ];

    $this->calendarService->expects($this->once())
      ->method('getAvailableSlots')
      ->with(
        '2026-03-01',
        $this->anything(),
        60,
        $this->callback(function ($options) {
          return $options['location'] === 'Borgerservice';
        })
      )
      ->willReturn($slotsResult);

    $this->submission->expects($this->exactly(4))
      ->method('setElementData')
      ->withConsecutive(
        ['available_slots', $this->isType('string')],
        ['slots_count', 2],
        ['slots_start_date', '2026-03-01'],
        ['slots_end_date', $this->anything()]
      );

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(FetchAvailableSlotsAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_fetch_available_slots',
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
    $property = $reflection->getProperty('calendarService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->calendarService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testDateRangeFiltering(): void {
    $submissionData = [
      'preferred_date' => '2026-04-01',
    ];

    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('601');

    $this->calendarService->expects($this->once())
      ->method('getAvailableSlots')
      ->with(
        '2026-04-01',
        '2026-05-01',
        60,
        $this->anything()
      )
      ->willReturn([
        'status' => 'success',
        'slots' => [],
        'total_slots' => 0,
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(FetchAvailableSlotsAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_fetch_available_slots',
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
    $property = $reflection->getProperty('calendarService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->calendarService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testSlotDuration(): void {
    $submissionData = ['preferred_date' => '2026-05-01'];
    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('602');

    $configuration = $this->action->getConfiguration();
    $configuration['slot_duration'] = '90';

    $actionWith90Min = new FetchAvailableSlotsAction(
      $configuration,
      'aabenforms_fetch_available_slots',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );

    $this->calendarService->expects($this->once())
      ->method('getAvailableSlots')
      ->with(
        $this->anything(),
        $this->anything(),
        90,
        $this->anything()
      )
      ->willReturn([
        'status' => 'success',
        'slots' => [],
        'total_slots' => 0,
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(FetchAvailableSlotsAction::class)
      ->setConstructorArgs([
        $configuration,
        'aabenforms_fetch_available_slots',
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
    $property = $reflection->getProperty('calendarService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->calendarService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testEmptySlots(): void {
    $submissionData = ['preferred_date' => '2026-06-01'];
    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('603');

    $this->calendarService->expects($this->once())
      ->method('getAvailableSlots')
      ->willReturn([
        'status' => 'success',
        'slots' => [],
        'total_slots' => 0,
      ]);

    $this->submission->expects($this->exactly(4))
      ->method('setElementData')
      ->withConsecutive(
        ['available_slots', '[]'],
        ['slots_count', 0],
        ['slots_start_date', '2026-06-01'],
        ['slots_end_date', $this->anything()]
      );

    $this->submission->expects($this->once())
      ->method('save');

    $this->logger->expects($this->once())
      ->method('info')
      ->with($this->stringContains('Fetched 0 available slots'));

    $actionMock = $this->getMockBuilder(FetchAvailableSlotsAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_fetch_available_slots',
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
    $property = $reflection->getProperty('calendarService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->calendarService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testCalendarServiceIntegration(): void {
    $today = date('Y-m-d');
    $submissionData = ['preferred_date' => $today];
    $this->submission->method('getData')->willReturn($submissionData);
    $this->submission->method('id')->willReturn('604');

    $this->calendarService->expects($this->once())
      ->method('getAvailableSlots')
      ->with(
        $this->equalTo($today),
        $this->isType('string'),
        $this->isType('int'),
        $this->callback(function ($options) {
          $this->assertArrayHasKey('location', $options);
          return TRUE;
        })
      )
      ->willReturn([
        'status' => 'success',
        'slots' => [
          [
            'slot_id' => 'SLOT-TEST-1',
            'date' => $today,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration' => 60,
            'location' => 'Borgerservice',
            'available' => TRUE,
          ],
        ],
        'total_slots' => 1,
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(FetchAvailableSlotsAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_fetch_available_slots',
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
    $property = $reflection->getProperty('calendarService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->calendarService);

    $actionMock->execute($this->submission);
  }

}
