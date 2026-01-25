<?php

namespace Drupal\aabenforms_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * GDPR-compliant audit logging service for sensitive data access.
 *
 * Logs all CPR/CVR lookups and personal data access for compliance auditing.
 * Required by Danish data protection regulations.
 *
 * Audit logs include:
 * - User ID (or workflow ID for flow-scoped access)
 * - Timestamp
 * - Action performed (e.g., 'cpr_lookup', 'cvr_lookup')
 * - Identifier queried (CPR/CVR number - hashed)
 * - Purpose (from workflow context)
 * - IP address
 * - Result status
 */
class AuditLogger {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

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
   * Constructs an AuditLogger.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    Connection $database,
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
    TimeInterface $time,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->time = $time;
    $this->logger = $logger_factory->get('aabenforms_audit');
  }

  /**
   * Logs CPR lookup access.
   *
   * @param string $cpr
   *   The CPR number queried.
   * @param string $purpose
   *   The purpose of the lookup (e.g., 'citizen_complaint_workflow').
   * @param string $status
   *   The result status ('success', 'failure', 'protected_person').
   * @param array $context
   *   Additional context (workflow_id, tenant_id, etc.).
   */
  public function logCprLookup(string $cpr, string $purpose, string $status, array $context = []): void {
    $this->log('cpr_lookup', $cpr, $purpose, $status, $context);
  }

  /**
   * Logs CVR lookup access.
   *
   * @param string $cvr
   *   The CVR number queried.
   * @param string $purpose
   *   The purpose of the lookup.
   * @param string $status
   *   The result status.
   * @param array $context
   *   Additional context.
   */
  public function logCvrLookup(string $cvr, string $purpose, string $status, array $context = []): void {
    $this->log('cvr_lookup', $cvr, $purpose, $status, $context);
  }

  /**
   * Logs workflow data access.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   * @param string $action
   *   The action performed (e.g., 'view', 'update', 'delete').
   * @param string $status
   *   The result status.
   * @param array $context
   *   Additional context.
   */
  public function logWorkflowAccess(string $workflow_id, string $action, string $status, array $context = []): void {
    $this->log('workflow_access', $workflow_id, $action, $status, $context);
  }

  /**
   * Logs an audit event.
   *
   * @param string $action
   *   The action type (e.g., 'cpr_lookup', 'cvr_lookup', 'workflow_access').
   * @param string $identifier
   *   The identifier queried (CPR, CVR, workflow ID).
   * @param string $purpose
   *   The purpose or sub-action.
   * @param string $status
   *   The result status.
   * @param array $context
   *   Additional context data.
   */
  protected function log(string $action, string $identifier, string $purpose, string $status, array $context): void {
    $request = $this->requestStack->getCurrentRequest();
    $ip_address = $request ? $request->getClientIp() : 'CLI';

    $entry = [
      'uid' => $this->currentUser->id(),
      'action' => $action,
    // Hash for privacy.
      'identifier_hash' => hash('sha256', $identifier),
      'purpose' => $purpose,
      'status' => $status,
      'ip_address' => $ip_address,
      'context' => json_encode($context),
      'timestamp' => $this->time->getRequestTime(),
    ];

    try {
      $this->database->insert('aabenforms_audit_log')
        ->fields($entry)
        ->execute();

      $this->logger->info('Audit: {action} | {purpose} | {status}', [
        'action' => $action,
        'purpose' => $purpose,
        'status' => $status,
        'uid' => $entry['uid'],
        'ip_address' => $ip_address,
      ]);

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to write audit log: {error}', [
        'error' => $e->getMessage(),
        'action' => $action,
      ]);
    }
  }

  /**
   * Retrieves audit log entries.
   *
   * @param array $conditions
   *   Query conditions (e.g., ['action' => 'cpr_lookup', 'uid' => 123]).
   * @param int $limit
   *   Maximum number of entries to return.
   * @param int $offset
   *   Query offset for pagination.
   *
   * @return array
   *   Array of audit log entries.
   */
  public function getAuditLog(array $conditions = [], int $limit = 100, int $offset = 0): array {
    $query = $this->database->select('aabenforms_audit_log', 'al')
      ->fields('al')
      ->orderBy('timestamp', 'DESC')
      ->range($offset, $limit);

    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

}
