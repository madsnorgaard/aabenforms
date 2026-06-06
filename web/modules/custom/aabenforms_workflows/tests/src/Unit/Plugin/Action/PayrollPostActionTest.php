<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\PayrollPostAction;
use Drupal\aabenforms_workflows\Service\OrgChartServiceInterface;
use Drupal\aabenforms_workflows\Service\PayrollService;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface as EcaTokenInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the PayrollPostAction policy check.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\PayrollPostAction
 * @group aabenforms_workflows
 */
class PayrollPostActionTest extends UnitTestCase {

  /**
   * The action under test.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\PayrollPostAction
   */
  protected PayrollPostAction $action;

  /**
   * Mock payroll service.
   *
   * @var \Drupal\aabenforms_workflows\Service\PayrollService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payroll;

  /**
   * Mock org chart.
   *
   * @var \Drupal\aabenforms_workflows\Service\OrgChartServiceInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $orgChart;

  /**
   * Token storage for testing.
   *
   * @var array
   */
  protected array $tokenStorage = [];

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
    $tokenServices->method('getTokenData')->willReturnCallback(fn ($name) => $this->tokenStorage[$name] ?? NULL);
    $tokenServices->method('addTokenData')->willReturnCallback(function ($name, $value) use ($tokenServices) {
      $this->tokenStorage[$name] = $value;
      return $tokenServices;
    });

    $this->payroll = $this->getMockBuilder(PayrollService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['forward'])
      ->getMock();
    $this->orgChart = $this->createMock(OrgChartServiceInterface::class);

    $configuration = [
      'employee_id_token' => 'emp',
      'amount_token' => 'amount',
      'claim_type' => 'mileage_expense',
      'result_token' => 'payroll_result',
    ];
    $this->action = new PayrollPostAction(
      $configuration,
      'aabenforms_payroll_post',
      [],
      $entityTypeManager,
      $tokenServices,
      $currentUser,
      $time,
      $ecaState,
      $logger
    );
    $this->action->setExecutionCollector($this->createMock(WorkflowExecutionCollector::class));

    $reflection = new \ReflectionClass($this->action);
    foreach (['payroll' => $this->payroll, 'orgChart' => $this->orgChart] as $prop => $value) {
      $p = $reflection->getProperty($prop);
      $p->setAccessible(TRUE);
      $p->setValue($this->action, $value);
    }

    $this->tokenStorage['emp'] = 'E1001';
  }

  /**
   * An amount within the policy limit is forwarded to payroll.
   *
   * @covers ::execute
   */
  public function testWithinLimitForwards(): void {
    $this->tokenStorage['amount'] = '450';
    $this->orgChart->method('tierLimitCents')->willReturn(500000);
    $this->payroll->expects($this->once())
      ->method('forward')
      ->willReturn([
        'status' => PayrollService::STATUS_SUCCESS,
        'transaction_id' => 'TX1',
        'reason_code' => '',
        'message' => 'ok',
      ]);

    $this->action->execute();
    $this->assertSame(PayrollService::STATUS_SUCCESS, $this->tokenStorage['payroll_result']['status']);
  }

  /**
   * An amount over the policy limit is blocked and never forwarded.
   *
   * @covers ::execute
   */
  public function testOverLimitBlocked(): void {
    $this->tokenStorage['amount'] = '6000';
    $this->orgChart->method('tierLimitCents')->willReturn(500000);
    $this->payroll->expects($this->never())->method('forward');

    $this->action->execute();

    $this->assertSame(PayrollService::STATUS_FAILURE, $this->tokenStorage['payroll_result']['status']);
    $this->assertSame('AMOUNT_EXCEEDS_POLICY', $this->tokenStorage['payroll_result']['reason_code']);
  }

}
