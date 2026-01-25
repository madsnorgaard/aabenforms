<?php

namespace Drupal\aabenforms_mitid\Service;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;

/**
 * Service for managing flow-scoped MitID sessions.
 *
 * Unlike traditional Drupal user sessions, MitID sessions are:
 * - Tied to workflow instances (not user accounts)
 * - Short-lived (15 minutes default)
 * - Auto-expiring (no permanent storage)
 * - GDPR compliant (deleted after workflow completion)
 */
class MitIdSessionManager {

  /**
   * Session expiration time (15 minutes).
   */
  protected const SESSION_EXPIRATION = 900;

  /**
   * The private tempstore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $tempStore;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger
   */
  protected AuditLogger $auditLogger;

  /**
   * Constructs a MitIdSessionManager.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store
   *   The private tempstore factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\aabenforms_core\Service\AuditLogger $audit_logger
   *   The audit logger.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store,
    TimeInterface $time,
    LoggerChannelFactoryInterface $logger_factory,
    AuditLogger $audit_logger
  ) {
    $this->tempStore = $temp_store;
    $this->time = $time;
    $this->logger = $logger_factory->get('aabenforms_mitid');
    $this->auditLogger = $audit_logger;
  }

  /**
   * Stores MitID session data for a workflow instance.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   * @param array $session_data
   *   The session data to store.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function storeSession(string $workflow_id, array $session_data): bool {
    try {
      $store = $this->tempStore->get('aabenforms_mitid');

      // Add metadata
      $session_data['created_at'] = $this->time->getRequestTime();
      $session_data['expires_at'] = $this->time->getRequestTime() + self::SESSION_EXPIRATION;
      $session_data['workflow_id'] = $workflow_id;

      // Store session
      $store->set($workflow_id, $session_data);

      $this->logger->info('MitID session stored for workflow: {workflow_id}', [
        'workflow_id' => $workflow_id,
      ]);

      // Audit log
      if (isset($session_data['cpr'])) {
        $this->auditLogger->logWorkflowAccess(
          $workflow_id,
          'mitid_session_created',
          'success',
          ['assurance_level' => $session_data['assurance_level'] ?? 'unknown']
        );
      }

      return TRUE;

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to store MitID session: {error}', [
        'error' => $e->getMessage(),
        'workflow_id' => $workflow_id,
      ]);
      return FALSE;
    }
  }

  /**
   * Retrieves MitID session data for a workflow instance.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   *
   * @return array|null
   *   The session data, or NULL if not found or expired.
   */
  public function getSession(string $workflow_id): ?array {
    try {
      $store = $this->tempStore->get('aabenforms_mitid');
      $session_data = $store->get($workflow_id);

      if (!$session_data) {
        return NULL;
      }

      // Check expiration
      $expiresAt = $session_data['expires_at'] ?? 0;
      if ($expiresAt < $this->time->getRequestTime()) {
        $this->logger->info('MitID session expired for workflow: {workflow_id}', [
          'workflow_id' => $workflow_id,
        ]);
        $this->deleteSession($workflow_id);
        return NULL;
      }

      return $session_data;

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to retrieve MitID session: {error}', [
        'error' => $e->getMessage(),
        'workflow_id' => $workflow_id,
      ]);
      return NULL;
    }
  }

  /**
   * Deletes MitID session data for a workflow instance.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  public function deleteSession(string $workflow_id): bool {
    try {
      $store = $this->tempStore->get('aabenforms_mitid');
      $store->delete($workflow_id);

      $this->logger->info('MitID session deleted for workflow: {workflow_id}', [
        'workflow_id' => $workflow_id,
      ]);

      // Audit log
      $this->auditLogger->logWorkflowAccess(
        $workflow_id,
        'mitid_session_deleted',
        'success',
        []
      );

      return TRUE;

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to delete MitID session: {error}', [
        'error' => $e->getMessage(),
        'workflow_id' => $workflow_id,
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if a valid MitID session exists for a workflow.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   *
   * @return bool
   *   TRUE if valid session exists, FALSE otherwise.
   */
  public function hasValidSession(string $workflow_id): bool {
    return $this->getSession($workflow_id) !== NULL;
  }

  /**
   * Gets CPR number from workflow session.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   *
   * @return string|null
   *   The CPR number, or NULL if not available.
   */
  public function getCprFromSession(string $workflow_id): ?string {
    $session = $this->getSession($workflow_id);
    return $session['cpr'] ?? NULL;
  }

  /**
   * Gets person data from workflow session.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   *
   * @return array|null
   *   The person data, or NULL if not available.
   */
  public function getPersonDataFromSession(string $workflow_id): ?array {
    $session = $this->getSession($workflow_id);

    if (!$session) {
      return NULL;
    }

    return [
      'cpr' => $session['cpr'] ?? NULL,
      'name' => $session['name'] ?? NULL,
      'given_name' => $session['given_name'] ?? NULL,
      'family_name' => $session['family_name'] ?? NULL,
      'birthdate' => $session['birthdate'] ?? NULL,
      'email' => $session['email'] ?? NULL,
      'assurance_level' => $session['assurance_level'] ?? NULL,
      'mitid_uuid' => $session['mitid_uuid'] ?? NULL,
    ];
  }

}
