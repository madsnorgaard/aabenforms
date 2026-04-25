<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Manages MED-style window-based elections (open / vote / tabulate / publish).
 *
 * Stateless. All persistence lives in {aabenforms_election} +
 * {aabenforms_election_ballot}. Voter privacy: ballots reference
 * SHA-256(election_id + cpr), never the raw CPR. Per-election
 * uniqueness is enforced by a UNIQUE index so duplicate ballots throw
 * at the DB layer regardless of how many code paths submit one.
 */
class ElectionService {

  public const STATUS_PENDING = 'pending';
  public const STATUS_OPEN = 'open';
  public const STATUS_CLOSED = 'closed';
  public const STATUS_PUBLISHED = 'published';

  public function __construct(
    protected readonly Connection $database,
    protected readonly TimeInterface $time,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Creates an election row.
   *
   * @param string $id
   *   Stable election identifier.
   * @param string $label
   *   Human-readable election label.
   * @param array $choices
   *   Ordered list of choices: [['index' => int, 'label' => string], ...].
   * @param int $opens_at
   *   Unix timestamp when voting starts.
   * @param int $closes_at
   *   Unix timestamp when voting ends.
   * @param string $description
   *   Optional long-form description.
   */
  public function create(string $id, string $label, array $choices, int $opens_at, int $closes_at, string $description = ''): void {
    $this->database->merge('aabenforms_election')
      ->key('id', $id)
      ->fields([
        'label' => mb_substr($label, 0, 255),
        'description' => $description,
        'choices' => json_encode(array_values($choices), JSON_UNESCAPED_UNICODE) ?: '[]',
        'opens_at' => $opens_at,
        'closes_at' => $closes_at,
        'status' => self::STATUS_PENDING,
        'created' => $this->time->getRequestTime(),
      ])
      ->execute();
    Cache::invalidateTags(['aabenforms_dashboard:overview', 'aabenforms_election:' . $id]);
  }

  /**
   * Transitions an election to the open status.
   *
   * Idempotent: calling twice is safe. Returns TRUE if a transition
   * happened (status was pending/closed/published) so callers can
   * distinguish the cron firing on already-open windows.
   */
  public function openWindow(string $id): bool {
    $row = $this->load($id);
    if (!$row || $row['status'] === self::STATUS_OPEN) {
      return FALSE;
    }
    $this->database->update('aabenforms_election')
      ->fields(['status' => self::STATUS_OPEN])
      ->condition('id', $id)
      ->execute();
    Cache::invalidateTags(['aabenforms_election:' . $id]);
    $this->logger->info('Election @id opened.', ['@id' => $id]);
    return TRUE;
  }

  /**
   * Tabulates ballots and transitions the election to closed.
   *
   * Returns the result rows even if already closed (idempotent re-tally).
   */
  public function closeWindow(string $id): array {
    $row = $this->load($id);
    if (!$row) {
      return [];
    }
    $tally = $this->tabulate($id);
    $this->database->update('aabenforms_election')
      ->fields([
        'status' => self::STATUS_CLOSED,
        'results' => json_encode($tally, JSON_UNESCAPED_UNICODE) ?: '[]',
      ])
      ->condition('id', $id)
      ->execute();
    Cache::invalidateTags(['aabenforms_election:' . $id]);
    $this->logger->info('Election @id closed; @count ballots tallied.', [
      '@id' => $id,
      '@count' => array_sum(array_column($tally, 'count')),
    ]);
    return $tally;
  }

  /**
   * Marks an election as publicly published.
   */
  public function publish(string $id): void {
    $this->database->update('aabenforms_election')
      ->fields(['status' => self::STATUS_PUBLISHED])
      ->condition('id', $id)
      ->execute();
    Cache::invalidateTags(['aabenforms_election:' . $id]);
  }

  /**
   * Records a ballot. Returns FALSE if the voter already cast one.
   *
   * @param string $election_id
   *   The election the ballot belongs to.
   * @param string $voter_hash
   *   SHA-256(election_id + cpr); never the raw CPR.
   * @param int $choice_index
   *   Index into the election's choices array.
   *
   * @return bool
   *   TRUE on insert, FALSE on duplicate.
   */
  public function recordBallot(string $election_id, string $voter_hash, int $choice_index): bool {
    try {
      $this->database->insert('aabenforms_election_ballot')
        ->fields([
          'election_id' => $election_id,
          'voter_hash' => $voter_hash,
          'choice_index' => $choice_index,
          'cast_at' => $this->time->getRequestTime(),
        ])
        ->execute();
      Cache::invalidateTags(['aabenforms_election:' . $election_id]);
      return TRUE;
    }
    catch (\Throwable) {
      // UNIQUE constraint violation is the only expected failure here.
      return FALSE;
    }
  }

  /**
   * Tells whether a voter has already cast a ballot.
   *
   * @return bool
   *   TRUE when a row exists for (election_id, voter_hash).
   */
  public function voterHasCast(string $election_id, string $voter_hash): bool {
    return (bool) $this->database->select('aabenforms_election_ballot', 'b')
      ->condition('election_id', $election_id)
      ->condition('voter_hash', $voter_hash)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Counts ballots per choice. Returns rows with index, label, count, percent.
   */
  public function tabulate(string $id): array {
    $row = $this->load($id);
    if (!$row) {
      return [];
    }
    $choices = json_decode($row['choices'] ?? '[]', TRUE);
    if (!is_array($choices)) {
      $choices = [];
    }
    $counts = $this->database->select('aabenforms_election_ballot', 'b')
      ->fields('b', ['choice_index'])
      ->condition('election_id', $id)
      ->execute()
      ->fetchAll(\PDO::FETCH_COLUMN);
    $totals = array_count_values(array_map('intval', $counts));
    $total = max(1, array_sum($totals));
    $rows = [];
    foreach ($choices as $choice) {
      $idx = (int) ($choice['index'] ?? 0);
      $count = (int) ($totals[$idx] ?? 0);
      $rows[] = [
        'index' => $idx,
        'label' => (string) ($choice['label'] ?? ('Choice ' . $idx)),
        'count' => $count,
        'percent' => round($count / $total * 100, 1),
      ];
    }
    return $rows;
  }

  /**
   * Loads an election row as an associative array, or NULL.
   */
  public function load(string $id): ?array {
    $row = $this->database->select('aabenforms_election', 'e')
      ->fields('e')
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();
    return $row ?: NULL;
  }

  /**
   * Lists elections, newest first.
   */
  public function list(int $limit = 50): array {
    return $this->database->select('aabenforms_election', 'e')
      ->fields('e')
      ->orderBy('created', 'DESC')
      ->range(0, $limit)
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Cron-friendly: returns elections whose window state is stale.
   *
   * @return array
   *   ['to_open' => string[], 'to_close' => string[]] of election ids.
   */
  public function findStaleWindows(): array {
    $now = $this->time->getRequestTime();
    $to_open = $this->database->select('aabenforms_election', 'e')
      ->fields('e', ['id'])
      ->condition('status', self::STATUS_PENDING)
      ->condition('opens_at', $now, '<=')
      ->execute()
      ->fetchCol();
    $to_close = $this->database->select('aabenforms_election', 'e')
      ->fields('e', ['id'])
      ->condition('status', self::STATUS_OPEN)
      ->condition('closes_at', $now, '<=')
      ->execute()
      ->fetchCol();
    return ['to_open' => $to_open, 'to_close' => $to_close];
  }

  /**
   * Generates the privacy-preserving voter hash.
   */
  public function voterHashFor(string $election_id, string $cpr): string {
    return hash('sha256', $election_id . ':' . $cpr);
  }

}
