<?php

declare(strict_types=1);

namespace Drupal\aabenforms_core\Dashboard;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Builds the dashboard's "Recent activity" feed.
 *
 * Pulls a unified feed from the Digital Post log + audit log, sorted by
 * timestamp desc, limited to 30 rows. The feed supports four filter
 * scopes via a query parameter (?filter=...): all, digital_post, audit,
 * errors. Default scope per the UX brief is errors+24h with auto-pivot
 * to "last 10 events" if zero errors.
 */
class RecentActivityBuilder {

  use StringTranslationTrait;

  public const FILTERS = ['all', 'digital_post', 'audit', 'errors'];
  public const DEFAULT_FILTER = 'errors';
  public const ROW_LIMIT = 30;

  public function __construct(
    protected readonly Connection $database,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * @param string $filter
   *   One of self::FILTERS.
   *
   * @return array{rows: array, pivoted: bool, filter: string}
   *   rows: normalized event rows;
   *   pivoted: true if scope was widened because no errors found;
   *   filter: actual filter applied.
   */
  public function build(string $filter): array {
    if (!in_array($filter, self::FILTERS, TRUE)) {
      $filter = self::DEFAULT_FILTER;
    }

    $rows = $this->fetchRows($filter);
    $pivoted = FALSE;

    // Errors-default empty pivot per UX brief.
    if ($filter === 'errors' && empty($rows)) {
      $rows = $this->fetchRows('all');
      $pivoted = TRUE;
    }

    return [
      'rows' => $rows,
      'pivoted' => $pivoted,
      'filter' => $filter,
    ];
  }

  protected function fetchRows(string $filter): array {
    $rows = [];
    $now = $this->time->getRequestTime();
    $since24h = $now - 86400;

    $needDigitalPost = in_array($filter, ['all', 'digital_post', 'errors'], TRUE);
    $needAudit = in_array($filter, ['all', 'audit', 'errors'], TRUE);

    if ($needDigitalPost) {
      try {
        $q = $this->database->select('aabenforms_digital_post_log', 'l')
          ->fields('l', ['transaction_id', 'mode', 'subject', 'status', 'reason_code', 'created']);
        if ($filter === 'errors') {
          $q->condition('l.status', 'failure');
          $q->condition('l.created', $since24h, '>=');
        }
        $q->orderBy('l.created', 'DESC');
        $q->range(0, self::ROW_LIMIT);
        foreach ($q->execute() as $r) {
          $rows[] = [
            'kind' => 'digital_post',
            'created' => (int) $r->created,
            'status' => $r->status,
            'tone' => $r->status === 'success' ? 'success' : 'danger',
            'message' => $r->subject ?: $this->t('(no subject)'),
            'context' => $r->mode . ' · tx=' . substr($r->transaction_id, 0, 8) . ($r->reason_code ? ' · ' . $r->reason_code : ''),
          ];
        }
      }
      catch (\Throwable) {
        // Log table missing.
      }
    }

    if ($needAudit) {
      try {
        $q = $this->database->select('aabenforms_audit_log', 'l')
          ->fields('l', ['action', 'status', 'purpose', 'timestamp']);
        if ($filter === 'errors') {
          $q->condition('l.status', 'failure');
          $q->condition('l.timestamp', $since24h, '>=');
        }
        $q->orderBy('l.timestamp', 'DESC');
        $q->range(0, self::ROW_LIMIT);
        foreach ($q->execute() as $r) {
          $rows[] = [
            'kind' => 'audit',
            'created' => (int) $r->timestamp,
            'status' => $r->status,
            'tone' => $r->status === 'success' ? 'neutral' : 'danger',
            'message' => $r->action,
            'context' => $r->purpose,
          ];
        }
      }
      catch (\Throwable) {
        // Audit table missing.
      }
    }

    usort($rows, static fn ($a, $b) => $b['created'] <=> $a['created']);
    return array_slice($rows, 0, self::ROW_LIMIT);
  }

}
