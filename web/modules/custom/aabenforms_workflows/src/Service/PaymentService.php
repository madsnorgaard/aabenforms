<?php

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Mock payment service for development and demos.
 *
 * This service simulates payment processing via Nets Easy payment gateway
 * for development and demonstration purposes. In production, this would be
 * replaced with actual Nets Easy API integration.
 *
 * @see https://developer.nexigroup.com/nexi-checkout/en-EU/api/
 */
class PaymentService {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a PaymentService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Processes a payment transaction.
   *
   * @param array $payment_data
   *   Payment data containing:
   *   - amount: int - Amount in øre (Danish cents, e.g., 10000 = 100 DKK)
   *   - currency: string - Currency code (default: 'DKK')
   *   - order_id: string - Unique order identifier
   *   - payment_method: string - Payment method (e.g., 'nets_easy', 'card')
   *   - description: string - Optional payment description.
   *
   * @return array
   *   Payment result containing:
   *   - status: string - 'success' or 'failed'
   *   - payment_id: string - Unique payment identifier
   *   - transaction_id: string - Transaction reference
   *   - amount: int - Processed amount
   *   - currency: string - Currency code
   *   - timestamp: int - Unix timestamp
   *   - error: string - Error message (if failed)
   */
  public function processPayment(array $payment_data): array {
    // Validate required fields.
    if (empty($payment_data['amount']) || empty($payment_data['order_id'])) {
      return [
        'status' => 'failed',
        'error' => 'Missing required payment data (amount or order_id)',
      ];
    }

    // Simulate processing delay (0.5 seconds).
    usleep(500000);

    // Mock successful payment 90% of the time.
    $success = (rand(1, 10) <= 9);

    if ($success) {
      $result = [
        'status' => 'success',
        'payment_id' => 'PAY-' . uniqid() . '-' . time(),
        'transaction_id' => 'TXN-' . strtoupper(bin2hex(random_bytes(8))),
        'amount' => $payment_data['amount'],
        'currency' => $payment_data['currency'] ?? 'DKK',
        'timestamp' => time(),
        'payment_method' => $payment_data['payment_method'] ?? 'nets_easy',
      ];

      $this->logger->info('Mock payment processed successfully: @payment_id for order @order_id (amount: @amount @currency)', [
        '@payment_id' => $result['payment_id'],
        '@order_id' => $payment_data['order_id'],
        '@amount' => $result['amount'] / 100,
        '@currency' => $result['currency'],
      ]);

      return $result;
    }
    else {
      // Simulate various error scenarios.
      $errors = [
        'Insufficient funds',
        'Card declined',
        'Invalid card number',
        'Card expired',
        'Transaction timeout',
      ];

      $error = $errors[array_rand($errors)] . ' (mock error)';

      $this->logger->warning('Mock payment failed for order @order_id: @error', [
        '@order_id' => $payment_data['order_id'],
        '@error' => $error,
      ]);

      return [
        'status' => 'failed',
        'error' => $error,
        'order_id' => $payment_data['order_id'],
        'timestamp' => time(),
      ];
    }
  }

  /**
   * Refunds a payment transaction.
   *
   * @param string $payment_id
   *   The payment ID to refund.
   * @param int|null $amount
   *   Optional partial refund amount in øre. If NULL, full refund.
   * @param string|null $reason
   *   Optional refund reason.
   *
   * @return array
   *   Refund result containing:
   *   - status: string - 'success' or 'failed'
   *   - refund_id: string - Unique refund identifier
   *   - payment_id: string - Original payment ID
   *   - amount: int - Refunded amount
   *   - timestamp: int - Unix timestamp
   *   - error: string - Error message (if failed)
   */
  public function refundPayment(string $payment_id, ?int $amount = NULL, ?string $reason = NULL): array {
    // Simulate processing delay.
    usleep(300000);

    // Mock successful refund 95% of the time.
    $success = (rand(1, 20) <= 19);

    if ($success) {
      $result = [
        'status' => 'success',
        'refund_id' => 'REF-' . uniqid() . '-' . time(),
        'payment_id' => $payment_id,
        'amount' => $amount,
        'reason' => $reason,
        'timestamp' => time(),
      ];

      $this->logger->info('Mock payment refund processed: @refund_id for payment @payment_id', [
        '@refund_id' => $result['refund_id'],
        '@payment_id' => $payment_id,
      ]);

      return $result;
    }
    else {
      $error = 'Refund failed - payment already refunded (mock error)';

      $this->logger->warning('Mock refund failed for payment @payment_id: @error', [
        '@payment_id' => $payment_id,
        '@error' => $error,
      ]);

      return [
        'status' => 'failed',
        'error' => $error,
        'payment_id' => $payment_id,
        'timestamp' => time(),
      ];
    }
  }

  /**
   * Retrieves payment status.
   *
   * @param string $payment_id
   *   The payment ID to check.
   *
   * @return array
   *   Payment status information.
   */
  public function getPaymentStatus(string $payment_id): array {
    // Simulate API call delay.
    usleep(200000);

    // Mock payment status - for demo purposes, assume all payments are completed.
    return [
      'payment_id' => $payment_id,
      'status' => 'completed',
      'timestamp' => time() - rand(60, 3600),
    ];
  }

  /**
   * Creates a payment checkout session.
   *
   * This would create a Nets Easy checkout session in production.
   * For mock purposes, returns a fake checkout URL.
   *
   * @param array $checkout_data
   *   Checkout session data.
   *
   * @return array
   *   Checkout session information.
   */
  public function createCheckoutSession(array $checkout_data): array {
    $checkout_id = 'CHK-' . uniqid();

    return [
      'checkout_id' => $checkout_id,
      'checkout_url' => 'https://test.checkout.dibspayment.eu/v1/checkout/' . $checkout_id,
      'expires_at' => time() + 3600,
    ];
  }

}
