<?php

declare(strict_types=1);

namespace Drupal\aabenforms_digital_post\DigitalPost;

/**
 * Immutable result of a Digital Post send attempt. Either success with a
 * transaction id, or failure with a typed reason code.
 */
final class Result {

  public const SUCCESS = 'success';
  public const FAILURE = 'failure';

  // Reason codes (failures only).
  public const REASON_CERT_INVALID = 'CERT_INVALID';
  public const REASON_RECIPIENT_UNKNOWN = 'RECIPIENT_UNKNOWN';
  public const REASON_RECIPIENT_NOT_REACHABLE = 'RECIPIENT_NOT_REACHABLE';
  public const REASON_QUOTA = 'QUOTA';
  public const REASON_TRANSPORT = 'TRANSPORT';
  public const REASON_VALIDATION = 'VALIDATION';
  public const REASON_UNKNOWN = 'UNKNOWN';

  /**
   * Constructs a Result.
   *
   * @param string $status
   *   Result status (SUCCESS or FAILURE).
   * @param string $transactionId
   *   Transaction identifier.
   * @param string|null $reasonCode
   *   Failure reason code, or NULL on success.
   * @param string $message
   *   Human-readable result message.
   * @param string|null $rawResponse
   *   Raw transport response, or NULL if unavailable.
   */
  private function __construct(
    public readonly string $status,
    public readonly string $transactionId,
    public readonly ?string $reasonCode,
    public readonly string $message,
    public readonly ?string $rawResponse,
  ) {
  }

  /**
   * Creates a success Result.
   *
   * @param string $transactionId
   *   Transaction identifier.
   * @param string $message
   *   Optional human-readable success message.
   * @param string|null $rawResponse
   *   Optional raw transport response.
   *
   * @return self
   *   The success result.
   */
  public static function success(string $transactionId, string $message = '', ?string $rawResponse = NULL): self {
    return new self(
      status: self::SUCCESS,
      transactionId: $transactionId,
      reasonCode: NULL,
      message: $message,
      rawResponse: $rawResponse,
    );
  }

  /**
   * Creates a failure Result.
   *
   * @param string $transactionId
   *   Transaction identifier.
   * @param string $reasonCode
   *   One of the REASON_* constants describing the failure.
   * @param string $message
   *   Human-readable failure message.
   * @param string|null $rawResponse
   *   Optional raw transport response.
   *
   * @return self
   *   The failure result.
   */
  public static function failure(string $transactionId, string $reasonCode, string $message, ?string $rawResponse = NULL): self {
    return new self(
      status: self::FAILURE,
      transactionId: $transactionId,
      reasonCode: $reasonCode,
      message: $message,
      rawResponse: $rawResponse,
    );
  }

  /**
   * Returns true when the send succeeded.
   */
  public function isSuccess(): bool {
    return $this->status === self::SUCCESS;
  }

  /**
   * Audit-log-safe context array. Does NOT include the full rawResponse
   * (which may carry PII in the MeMo envelope); callers that want it can
   * read it explicitly.
   */
  public function auditContext(): array {
    return [
      'status' => $this->status,
      'transaction_id' => $this->transactionId,
      'reason_code' => $this->reasonCode,
      'message' => $this->message,
    ];
  }

}
