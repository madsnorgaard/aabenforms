<?php

namespace Drupal\Tests\aabenforms_mitid\Unit\Service;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests MitID session management.
 *
 * @coversDefaultClass \Drupal\aabenforms_mitid\Service\MitIdSessionManager
 * @group aabenforms_mitid
 */
class MitIdSessionManagerTest extends UnitTestCase {

  /**
   * The session manager service.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdSessionManager
   */
  protected MitIdSessionManager $sessionManager;

  /**
   * Mock private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tempStore;

  /**
   * Mock time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $time;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $auditLogger;

  /**
   * Current timestamp for testing.
   *
   * @var int
   */
  protected int $currentTime = 1706198400;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock dependencies.
    $this->tempStore = $this->createMock(PrivateTempStore::class);
    $tempStoreFactory = $this->createMock(PrivateTempStoreFactory::class);
    $tempStoreFactory->method('get')
      ->with('aabenforms_mitid')
      ->willReturn($this->tempStore);

    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')
      ->willReturn($this->currentTime);

    $this->logger = $this->createMock(LoggerInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')
      ->with('aabenforms_mitid')
      ->willReturn($this->logger);

    $this->auditLogger = $this->createMock(AuditLogger::class);

    // Create session manager with mocks.
    $this->sessionManager = new MitIdSessionManager(
      $tempStoreFactory,
      $this->time,
      $loggerFactory,
      $this->auditLogger
    );
  }

  /**
   * Tests storing a session successfully.
   *
   * @covers ::storeSession
   */
  public function testStoreSession(): void {
    $workflowId = 'workflow-123';
    $sessionData = [
      'cpr' => '0101001234',
      'name' => 'Test Testesen',
      'assurance_level' => 'substantial',
      'mitid_uuid' => 'uuid-123',
    ];

    // Expected stored data with metadata.
    $expectedData = $sessionData + [
      'created_at' => $this->currentTime,
      'expires_at' => $this->currentTime + 900,
      'workflow_id' => $workflowId,
    ];

    $this->tempStore->expects($this->once())
      ->method('set')
      ->with($workflowId, $expectedData);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'MitID session stored for workflow: {workflow_id}',
        ['workflow_id' => $workflowId]
      );

    $this->auditLogger->expects($this->once())
      ->method('logWorkflowAccess')
      ->with(
        $workflowId,
        'mitid_session_created',
        'success',
        ['assurance_level' => 'substantial']
      );

    $result = $this->sessionManager->storeSession($workflowId, $sessionData);
    $this->assertTrue($result);
  }

  /**
   * Tests storing session without CPR (no audit log).
   *
   * @covers ::storeSession
   */
  public function testStoreSessionWithoutCpr(): void {
    $workflowId = 'workflow-456';
    $sessionData = [
      'temp_data' => 'value',
    ];

    $this->tempStore->expects($this->once())
      ->method('set');

    $this->logger->expects($this->once())
      ->method('info');

    // Should NOT call audit logger without CPR.
    $this->auditLogger->expects($this->never())
      ->method('logWorkflowAccess');

    $result = $this->sessionManager->storeSession($workflowId, $sessionData);
    $this->assertTrue($result);
  }

  /**
   * Tests store session with exception.
   *
   * @covers ::storeSession
   */
  public function testStoreSessionWithException(): void {
    $workflowId = 'workflow-789';
    $sessionData = ['data' => 'value'];

    $this->tempStore->expects($this->once())
      ->method('set')
      ->willThrowException(new \Exception('Storage error'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Failed to store MitID session: {error}',
        [
          'error' => 'Storage error',
          'workflow_id' => $workflowId,
        ]
      );

    $result = $this->sessionManager->storeSession($workflowId, $sessionData);
    $this->assertFalse($result);
  }

  /**
   * Tests retrieving a valid session.
   *
   * @covers ::getSession
   */
  public function testGetValidSession(): void {
    $workflowId = 'workflow-valid';
    $sessionData = [
      'cpr' => '0101001234',
      'name' => 'Test Testesen',
      'created_at' => $this->currentTime - 300,
      'expires_at' => $this->currentTime + 600,
      'workflow_id' => $workflowId,
    ];

    $this->tempStore->expects($this->once())
      ->method('get')
      ->with($workflowId)
      ->willReturn($sessionData);

    $result = $this->sessionManager->getSession($workflowId);
    $this->assertEquals($sessionData, $result);
  }

  /**
   * Tests retrieving non-existent session.
   *
   * @covers ::getSession
   */
  public function testGetNonExistentSession(): void {
    $workflowId = 'workflow-missing';

    $this->tempStore->expects($this->once())
      ->method('get')
      ->with($workflowId)
      ->willReturn(NULL);

    $result = $this->sessionManager->getSession($workflowId);
    $this->assertNull($result);
  }

  /**
   * Tests retrieving expired session.
   *
   * @covers ::getSession
   */
  public function testGetExpiredSession(): void {
    $workflowId = 'workflow-expired';
    $sessionData = [
      'cpr' => '0101001234',
      'created_at' => $this->currentTime - 1000,
      'expires_at' => $this->currentTime - 100,
      'workflow_id' => $workflowId,
    ];

    $this->tempStore->expects($this->once())
      ->method('get')
      ->with($workflowId)
      ->willReturn($sessionData);

    // Logger will be called twice: once for expiration, once for deletion.
    $this->logger->expects($this->exactly(2))
      ->method('info');

    // Should delete expired session.
    $this->tempStore->expects($this->once())
      ->method('delete')
      ->with($workflowId);

    $result = $this->sessionManager->getSession($workflowId);
    $this->assertNull($result);
  }

  /**
   * Tests get session with exception.
   *
   * @covers ::getSession
   */
  public function testGetSessionWithException(): void {
    $workflowId = 'workflow-error';

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willThrowException(new \Exception('Retrieval error'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Failed to retrieve MitID session: {error}',
        [
          'error' => 'Retrieval error',
          'workflow_id' => $workflowId,
        ]
      );

    $result = $this->sessionManager->getSession($workflowId);
    $this->assertNull($result);
  }

  /**
   * Tests deleting a session successfully.
   *
   * @covers ::deleteSession
   */
  public function testDeleteSession(): void {
    $workflowId = 'workflow-delete';

    $this->tempStore->expects($this->once())
      ->method('delete')
      ->with($workflowId);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'MitID session deleted for workflow: {workflow_id}',
        ['workflow_id' => $workflowId]
      );

    $this->auditLogger->expects($this->once())
      ->method('logWorkflowAccess')
      ->with(
        $workflowId,
        'mitid_session_deleted',
        'success',
        []
      );

    $result = $this->sessionManager->deleteSession($workflowId);
    $this->assertTrue($result);
  }

  /**
   * Tests delete session with exception.
   *
   * @covers ::deleteSession
   */
  public function testDeleteSessionWithException(): void {
    $workflowId = 'workflow-delete-fail';

    $this->tempStore->expects($this->once())
      ->method('delete')
      ->willThrowException(new \Exception('Delete error'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        'Failed to delete MitID session: {error}',
        [
          'error' => 'Delete error',
          'workflow_id' => $workflowId,
        ]
      );

    $result = $this->sessionManager->deleteSession($workflowId);
    $this->assertFalse($result);
  }

  /**
   * Tests checking for valid session.
   *
   * @covers ::hasValidSession
   */
  public function testHasValidSessionTrue(): void {
    $workflowId = 'workflow-has-valid';
    $sessionData = [
      'cpr' => '0101001234',
      'expires_at' => $this->currentTime + 600,
    ];

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willReturn($sessionData);

    $result = $this->sessionManager->hasValidSession($workflowId);
    $this->assertTrue($result);
  }

  /**
   * Tests checking for invalid session.
   *
   * @covers ::hasValidSession
   */
  public function testHasValidSessionFalse(): void {
    $workflowId = 'workflow-no-valid';

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willReturn(NULL);

    $result = $this->sessionManager->hasValidSession($workflowId);
    $this->assertFalse($result);
  }

  /**
   * Tests getting CPR from session.
   *
   * @covers ::getCprFromSession
   */
  public function testGetCprFromSession(): void {
    $workflowId = 'workflow-cpr';
    $sessionData = [
      'cpr' => '0101001234',
      'name' => 'Test Testesen',
      'expires_at' => $this->currentTime + 600,
    ];

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willReturn($sessionData);

    $result = $this->sessionManager->getCprFromSession($workflowId);
    $this->assertEquals('0101001234', $result);
  }

  /**
   * Tests getting CPR from missing session.
   *
   * @covers ::getCprFromSession
   */
  public function testGetCprFromMissingSession(): void {
    $workflowId = 'workflow-no-cpr';

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willReturn(NULL);

    $result = $this->sessionManager->getCprFromSession($workflowId);
    $this->assertNull($result);
  }

  /**
   * Tests getting CPR from session without CPR field.
   *
   * @covers ::getCprFromSession
   */
  public function testGetCprFromSessionWithoutCprField(): void {
    $workflowId = 'workflow-no-cpr-field';
    $sessionData = [
      'name' => 'Test Testesen',
      'expires_at' => $this->currentTime + 600,
    ];

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willReturn($sessionData);

    $result = $this->sessionManager->getCprFromSession($workflowId);
    $this->assertNull($result);
  }

  /**
   * Tests getting person data from session.
   *
   * @covers ::getPersonDataFromSession
   */
  public function testGetPersonDataFromSession(): void {
    $workflowId = 'workflow-person';
    $sessionData = [
      'cpr' => '0101001234',
      'name' => 'Test Testesen',
      'given_name' => 'Test',
      'family_name' => 'Testesen',
      'birthdate' => '2000-01-01',
      'email' => 'test@example.dk',
      'assurance_level' => 'substantial',
      'mitid_uuid' => 'uuid-123',
      'expires_at' => $this->currentTime + 600,
    ];

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willReturn($sessionData);

    $result = $this->sessionManager->getPersonDataFromSession($workflowId);

    $expected = [
      'cpr' => '0101001234',
      'name' => 'Test Testesen',
      'given_name' => 'Test',
      'family_name' => 'Testesen',
      'birthdate' => '2000-01-01',
      'email' => 'test@example.dk',
      'assurance_level' => 'substantial',
      'mitid_uuid' => 'uuid-123',
    ];

    $this->assertEquals($expected, $result);
  }

  /**
   * Tests getting person data from partial session.
   *
   * @covers ::getPersonDataFromSession
   */
  public function testGetPersonDataFromPartialSession(): void {
    $workflowId = 'workflow-partial';
    $sessionData = [
      'cpr' => '0101001234',
      'name' => 'Test Testesen',
      'expires_at' => $this->currentTime + 600,
    ];

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willReturn($sessionData);

    $result = $this->sessionManager->getPersonDataFromSession($workflowId);

    $expected = [
      'cpr' => '0101001234',
      'name' => 'Test Testesen',
      'given_name' => NULL,
      'family_name' => NULL,
      'birthdate' => NULL,
      'email' => NULL,
      'assurance_level' => NULL,
      'mitid_uuid' => NULL,
    ];

    $this->assertEquals($expected, $result);
  }

  /**
   * Tests getting person data from missing session.
   *
   * @covers ::getPersonDataFromSession
   */
  public function testGetPersonDataFromMissingSession(): void {
    $workflowId = 'workflow-no-person';

    $this->tempStore->expects($this->once())
      ->method('get')
      ->willReturn(NULL);

    $result = $this->sessionManager->getPersonDataFromSession($workflowId);
    $this->assertNull($result);
  }

}
