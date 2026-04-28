<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\ValidateZoningAction;
use Drupal\aabenforms_workflows\Service\GisService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for ValidateZoningAction plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\ValidateZoningAction
 */
class ValidateZoningActionTest extends UnitTestCase {

  /**
   * The action plugin instance.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\ValidateZoningAction
   */
  protected $action;

  /**
   * The GIS service.
   *
   * @var \Drupal\aabenforms_workflows\Service\GisService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $gisService;

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

    $this->gisService = $this->createMock(GisService::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $this->configuration = [
      'address_field' => 'property_address',
      'construction_type_field' => 'construction_type',
      'store_result_in' => 'zoning_validation',
    ];

    $this->action = new ValidateZoningAction(
      $this->configuration,
      'aabenforms_validate_zoning',
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
    $property = $reflection->getProperty('gisService');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->gisService);
  }

  /**
   * Builds a partial-mock of the action that returns the supplied submission.
   */
  protected function createActionMock(WebformSubmissionInterface $submission): ValidateZoningAction {
    $actionMock = $this->getMockBuilder(ValidateZoningAction::class)
      ->setConstructorArgs([
        $this->configuration,
        'aabenforms_validate_zoning',
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
    $property = $reflection->getProperty('gisService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->gisService);

    return $actionMock;
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

    $this->assertTrue($writes['zoning_allowed']);
    $this->assertSame('residential', $writes['zoning_zone_type']);
    $this->assertSame('Garages are permitted in residential zones', $writes['zoning_reason']);
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

    // The production logger call uses placeholder substitution; the
    // resolved string ("ALLOWED" or "NOT ALLOWED") lives in the context
    // array under '@result', not in the message template.
    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Zoning validation'),
        $this->callback(function ($context) {
          return ($context['@result'] ?? '') === 'ALLOWED';
        })
      );

    $actionMock = $this->createActionMock($this->submission);
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

    $writes = [];
    $this->submission->expects($this->exactly(3))
      ->method('setElementData')
      ->willReturnCallback(function ($key, $value) use (&$writes) {
        $writes[$key] = $value;
      });

    $this->submission->expects($this->once())
      ->method('save');

    // The placeholder-resolved value lives in the context array, not the
    // message template.
    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Zoning validation'),
        $this->callback(function ($context) {
          return ($context['@result'] ?? '') === 'NOT ALLOWED';
        })
      );

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);

    $this->assertFalse($writes['zoning_allowed']);
    $this->assertSame('residential', $writes['zoning_zone_type']);
    $this->assertSame('Factories are not permitted in residential zones', $writes['zoning_reason']);
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

    $actionMock = $this->createActionMock($this->submission);
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

    $actionMock = $this->createActionMock($this->submission);
    $actionMock->execute($this->submission);
  }

}
