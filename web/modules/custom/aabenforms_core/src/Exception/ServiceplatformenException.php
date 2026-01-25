<?php

namespace Drupal\aabenforms_core\Exception;

/**
 * Exception thrown when Serviceplatformen API requests fail.
 *
 * This exception provides structured error information for Danish government
 * service integration failures (SF1520 CPR, SF1530 CVR, SF1601 Digital Post).
 */
class ServiceplatformenException extends \RuntimeException {

  /**
   * The service that failed (e.g., 'SF1520', 'SF1530', 'SF1601').
   *
   * @var string
   */
  protected string $service;

  /**
   * The operation that failed (e.g., 'PersonLookup', 'CompanyLookup').
   *
   * @var string
   */
  protected string $operation;

  /**
   * Whether this error should trigger a retry.
   *
   * @var bool
   */
  protected bool $retryable;

  /**
   * Constructs a ServiceplatformenException.
   *
   * @param string $message
   *   The exception message.
   * @param string $service
   *   The service name (SF1520, SF1530, etc.).
   * @param string $operation
   *   The operation name.
   * @param bool $retryable
   *   Whether this error is retryable (timeout, connection error).
   * @param int $code
   *   The exception code.
   * @param \Throwable|null $previous
   *   The previous throwable for chaining.
   */
  public function __construct(
    string $message,
    string $service,
    string $operation,
    bool $retryable = FALSE,
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    parent::__construct($message, $code, $previous);
    $this->service = $service;
    $this->operation = $operation;
    $this->retryable = $retryable;
  }

  /**
   * Gets the service name.
   *
   * @return string
   *   The service name (e.g., 'SF1520').
   */
  public function getService(): string {
    return $this->service;
  }

  /**
   * Gets the operation name.
   *
   * @return string
   *   The operation name (e.g., 'PersonLookup').
   */
  public function getOperation(): string {
    return $this->operation;
  }

  /**
   * Checks if this error is retryable.
   *
   * @return bool
   *   TRUE if the request should be retried, FALSE otherwise.
   */
  public function isRetryable(): bool {
    return $this->retryable;
  }

}
