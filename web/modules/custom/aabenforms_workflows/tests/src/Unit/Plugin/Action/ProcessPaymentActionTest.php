<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_workflows\Plugin\Action\ProcessPaymentAction;
use Drupal\aabenforms_workflows\Service\PaymentService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\eca\Token\TokenInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Psr\Log\LoggerInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for ProcessPaymentAction plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\ProcessPaymentAction
 */
class ProcessPaymentActionTest extends UnitTestCase {

  /**
   * The action plugin.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\ProcessPaymentAction
   */
  protected $action;

  /**
   * Mock payment service.
   *
   * @var \Drupal\aabenforms_workflows\Service\PaymentService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentService;

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

    // Mock dependencies.
    $this->paymentService = $this->createMock(PaymentService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->submission = $this->createMock(WebformSubmissionInterface::class);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $tokenService = $this->createMock(TokenInterface::class);
    $currentUser = $this->createMock(AccountInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $ecaState = $this->createMock(EcaState::class);

    // Create action instance.
    $configuration = [
      'amount_field' => 'payment_amount',
      'currency' => 'DKK',
      'payment_method' => 'nets_easy',
      'description_field' => 'payment_description',
      'store_payment_id_in' => 'payment_id',
      'store_status_in' => 'payment_status',
    ];

    $this->action = new ProcessPaymentAction(
      $configuration,
      'aabenforms_process_payment',
      ['provider' => 'aabenforms_workflows'],
      $entityTypeManager,
      $tokenService,
      $currentUser,
      $time,
      $ecaState,
      $this->logger
    );

    // Inject payment service via reflection.
    $reflection = new \ReflectionClass($this->action);
    $property = $reflection->getProperty('paymentService');
    $property->setAccessible(TRUE);
    $property->setValue($this->action, $this->paymentService);
  }

  /**
   * Tests successful payment processing.
   *
   * @covers ::execute
   */
  public function testSuccessfulPayment(): void {
    // Mock submission data.
    $submissionData = [
      'payment_amount' => 10000,
      'payment_description' => 'Parking permit fee',
    ];

    $this->submission->expects($this->once())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->once())
      ->method('id')
      ->willReturn('123');

    // Mock successful payment result.
    $paymentResult = [
      'status' => 'success',
      'payment_id' => 'PAY-123-456',
      'transaction_id' => 'TXN-ABC123',
      'currency' => 'DKK',
      'timestamp' => time(),
    ];

    $this->paymentService->expects($this->once())
      ->method('processPayment')
      ->with($this->callback(function ($data) {
        return $data['amount'] === 10000
          && $data['currency'] === 'DKK'
          && $data['payment_method'] === 'nets_easy'
          && $data['description'] === 'Parking permit fee';
      }))
      ->willReturn($paymentResult);

    // Expect submission data to be updated.
    $this->submission->expects($this->exactly(4))
      ->method('setElementData')
      ->withConsecutive(
        ['payment_id', 'PAY-123-456'],
        ['payment_status', 'completed'],
        ['payment_transaction_id', 'TXN-ABC123'],
        ['payment_timestamp', $this->anything()]
      );

    $this->submission->expects($this->once())
      ->method('save');

    // Execute action with mock submission.
    $reflection = new \ReflectionClass($this->action);
    $method = $reflection->getMethod('execute');
    $method->setAccessible(TRUE);

    // Mock getSubmission method.
    $getSubmissionMethod = $reflection->getMethod('getSubmission');
    $getSubmissionMethod->setAccessible(TRUE);

    // Use a closure to override getSubmission behavior.
    $actionMock = $this->getMockBuilder(ProcessPaymentAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_process_payment',
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

    // Inject payment service.
    $reflection = new \ReflectionClass($actionMock);
    $property = $reflection->getProperty('paymentService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->paymentService);

    $actionMock->execute($this->submission);
  }

  /**
   * Tests failed payment processing.
   *
   * @covers ::execute
   */
  public function testFailedPayment(): void {
    $submissionData = [
      'payment_amount' => 10000,
      'payment_description' => 'Test payment',
    ];

    $this->submission->expects($this->once())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->once())
      ->method('id')
      ->willReturn('124');

    // Mock failed payment result.
    $paymentResult = [
      'status' => 'failed',
      'error' => 'Card declined',
    ];

    $this->paymentService->expects($this->once())
      ->method('processPayment')
      ->willReturn($paymentResult);

    // Expect failure data to be stored.
    $this->submission->expects($this->exactly(2))
      ->method('setElementData')
      ->withConsecutive(
        ['payment_status', 'failed'],
        ['payment_error', 'Card declined']
      );

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(ProcessPaymentAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_process_payment',
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
    $property = $reflection->getProperty('paymentService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->paymentService);

    $actionMock->execute($this->submission);
  }

  /**
   * Tests handling of invalid amount.
   *
   * @covers ::execute
   */
  public function testInvalidAmount(): void {
    $submissionData = [
      'payment_amount' => -100,
    ];

    $this->submission->expects($this->once())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->once())
      ->method('id')
      ->willReturn('125');

    // Payment service should not be called.
    $this->paymentService->expects($this->never())
      ->method('processPayment');

    // Expect error to be logged.
    $this->logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Invalid amount'));

    $this->submission->expects($this->never())
      ->method('save');

    $actionMock = $this->getMockBuilder(ProcessPaymentAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_process_payment',
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
    $property = $reflection->getProperty('paymentService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->paymentService);

    $actionMock->execute($this->submission);
  }

  /**
   * Tests handling of missing configuration.
   *
   * @covers ::execute
   */
  public function testMissingConfiguration(): void {
    $submissionData = [
      'wrong_field' => 10000,
    ];

    $this->submission->expects($this->once())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->once())
      ->method('id')
      ->willReturn('126');

    // Payment service should not be called.
    $this->paymentService->expects($this->never())
      ->method('processPayment');

    // Expect error to be logged.
    $this->logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Amount field'));

    $actionMock = $this->getMockBuilder(ProcessPaymentAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_process_payment',
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
    $property = $reflection->getProperty('paymentService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->paymentService);

    $actionMock->execute($this->submission);
  }

  /**
   * Tests payment service integration.
   *
   * @covers ::execute
   */
  public function testPaymentServiceIntegration(): void {
    $submissionData = [
      'payment_amount' => 25000,
      'payment_description' => 'Integration test payment',
    ];

    $this->submission->expects($this->once())
      ->method('getData')
      ->willReturn($submissionData);

    $this->submission->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn('127');

    // Test that correct data is passed to payment service.
    $this->paymentService->expects($this->once())
      ->method('processPayment')
      ->with($this->callback(function ($data) {
        // Verify all required fields are present.
        $this->assertArrayHasKey('amount', $data);
        $this->assertArrayHasKey('currency', $data);
        $this->assertArrayHasKey('order_id', $data);
        $this->assertArrayHasKey('payment_method', $data);
        $this->assertArrayHasKey('description', $data);

        // Verify values.
        $this->assertEquals(25000, $data['amount']);
        $this->assertEquals('DKK', $data['currency']);
        $this->assertEquals('nets_easy', $data['payment_method']);
        $this->assertEquals('Integration test payment', $data['description']);
        $this->assertStringStartsWith('WF-127-', $data['order_id']);

        return TRUE;
      }))
      ->willReturn([
        'status' => 'success',
        'payment_id' => 'PAY-TEST-789',
        'transaction_id' => 'TXN-TEST',
        'currency' => 'DKK',
        'timestamp' => time(),
      ]);

    $this->submission->expects($this->atLeastOnce())
      ->method('setElementData');

    $this->submission->expects($this->once())
      ->method('save');

    $actionMock = $this->getMockBuilder(ProcessPaymentAction::class)
      ->setConstructorArgs([
        $this->action->getConfiguration(),
        'aabenforms_process_payment',
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
    $property = $reflection->getProperty('paymentService');
    $property->setAccessible(TRUE);
    $property->setValue($actionMock, $this->paymentService);

    $actionMock->execute($this->submission);
  }

  /**
   * Tests default configuration.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration(): void {
    $config = $this->action->defaultConfiguration();

    $this->assertIsArray($config);
    $this->assertArrayHasKey('amount_field', $config);
    $this->assertArrayHasKey('currency', $config);
    $this->assertArrayHasKey('payment_method', $config);
    $this->assertEquals('amount', $config['amount_field']);
    $this->assertEquals('DKK', $config['currency']);
    $this->assertEquals('nets_easy', $config['payment_method']);
  }

}
