<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\DenyAction;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface as EcaTokenInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the DenyAction ECA plugin.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\DenyAction
 * @group aabenforms_workflows
 */
class DenyActionTest extends UnitTestCase {

  /**
   * The DenyAction plugin.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\DenyAction
   */
  protected DenyAction $action;

  /**
   * Mock audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $auditLogger;

  /**
   * Mock execution collector.
   *
   * @var \Drupal\aabenforms_core\Service\WorkflowExecutionCollector|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $collector;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $currentUser = $this->createMock(AccountProxyInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $ecaState = $this->createMock(EcaState::class);
    $logger = $this->createMock(LoggerChannelInterface::class);
    $tokenServices = $this->createMock(EcaTokenInterface::class);

    $this->auditLogger = $this->getMockBuilder(AuditLogger::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['log'])
      ->getMock();
    $this->collector = $this->createMock(WorkflowExecutionCollector::class);

    $configuration = [
      'event_type' => 'citizen_service_denied',
      'step_label' => 'Identitet ikke bekraeftet',
      'message' => 'Sagen blev ikke behandlet.',
    ];

    $this->action = new DenyAction(
      $configuration,
      'aabenforms_workflow_deny',
      [],
      $entityTypeManager,
      $tokenServices,
      $currentUser,
      $time,
      $ecaState,
      $logger
    );
    $this->action->setExecutionCollector($this->collector);

    $reflectionClass = new \ReflectionClass($this->action);
    $auditLoggerProperty = $reflectionClass->getProperty('auditLogger');
    $auditLoggerProperty->setAccessible(TRUE);
    $auditLoggerProperty->setValue($this->action, $this->auditLogger);
  }

  /**
   * Records a failed step and writes a denied audit row.
   *
   * @covers ::execute
   */
  public function testDenyRecordsFailedStepAndDeniedAudit(): void {
    $this->collector->expects($this->once())
      ->method('addStep')
      ->with(
        'aabenforms_workflow_deny',
        'Identitet ikke bekraeftet',
        'Sagen blev ikke behandlet.',
        'failed'
      );

    $this->auditLogger->expects($this->once())
      ->method('log')
      ->with(
        'citizen_service_denied',
        'system',
        'Sagen blev ikke behandlet.',
        'denied',
        $this->anything()
      );

    $this->action->execute();
  }

  /**
   * Tests default configuration.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration(): void {
    $defaults = $this->action->defaultConfiguration();
    $this->assertArrayHasKey('event_type', $defaults);
    $this->assertArrayHasKey('step_label', $defaults);
    $this->assertArrayHasKey('message', $defaults);
  }

}
