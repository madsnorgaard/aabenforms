<?php

namespace Drupal\Tests\aabenforms_workflows\Unit\Plugin\Action;

use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\aabenforms_workflows\Plugin\Action\MitIdValidateAction;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface as EcaTokenInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MitIdValidateAction ECA plugin.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\MitIdValidateAction
 * @group              aabenforms_workflows
 */
class MitIdValidateActionTest extends UnitTestCase
{

    /**
     * The MitIdValidateAction plugin.
     *
     * @var \Drupal\aabenforms_workflows\Plugin\Action\MitIdValidateAction
     */
    protected MitIdValidateAction $action;

    /**
     * Mock MitID session manager.
     *
     * @var \Drupal\aabenforms_mitid\Service\MitIdSessionManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $sessionManager;

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
     * Mock entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityTypeManager;

    /**
     * Mock current user.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currentUser;

    /**
     * Mock time service.
     *
     * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $time;

    /**
     * Mock ECA state service.
     *
     * @var \Drupal\eca\EcaState|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $ecaState;

    /**
     * Token storage for testing.
     *
     * @var array
     */
    protected array $tokenStorage = [];

    /**
     * Current timestamp for testing.
     *
     * @var int
     */
    protected int $currentTime = 1706198400;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock all ECA ActionBase dependencies.
        $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
        $this->currentUser = $this->createMock(AccountProxyInterface::class);
        $this->time = $this->createMock(TimeInterface::class);
        $this->ecaState = $this->createMock(EcaState::class);
        $this->logger = $this->createMock(LoggerChannelInterface::class);

        // Mock ECA token services.
        $this->tokenServices = $this->createMock(EcaTokenInterface::class);
        $this->tokenServices->method('getTokenData')
            ->willReturnCallback(
                function ($name) {
                    return $this->tokenStorage[$name] ?? null;
                }
            );
        $this->tokenServices->method('addTokenData')
            ->willReturnCallback(
                function ($name, $value) {
                    $this->tokenStorage[$name] = $value;
                    return $this->tokenServices;
                }
            );

        // Mock session manager.
        $this->sessionManager = $this->createMock(MitIdSessionManager::class);

        // Create action instance with all ECA dependencies.
        $configuration = [
        'workflow_id_token' => 'workflow_id',
        'result_token' => 'mitid_valid',
        'session_data_token' => 'mitid_session',
        ];

        $this->action = new MitIdValidateAction(
            $configuration,
            'aabenforms_mitid_validate',
            [],
            $this->entityTypeManager,
            $this->tokenServices,
            $this->currentUser,
            $this->time,
            $this->ecaState,
            $this->logger
        );

        // Inject session manager using reflection.
        $reflectionClass = new \ReflectionClass($this->action);
        $sessionManagerProperty = $reflectionClass->getProperty('sessionManager');
        $sessionManagerProperty->setAccessible(true);
        $sessionManagerProperty->setValue($this->action, $this->sessionManager);
    }

    /**
     * Tests validation with a valid MitID session.
     *
     * @covers ::execute
     */
    public function testValidSession(): void
    {
        $workflowId = 'workflow-123';
        $sessionData = [
        'cpr' => '0101001234',
        'name' => 'Test Testesen',
        'assurance_level' => 'substantial',
        'created_at' => time() - 300,
        'expires_at' => time() + 600,
        ];

        // Set workflow ID token.
        $this->tokenStorage['workflow_id'] = $workflowId;

        // Mock session manager to return valid session.
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->with($workflowId)
            ->willReturn($sessionData);

        // Expect success log.
        $this->logger->expects($this->once())
            ->method('info');

        // Execute action.
        $this->action->execute();

        // Verify result tokens.
        $this->assertTrue($this->tokenStorage['mitid_valid'], 'mitid_valid token should be TRUE');
        $this->assertEquals($sessionData, $this->tokenStorage['mitid_session'], 'mitid_session should contain session data');
    }

    /**
     * Tests validation with an expired MitID session.
     *
     * @covers ::execute
     */
    public function testExpiredSession(): void
    {
        $workflowId = 'workflow-expired';
        $sessionData = [
        'cpr' => '0101001234',
        'name' => 'Test Testesen',
        'created_at' => time() - 1000,
        'expires_at' => time() - 100,
        ];

        // Set workflow ID token.
        $this->tokenStorage['workflow_id'] = $workflowId;

        // Mock session manager to return expired session.
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->with($workflowId)
            ->willReturn($sessionData);

        // Expect warning log for expired session.
        $this->logger->expects($this->once())
            ->method('warning');

        // Execute action.
        $this->action->execute();

        // Verify result is FALSE.
        $this->assertFalse($this->tokenStorage['mitid_valid']);
    }

    /**
     * Tests validation with missing MitID session.
     *
     * @covers ::execute
     */
    public function testMissingSession(): void
    {
        $workflowId = 'workflow-missing';

        // Set workflow ID token.
        $this->tokenStorage['workflow_id'] = $workflowId;

        // Mock session manager to return NULL (no session).
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->with($workflowId)
            ->willReturn(null);

        // Expect warning log for missing session.
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'MitID validation failed: No session found for workflow {workflow_id}',
                ['workflow_id' => $workflowId, 'action' => 'aabenforms_mitid_validate']
            );

        // Execute action.
        $this->action->execute();

        // Verify result is FALSE.
        $this->assertFalse($this->tokenStorage['mitid_valid']);
    }

    /**
     * Tests CPR extraction from session data.
     *
     * @covers ::execute
     */
    public function testCprExtraction(): void
    {
        $workflowId = 'workflow-cpr';
        $expectedCpr = '0101001234';
        $sessionData = [
        'cpr' => $expectedCpr,
        'name' => 'Test Testesen',
        'given_name' => 'Test',
        'family_name' => 'Testesen',
        'birthdate' => '2000-01-01',
        'email' => 'test@example.dk',
        'assurance_level' => 'substantial',
        'mitid_uuid' => 'uuid-123',
        'created_at' => time() - 300,
        'expires_at' => time() + 600,
        ];

        // Set workflow ID token.
        $this->tokenStorage['workflow_id'] = $workflowId;

        // Mock session manager.
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->with($workflowId)
            ->willReturn($sessionData);

        // Execute action.
        $this->action->execute();

        // Verify session data token contains CPR.
        $this->assertArrayHasKey('mitid_session', $this->tokenStorage, 'mitid_session token should be set');
        $storedSession = $this->tokenStorage['mitid_session'];
        $this->assertIsArray($storedSession, 'Session data should be an array');
        $this->assertArrayHasKey('cpr', $storedSession, 'Session should contain CPR');
        $this->assertEquals($expectedCpr, $storedSession['cpr'], 'CPR should match');

        // Verify all person data is preserved.
        $this->assertEquals('Test Testesen', $storedSession['name']);
        $this->assertEquals('substantial', $storedSession['assurance_level']);
        $this->assertEquals('uuid-123', $storedSession['mitid_uuid']);
    }

    /**
     * Tests error handling and event dispatching.
     *
     * @covers ::execute
     */
    public function testErrorHandling(): void
    {
        $workflowId = 'workflow-error';

        // Set workflow ID token.
        $this->tokenStorage['workflow_id'] = $workflowId;

        // Mock session manager to throw exception.
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->with($workflowId)
            ->willThrowException(new \Exception('Database connection error'));

        // Expect error log.
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Action failed'),
                $this->callback(
                    function ($context) {
                        return isset($context['message']) &&
                         $context['message'] === 'Database connection error' &&
                         isset($context['context']) &&
                         $context['context'] === 'Validating MitID session';
                    }
                )
            );

        // Execute action.
        $this->action->execute();

        // Verify result is FALSE on exception.
        $this->assertFalse($this->tokenStorage['mitid_valid']);
    }

    /**
     * Tests validation without workflow ID.
     *
     * @covers ::execute
     */
    public function testMissingWorkflowId(): void
    {
        // Don't set workflow_id token (simulate missing context).

        // Expect warning log.
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'MitID validation failed: No workflow ID provided',
                ['action' => 'aabenforms_mitid_validate']
            );

        // Session manager should NOT be called.
        $this->sessionManager->expects($this->never())
            ->method('getSession');

        // Execute action.
        $this->action->execute();

        // Verify result is FALSE.
        $this->assertFalse($this->tokenStorage['mitid_valid']);
    }

    /**
     * Tests configuration form default values.
     *
     * @covers ::defaultConfiguration
     */
    public function testDefaultConfiguration(): void
    {
        $defaults = $this->action->defaultConfiguration();

        $this->assertArrayHasKey('workflow_id_token', $defaults);
        $this->assertEquals('workflow_id', $defaults['workflow_id_token']);

        $this->assertArrayHasKey('result_token', $defaults);
        $this->assertEquals('mitid_valid', $defaults['result_token']);

        $this->assertArrayHasKey('session_data_token', $defaults);
        $this->assertEquals('mitid_session', $defaults['session_data_token']);
    }

}
