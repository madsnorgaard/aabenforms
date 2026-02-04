<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_workflows\Plugin\Action\ValidateZoningAction;
use Drupal\aabenforms_workflows\Service\GisService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Psr\Log\LoggerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for ValidateZoningAction plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\ValidateZoningAction
 */
class ValidateZoningActionTest extends UnitTestCase {

  protected $action;
  protected $gisService;
  protected $logger;
  protected $submission;

  protected function setUp(): void {
    parent::setUp();

    $this->gisService = $this->createMock(GisService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $configuration = [
      'address_field' => 'property_address',
      'construction_type_field' => 'construction_type',
      'store_result_in' => 'zoning_validation',
    ];

    $this->action = new ValidateZoningAction(
      $configuration,
      'aabenforms_validate_zoning',
      ['provider' => 'aabenforms_workflows'],
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TokenInterface::class),
      $this->createMock(AccountInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(EcaState::class),
      $this->logger
    );

    $reflection = new \ReflectionClass($this->action);
    $property = $reflection->getProperty('gisService');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->gisService);
  }

  /**
   * @covers ::execute
   */
  public function testZoningValidation(): void {
    $submissionData = [
      'property_address' => 'Hovedgaden 1, 8000 Aarhus C',
      'construction_type' => 'garage',
    ];

    $this->submission->method('getData')->willReturn($submissionData);

    $validationResult = [
      'allowed' => TRUE,
      'zone_type' => 'residential',
      'reason' => 'Garages are permitted in residential zones',
    ];

    $this->gisService->expects($this->once())
      ->method('validateConstructionType')
      ->with('Hovedgaden 1, 8000 Aarhus C', 'garage')
      ->willReturn($validationResult);

    $this->submission->expects($this->exactly(3))
      ->method('setElementData')
      ->withConsecutive(
        ['zoning_allowed', TRUE],
        ['zoning_zone_type', 'residential'],
        ['zoning_reason', 'Garages are permitted in residential zones']
      );

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(ValidateZoningAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_validate_zoning',
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
    $property = $reflection->getProperty('gisService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->gisService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testAllowedConstruction(): void {
    $submissionData = [
      'property_address' => 'Erhvervsvej 10, 8200 Aarhus N',
      'construction_type' => 'warehouse',
    ];

    $this->submission->method('getData')->willReturn($submissionData);

    $validationResult = [
      'allowed' => TRUE,
      'zone_type' => 'industrial',
      'reason' => 'Warehouses are permitted in industrial zones',
    ];

    $this->gisService->expects($this->once())
      ->method('validateConstructionType')
      ->willReturn($validationResult);

    $this->submission->expects($this->exactly(3))
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $this->logger->expects($this->once())
      ->method('info')
      ->with($this->stringContains('ALLOWED'));

    $actionMock = $this->getMockBuilder(ValidateZoningAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_validate_zoning',
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
    $property = $reflection->getProperty('gisService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->gisService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testProhibitedConstruction(): void {
    $submissionData = [
      'property_address' => 'Parkvej 5, 8000 Aarhus C',
      'construction_type' => 'factory',
    ];

    $this->submission->method('getData')->willReturn($submissionData);

    $validationResult = [
      'allowed' => FALSE,
      'zone_type' => 'residential',
      'reason' => 'Factories are not permitted in residential zones',
    ];

    $this->gisService->expects($this->once())
      ->method('validateConstructionType')
      ->willReturn($validationResult);

    $this->submission->expects($this->exactly(3))
      ->method('setElementData')
      ->withConsecutive(
        ['zoning_allowed', FALSE],
        ['zoning_zone_type', 'residential'],
        ['zoning_reason', 'Factories are not permitted in residential zones']
      );

    $this->submission->expects($this->once())
      ->method('save');

    $this->logger->expects($this->once())
      ->method('info')
      ->with($this->stringContains('NOT ALLOWED'));

    $actionMock = $this->getMockBuilder(ValidateZoningAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_validate_zoning',
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
    $property = $reflection->getProperty('gisService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->gisService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testInvalidAddress(): void {
    $submissionData = [
      'property_address' => '',
      'construction_type' => 'garage',
    ];

    $this->submission->method('getData')->willReturn($submissionData);

    $this->gisService->expects($this->never())
      ->method('validateConstructionType');

    $this->logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Missing address'));

    $actionMock = $this->getMockBuilder(ValidateZoningAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_validate_zoning',
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
    $property = $reflection->getProperty('gisService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->gisService);

    $actionMock->execute($this->submission);
  }

  /**
   * @covers ::execute
   */
  public function testGisServiceIntegration(): void {
    $submissionData = [
      'property_address' => 'Integration Test Address',
      'construction_type' => 'extension',
    ];

    $this->submission->method('getData')->willReturn($submissionData);

    $this->gisService->expects($this->once())
      ->method('validateConstructionType')
      ->with(
        $this->equalTo('Integration Test Address'),
        $this->equalTo('extension')
      )
      ->willReturn([
        'allowed' => TRUE,
        'zone_type' => 'mixed',
        'reason' => 'Extensions are permitted with restrictions',
      ]);

    $this->submission->expects($this->exactly(3))
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(ValidateZoningAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_validate_zoning',
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
    $property = $reflection->getProperty('gisService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->gisService);

    $actionMock->execute($this->submission);
  }

}
