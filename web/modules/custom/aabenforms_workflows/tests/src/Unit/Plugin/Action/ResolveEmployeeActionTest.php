<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\ResolveEmployeeAction;
use Drupal\aabenforms_workflows\Service\OrgChartServiceInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface as EcaTokenInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ResolveEmployeeAction ECA plugin.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\ResolveEmployeeAction
 * @group aabenforms_workflows
 */
class ResolveEmployeeActionTest extends UnitTestCase {

  /**
   * The action under test.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\ResolveEmployeeAction
   */
  protected ResolveEmployeeAction $action;

  /**
   * Mock current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

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

    $this->account = $this->createMock(AccountProxyInterface::class);
    $this->orgChart = $this->createMock(OrgChartServiceInterface::class);

    $configuration = [
      'submitted_employee_id_token' => 'submitted_id',
      'result_token' => 'employee_id',
    ];
    $this->action = new ResolveEmployeeAction(
      $configuration,
      'aabenforms_resolve_employee',
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
    foreach (['account' => $this->account, 'orgChart' => $this->orgChart] as $prop => $value) {
      $p = $reflection->getProperty($prop);
      $p->setAccessible(TRUE);
      $p->setValue($this->action, $value);
    }
  }

  /**
   * Anonymous submitters are rejected.
   *
   * @covers ::execute
   */
  public function testAnonymousFailsClosed(): void {
    $this->account->method('isAnonymous')->willReturn(TRUE);
    $this->action->execute();
    $this->assertSame('failed', $this->tokenStorage['employee_id_status']);
    $this->assertSame('', $this->tokenStorage['employee_id']);
  }

  /**
   * An authenticated account mapped to an employee resolves verified.
   *
   * @covers ::execute
   */
  public function testMappedAccountResolvesVerified(): void {
    $this->account->method('isAnonymous')->willReturn(FALSE);
    $this->account->method('getAccountName')->willReturn('admin');
    $this->orgChart->method('employeeIdForAccountName')->with('admin')->willReturn('E1001');

    $this->action->execute();

    $this->assertSame('verified', $this->tokenStorage['employee_id_status']);
    $this->assertSame('E1001', $this->tokenStorage['employee_id']);
  }

  /**
   * An authenticated account not in the directory fails closed.
   *
   * @covers ::execute
   */
  public function testUnmappedAccountFailsClosed(): void {
    $this->account->method('isAnonymous')->willReturn(FALSE);
    $this->account->method('getAccountName')->willReturn('stranger');
    $this->orgChart->method('employeeIdForAccountName')->willReturn('');

    $this->action->execute();

    $this->assertSame('failed', $this->tokenStorage['employee_id_status']);
  }

}
