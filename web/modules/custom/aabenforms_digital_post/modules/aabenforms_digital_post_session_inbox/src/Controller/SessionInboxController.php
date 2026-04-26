<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post_session_inbox\Controller;

use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns recent Digital Post log rows for a MitID session's recipient.
 *
 * Capability model: the workflow session id (15-min tempstore) is the only
 * thing accepted. The controller resolves the session's CPR server-side,
 * hashes it, and queries the log by hash. The CPR never appears in request
 * or response. When the session is absent or expired the response is `[]`
 * with HTTP 200 - the demo frontend renders an empty inbox cleanly without
 * a 404 spike in the browser console.
 */
final class SessionInboxController extends ControllerBase {

  private const DEFAULT_LIMIT = 5;
  private const MAX_LIMIT = 20;

  /**
   * Constructs a SessionInboxController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\aabenforms_mitid\Service\MitIdSessionManager $sessionManager
   *   The MitID session manager - resolves CPR from the workflow_id.
   */
  public function __construct(
    private readonly Connection $database,
    private readonly MitIdSessionManager $sessionManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
      $container->get('aabenforms_mitid.session_manager'),
    );
  }

  /**
   * GET /api/digital-post/recent?session={workflow_id}&limit=N.
   */
  public function getRecent(Request $request): JsonResponse {
    $sessionId = (string) $request->query->get('session', '');
    $limit = (int) $request->query->get('limit', self::DEFAULT_LIMIT);
    if ($limit < 1) {
      $limit = self::DEFAULT_LIMIT;
    }
    if ($limit > self::MAX_LIMIT) {
      $limit = self::MAX_LIMIT;
    }

    if ($sessionId === '') {
      return new JsonResponse(['data' => []]);
    }

    $cpr = $this->sessionManager->getCprFromSession($sessionId);
    if (!$cpr) {
      return new JsonResponse(['data' => []]);
    }

    // Match Recipient::identifierHash() in aabenforms_digital_post: it
    // hashes "{type}:{digits}" not just the digits. Stay aligned with the
    // log writer or the join misses every row.
    $hash = hash('sha256', 'cpr:' . preg_replace('/\D+/', '', $cpr));

    $rows = $this->database->select('aabenforms_digital_post_log', 'l')
      ->fields('l', [
        'transaction_id',
        'subject',
        'status',
        'payload',
        'created',
      ])
      ->condition('l.recipient_type', 'cpr')
      ->condition('l.recipient_identifier_hash', $hash)
      ->orderBy('l.created', 'DESC')
      ->range(0, $limit)
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
      $payload = json_decode((string) ($row['payload'] ?? '{}'), TRUE) ?: [];
      $bodyRaw = is_string($payload['body'] ?? NULL) ? $payload['body'] : '';
      $data[] = [
        'transaction_id' => $row['transaction_id'] ?? '',
        'subject' => $row['subject'] ?? '',
        'body_html' => Xss::filterAdmin($bodyRaw),
        'status' => $row['status'] ?? '',
        'created' => (int) ($row['created'] ?? 0),
        'created_iso' => gmdate('c', (int) ($row['created'] ?? 0)),
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

}
