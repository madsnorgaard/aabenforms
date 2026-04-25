<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Payroll forwarding service.
 *
 * V1 mirrors the aabenforms_digital_post fake_db pattern: every
 * approved claim is recorded to {aabenforms_payroll_log} with a
 * synthetic success receipt. A real Danish payroll API client (KMD
 * Lon, Silkeborg Data, etc.) decorates this service when those
 * contracts land. The PayrollPostAction never knows which transport
 * is wired - it just calls forward().
 */
class PayrollService {

  public const STATUS_SUCCESS = 'success';
  public const STATUS_FAILURE = 'failure';
  public const REASON_TRANSPORT = 'TRANSPORT';
  public const REASON_VALIDATION = 'VALIDATION';

  public function __construct(
    protected readonly Connection $database,
    protected readonly TimeInterface $time,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Records a payroll-forward attempt and returns the result.
   *
   * @param string $employee_id
   *   The employee identifier (CPR, employee number, email).
   * @param string $claim_type
   *   E.g. "mileage", "expense", "phone_subsidy".
   * @param int $amount_cents
   *   Amount in øre (DKK cents).
   * @param array $payload
   *   Free-form payload preserved as JSON in the log row.
   *
   * @return array
   *   ['transaction_id' => string, 'status' => self::STATUS_*, 'reason_code'
   *   => ?string, 'message' => string].
   */
  public function forward(string $employee_id, string $claim_type, int $amount_cents, array $payload = []): array {
    if ($employee_id === '') {
      return [
        'transaction_id' => '',
        'status' => self::STATUS_FAILURE,
        'reason_code' => self::REASON_VALIDATION,
        'message' => 'employee_id is empty',
      ];
    }

    $transaction_id = $this->generateTransactionId();
    $row = [
      'transaction_id' => $transaction_id,
      'mode' => 'fake_db',
      'employee_id_hash' => hash('sha256', $employee_id),
      'claim_type' => $claim_type !== '' ? $claim_type : 'unspecified',
      'amount_cents' => $amount_cents,
      'status' => self::STATUS_SUCCESS,
      'reason_code' => NULL,
      'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
      'response' => 'fake_db:synthetic-receipt',
      'created' => $this->time->getRequestTime(),
    ];
    try {
      $this->database->insert('aabenforms_payroll_log')->fields($row)->execute();
      Cache::invalidateTags(['aabenforms_dashboard:activity']);
      $this->logger->info('Payroll fake-db forward: tx=@tx claim=@claim amount=@amount', [
        '@tx' => $transaction_id,
        '@claim' => $claim_type,
        '@amount' => $amount_cents,
      ]);
      return [
        'transaction_id' => $transaction_id,
        'status' => self::STATUS_SUCCESS,
        'reason_code' => NULL,
        'message' => 'fake_db: payload recorded in aabenforms_payroll_log',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Payroll fake-db forward failed: @msg', ['@msg' => $e->getMessage()]);
      return [
        'transaction_id' => $transaction_id,
        'status' => self::STATUS_FAILURE,
        'reason_code' => self::REASON_TRANSPORT,
        'message' => 'fake_db write failed: ' . $e->getMessage(),
      ];
    }
  }

  /**
   * Returns the count of payroll log rows for the last $seconds.
   */
  public function recentCount(int $seconds): int {
    try {
      return (int) $this->database->select('aabenforms_payroll_log', 'l')
        ->condition('created', $this->time->getRequestTime() - $seconds, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * UUID v4 (good enough for an opaque receipt id).
   */
  protected function generateTransactionId(): string {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
  }

}
