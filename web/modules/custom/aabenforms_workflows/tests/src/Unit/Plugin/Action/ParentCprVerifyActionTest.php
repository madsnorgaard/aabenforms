<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\aabenforms_workflows\Plugin\Action\ParentCprVerifyAction;
use Drupal\aabenforms_workflows\Service\ParentCprVerifier;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface as EcaTokenInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests the ParentCprVerifyAction ECA plugin (issue #54 consent gate).
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\ParentCprVerifyAction
 * @group aabenforms_workflows
 */
class ParentCprVerifyActionTest extends UnitTestCase {

  /**
   * The action under test.
   *
   * @var \Drupal\aabenforms_workflows\Plugin\Action\ParentCprVerifyAction
   */
  protected ParentCprVerifyAction $action;

  /**
   * Mock parent CPR verifier.
   *
   * @var \Drupal\aabenforms_workflows\Service\ParentCprVerifier|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cprVerifier;

  /**
   * Mock logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock ECA token services.
   *
   * @var \Drupal\eca\Token\TokenInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tokenServices;

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
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    $this->tokenServices = $this->createMock(EcaTokenInterface::class);
    $this->tokenServices->method('getTokenData')
      ->willReturnCallback(fn ($name) => $this->tokenStorage[$name] ?? NULL);
    $this->tokenServices->method('addTokenData')
      ->willReturnCallback(function ($name, $value) {
        $this->tokenStorage[$name] = $value;
        return $this->tokenServices;
      });

    $this->cprVerifier = $this->createMock(ParentCprVerifier::class);

    $configuration = [
      'parent_number' => '1',
      'workflow_id_token' => '',
      'result_token' => 'cpr_consent_result',
    ];

    $this->action = new ParentCprVerifyAction(
      $configuration,
      'aabenforms_parent_cpr_verify',
      [],
      $entityTypeManager,
      $this->tokenServices,
      $currentUser,
      $time,
      $ecaState,
      $this->logger
    );
    $this->action->setExecutionCollector($this->createMock(WorkflowExecutionCollector::class));

    $reflection = new \ReflectionProperty($this->action, 'cprVerifier');
    $reflection->setAccessible(TRUE);
    $reflection->setValue($this->action, $this->cprVerifier);
  }

  /**
   * Builds a webform submission mock and puts it in the token environment.
   */
  protected function seedSubmission(int $id = 42): WebformSubmissionInterface {
    $submission = $this->createMock(WebformSubmissionInterface::class);
    $submission->method('id')->willReturn($id);
    $this->tokenStorage['webform_submission'] = $submission;
    return $submission;
  }

  /**
   * A MATCH result is stored on the result token.
   *
   * @covers ::execute
   */
  public function testMatchStored(): void {
    $this->seedSubmission();
    $this->cprVerifier->method('verify')->willReturn(ParentCprVerifier::RESULT_MATCH);

    $this->action->execute();

    $this->assertSame(ParentCprVerifier::RESULT_MATCH, $this->tokenStorage['cpr_consent_result']);
  }

  /**
   * A MISMATCH result is stored on the result token (consent denied).
   *
   * @covers ::execute
   */
  public function testMismatchStored(): void {
    $this->seedSubmission();
    $this->cprVerifier->method('verify')->willReturn(ParentCprVerifier::RESULT_MISMATCH);

    $this->action->execute();

    $this->assertSame(ParentCprVerifier::RESULT_MISMATCH, $this->tokenStorage['cpr_consent_result']);
  }

  /**
   * The deterministic workflow id and parent number reach the verifier.
   *
   * With no token override, the action derives parent_approval_<sid>_p<N>.
   *
   * @covers ::execute
   */
  public function testDerivesDeterministicWorkflowId(): void {
    $submission = $this->seedSubmission(99);

    $this->cprVerifier->expects($this->once())
      ->method('verify')
      ->with($submission, 1, 'parent_approval_99_p1')
      ->willReturn(ParentCprVerifier::RESULT_MATCH);

    $this->action->execute();
  }

  /**
   * An explicit workflow_id token overrides the derived id.
   *
   * @covers ::execute
   */
  public function testWorkflowIdTokenOverride(): void {
    $submission = $this->seedSubmission(99);
    $this->tokenStorage['my_wid'] = 'explicit-wid';
    $config = new \ReflectionProperty($this->action, 'configuration');
    $config->setAccessible(TRUE);
    $config->setValue($this->action, [
      'parent_number' => '1',
      'workflow_id_token' => 'my_wid',
      'result_token' => 'cpr_consent_result',
    ]);

    $this->cprVerifier->expects($this->once())
      ->method('verify')
      ->with($submission, 1, 'explicit-wid')
      ->willReturn(ParentCprVerifier::RESULT_MATCH);

    $this->action->execute();
  }

  /**
   * No submission in context: do not fail open - record as not verified.
   *
   * @covers ::execute
   */
  public function testNoSubmissionDoesNotFailOpen(): void {
    $this->cprVerifier->expects($this->never())->method('verify');

    $this->action->execute();

    $this->assertSame(
      ParentCprVerifier::RESULT_MISSING_EXPECTED_CPR,
      $this->tokenStorage['cpr_consent_result']
    );
  }

  /**
   * If the verifier throws, the gate is denied (mismatch), never failed open.
   *
   * @covers ::execute
   */
  public function testNeverFailsOpenOnException(): void {
    $this->seedSubmission();
    $this->cprVerifier->method('verify')
      ->willThrowException(new \RuntimeException('boom'));
    $this->logger->expects($this->once())->method('error');

    $this->action->execute();

    $this->assertSame(ParentCprVerifier::RESULT_MISMATCH, $this->tokenStorage['cpr_consent_result']);
  }

  /**
   * Default configuration exposes the expected keys.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration(): void {
    $defaults = $this->action->defaultConfiguration();
    $this->assertSame('1', $defaults['parent_number']);
    $this->assertSame('', $defaults['workflow_id_token']);
    $this->assertSame('cpr_consent_result', $defaults['result_token']);
  }

}
