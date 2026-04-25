<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_digital_post_eca\Unit\Plugin\Action;

use Drupal\aabenforms_digital_post\DigitalPost\Result;
use Drupal\aabenforms_digital_post\Service\DigitalPostSender;
use Drupal\aabenforms_digital_post_eca\Plugin\Action\SendDigitalPostAction;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenServices;
use PHPUnit\Framework\TestCase;

/**
 * Tests the SendDigitalPostAction ECA action.
 *
 * @coversDefaultClass \Drupal\aabenforms_digital_post_eca\Plugin\Action\SendDigitalPostAction
 * @group aabenforms_digital_post_eca
 */
class SendDigitalPostActionTest extends TestCase {

  /**
   * Mock DigitalPostSender.
   */
  private DigitalPostSender $sender;

  /**
   * Mock ConfigFactory.
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Mock TokenServices.
   */
  private TokenServices $tokenService;

  /**
   * Mock WorkflowExecutionCollector.
   */
  private WorkflowExecutionCollector $executionCollector;

  /**
   * Mock EntityTypeManager.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock AccountProxy.
   */
  private AccountProxyInterface $currentUser;

  /**
   * Mock Time.
   */
  private TimeInterface $time;

  /**
   * Mock EcaState.
   */
  private EcaState $ecaState;

  /**
   * Mock Logger.
   */
  private LoggerChannelInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->sender = $this->createMock(DigitalPostSender::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->tokenService = $this->createMock(TokenServices::class);
    $this->executionCollector = $this->createMock(WorkflowExecutionCollector::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->ecaState = $this->createMock(EcaState::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    // Configure config factory
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['sender_cvr', '12345678'],
      ['sender_name', 'Test Kommune'],
    ]);
    $this->configFactory->method('get')
      ->with('aabenforms_digital_post.settings')
      ->willReturn($config);

    $this->sender->method('testMode')->willReturn('test_mode');
  }

  /**
   * Creates a SendDigitalPostAction instance with test configuration.
   */
  private function createAction(array $config = []): SendDigitalPostAction {
    $defaultConfig = [
      'recipient_token' => '[webform_submission:values:cpr:raw]',
      'recipient_type' => 'cpr',
      'sender_cvr_token' => '',
      'subject_template' => 'Afgørelse',
      'body_template' => '<p>Se vedlagte bilag.</p>',
      'type' => 'Digital Post',
      'result_token' => 'digital_post_result',
    ];

    $mergedConfig = array_merge($defaultConfig, $config);

    // Create a testable subclass that exposes protected methods.
    $action = $this->getMockBuilder(SendDigitalPostAction::class)
      ->setConstructorArgs([
        $mergedConfig,
        'aabenforms_digital_post_send',
        ['id' => 'aabenforms_digital_post_send'],
        $this->entityTypeManager,
        $this->tokenService,
        $this->currentUser,
        $this->time,
        $this->ecaState,
        $this->logger,
      ])
      ->onlyMethods(['eventEntity'])
      ->getMock();

    // Inject dependencies via reflection.
    $reflector = new \ReflectionClass($action);

    $senderProp = $reflector->getProperty('sender');
    $senderProp->setAccessible(TRUE);
    $senderProp->setValue($action, $this->sender);

    $configFactoryProp = $reflector->getProperty('configFactory');
    $configFactoryProp->setAccessible(TRUE);
    $configFactoryProp->setValue($action, $this->configFactory);

    $collectorProp = $reflector->getProperty('executionCollector');
    $collectorProp->setAccessible(TRUE);
    $collectorProp->setValue($action, $this->executionCollector);

    return $action;
  }

  /**
   * Tests resolveRecipient() strategy 1: webform_submission token parsing.
   *
   * @covers ::execute
   */
  public function testResolveRecipientStrategy1WebformSubmission(): void {
    $config = [
      'recipient_token' => '[webform_submission:values:citizen_cpr:raw]',
    ];

    $action = $this->createAction($config);

    // Create mock entity that has getElementData method.
    $mockEntity = $this->createMock(\stdClass::class);
    $mockEntity->method('getElementData')
      ->with('citizen_cpr')
      ->willReturn('1234567890');

    $action->method('eventEntity')->willReturn($mockEntity);

    $this->sender
      ->expects($this->once())
      ->method('send')
      ->willReturn(Result::success('tx-123'));

    $action->execute();
  }

  /**
   * Tests resolveRecipient() strategy 2: ECA token data lookup.
   *
   * @covers ::execute
   */
  public function testResolveRecipientStrategy2TokenDataLookup(): void {
    $config = [
      'recipient_token' => '[citizen_session:cpr]',
    ];

    $action = $this->createAction($config);
    $action->method('eventEntity')->willReturn(NULL);

    $this->tokenService
      ->method('getTokenData')
      ->willReturnMap([
        ['citizen_session:cpr', '0987654321'],
        ['sender_cvr_token', NULL],
      ]);

    $this->sender
      ->expects($this->once())
      ->method('send')
      ->willReturn(Result::success('tx-456'));

    $action->execute();
  }

  /**
   * Tests empty fallback when recipient cannot be resolved.
   *
   * @covers ::execute
   */
  public function testResolveRecipientEmptyFallback(): void {
    $config = [
      'recipient_token' => '[nonexistent:token]',
    ];

    $action = $this->createAction($config);
    $action->method('eventEntity')->willReturn(NULL);

    $this->tokenService
      ->method('getTokenData')
      ->willReturn(NULL);

    // Sender should NOT be called when recipient is empty.
    $this->sender
      ->expects($this->never())
      ->method('send');

    // Should record a "skipped" step.
    $this->executionCollector
      ->expects($this->atLeastOnce())
      ->method('addStep')
      ->with(
        $this->anything(),
        'Digital Post skipped',
        $this->anything(),
        'skipped',
        $this->anything(),
      );

    // Should set result token with RECIPIENT_EMPTY reason.
    $this->tokenService
      ->expects($this->atLeastOnce())
      ->method('addTokenData')
      ->with(
        'digital_post_result',
        $this->callback(function ($value) {
          return $value['success'] === FALSE
            && $value['reason_code'] === 'RECIPIENT_EMPTY';
        }),
      );

    $action->execute();
  }

  /**
   * Tests renderTemplate() with no tokens.
   *
   * @covers ::execute
   */
  public function testRenderTemplateWithoutTokens(): void {
    $config = [
      'recipient_token' => '[test:cpr]',
      'subject_template' => 'Plain Subject',
      'body_template' => '<p>Plain body without tokens</p>',
    ];

    $action = $this->createAction($config);
    $action->method('eventEntity')->willReturn(NULL);

    $this->tokenService
      ->method('getTokenData')
      ->willReturnMap([
        ['test:cpr', '1234567890'],
      ]);

    $this->sender
      ->expects($this->once())
      ->method('send')
      ->with($this->callback(function ($post) {
        return $post->subject === 'Plain Subject'
          && $post->body === '<p>Plain body without tokens</p>';
      }))
      ->willReturn(Result::success('tx-render'));

    $action->execute();
  }

  /**
   * Tests renderTemplate() with token replacement.
   *
   * @covers ::execute
   */
  public function testRenderTemplateWithTokens(): void {
    $config = [
      'recipient_token' => '[test:cpr]',
      'subject_template' => 'Afgørelse for [citizen:name]',
      'body_template' => '<p>Kære [citizen:name], se bilag.</p>',
    ];

    $action = $this->createAction($config);
    $action->method('eventEntity')->willReturn(NULL);

    $this->tokenService
      ->method('getTokenData')
      ->willReturnMap([
        ['test:cpr', '1234567890'],
        ['citizen:name', 'Test Borger'],
      ]);

    $this->sender
      ->expects($this->once())
      ->method('send')
      ->with($this->callback(function ($post) {
        return $post->subject === 'Afgørelse for Test Borger'
          && $post->body === '<p>Kære Test Borger, se bilag.</p>';
      }))
      ->willReturn(Result::success('tx-tokens'));

    $action->execute();
  }

  /**
   * Tests failure path writes correct result token.
   *
   * @covers ::execute
   */
  public function testFailurePathWritesCorrectResultToken(): void {
    $config = [
      'recipient_token' => '[test:cpr]',
      'result_token' => 'dp_result',
    ];

    $action = $this->createAction($config);
    $action->method('eventEntity')->willReturn(NULL);

    $this->tokenService
      ->method('getTokenData')
      ->willReturnMap([
        ['test:cpr', '1234567890'],
      ]);

    $failureResult = Result::failure(
      transactionId: 'tx-fail',
      reasonCode: Result::REASON_RECIPIENT_UNKNOWN,
      message: 'Recipient not registered',
    );

    $this->sender
      ->method('send')
      ->willReturn($failureResult);

    $this->tokenService
      ->expects($this->atLeastOnce())
      ->method('addTokenData')
      ->with(
        'dp_result',
        $this->callback(function ($value) {
          return $value['success'] === FALSE
            && $value['transaction_id'] === 'tx-fail'
            && $value['reason_code'] === Result::REASON_RECIPIENT_UNKNOWN
            && $value['message'] === 'Recipient not registered';
        }),
      );

    $action->execute();
  }

  /**
   * Tests success path writes correct result token.
   *
   * @covers ::execute
   */
  public function testSuccessPathWritesCorrectResultToken(): void {
    $config = [
      'recipient_token' => '[test:cpr]',
      'result_token' => 'my_result',
    ];

    $action = $this->createAction($config);
    $action->method('eventEntity')->willReturn(NULL);

    $this->tokenService
      ->method('getTokenData')
      ->willReturnMap([
        ['test:cpr', '1234567890'],
      ]);

    $successResult = Result::success('tx-success', 'Delivered');

    $this->sender
      ->method('send')
      ->willReturn($successResult);

    $this->tokenService
      ->expects($this->atLeastOnce())
      ->method('addTokenData')
      ->with(
        'my_result',
        $this->callback(function ($value) {
          return $value['success'] === TRUE
            && $value['transaction_id'] === 'tx-success'
            && $value['reason_code'] === NULL;
        }),
      );

    $action->execute();
  }

  /**
   * Tests that defaultConfiguration() includes expected keys.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfigurationIncludesExpectedKeys(): void {
    $action = $this->createAction([]);

    $defaults = $action->defaultConfiguration();

    $this->assertArrayHasKey('recipient_token', $defaults);
    $this->assertArrayHasKey('recipient_type', $defaults);
    $this->assertArrayHasKey('sender_cvr_token', $defaults);
    $this->assertArrayHasKey('subject_template', $defaults);
    $this->assertArrayHasKey('body_template', $defaults);
    $this->assertArrayHasKey('type', $defaults);
    $this->assertArrayHasKey('result_token', $defaults);
  }

}
