<?php

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Mock SMS service for development and demos.
 *
 * This service simulates SMS sending via Danish SMS gateway (e.g., SMS1919,
 * GatewayAPI) for development and demonstration purposes.
 *
 * @see https://gatewayapi.com/
 */
class SmsService {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs an SmsService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Sends an SMS message.
   *
   * @param string $phone
   *   Phone number in international format (e.g., +4512345678).
   * @param string $message
   *   SMS message content (max 160 characters for single SMS).
   * @param array $options
   *   Optional settings:
   *   - sender: string - Sender name/number
   *   - priority: string - 'normal' or 'high'
   *   - delivery_report: bool - Request delivery report.
   *
   * @return array
   *   Result containing:
   *   - status: string - 'sent', 'queued', or 'failed'
   *   - message_id: string - Unique message identifier
   *   - phone: string - Recipient phone number
   *   - segments: int - Number of SMS segments
   *   - timestamp: int - Unix timestamp
   *   - error: string - Error message (if failed)
   */
  public function sendSms(string $phone, string $message, array $options = []): array {
    // Validate phone number format.
    if (!preg_match('/^\+45\d{8}$/', $phone)) {
      return [
        'status' => 'failed',
        'error' => 'Invalid Danish phone number format. Expected: +45xxxxxxxx',
        'phone' => $phone,
      ];
    }

    // Validate message length.
    $length = mb_strlen($message);
    if ($length === 0) {
      return [
        'status' => 'failed',
        'error' => 'Message cannot be empty',
      ];
    }

    if ($length > 1600) {
      return [
        'status' => 'failed',
        'error' => 'Message too long (max 1600 characters)',
      ];
    }

    // Calculate SMS segments (160 chars per segment).
    $segments = (int) ceil($length / 160);

    // Simulate sending delay.
    usleep(300000);

    // Mock successful delivery 95% of the time.
    $success = (rand(1, 20) <= 19);

    if ($success) {
      $result = [
        'status' => 'sent',
        'message_id' => 'SMS-' . uniqid() . '-' . time(),
        'phone' => $phone,
        'message' => $message,
        'segments' => $segments,
        'sender' => $options['sender'] ?? 'ÅbenForms',
        'timestamp' => time(),
      ];

      $this->logger->info('Mock SMS sent to @phone: "@message" (message_id: @message_id)', [
        '@phone' => $phone,
        '@message' => mb_substr($message, 0, 50) . (mb_strlen($message) > 50 ? '...' : ''),
        '@message_id' => $result['message_id'],
      ]);

      return $result;
    }
    else {
      // Simulate various error scenarios.
      $errors = [
        'Invalid phone number',
        'Network error',
        'SMS gateway timeout',
        'Daily quota exceeded',
      ];

      $error = $errors[array_rand($errors)] . ' (mock error)';

      $this->logger->warning('Mock SMS failed to @phone: @error', [
        '@phone' => $phone,
        '@error' => $error,
      ]);

      return [
        'status' => 'failed',
        'error' => $error,
        'phone' => $phone,
        'timestamp' => time(),
      ];
    }
  }

  /**
   * Sends bulk SMS messages.
   *
   * @param array $recipients
   *   Array of phone numbers.
   * @param string $message
   *   SMS message content.
   * @param array $options
   *   Optional settings.
   *
   * @return array
   *   Bulk send results.
   */
  public function sendBulkSms(array $recipients, string $message, array $options = []): array {
    $results = [
      'total' => count($recipients),
      'sent' => 0,
      'failed' => 0,
      'messages' => [],
    ];

    foreach ($recipients as $phone) {
      $result = $this->sendSms($phone, $message, $options);
      $results['messages'][] = $result;

      if ($result['status'] === 'sent') {
        $results['sent']++;
      }
      else {
        $results['failed']++;
      }
    }

    return $results;
  }

  /**
   * Gets SMS delivery status.
   *
   * @param string $message_id
   *   The message ID to check.
   *
   * @return array
   *   Delivery status information.
   */
  public function getDeliveryStatus(string $message_id): array {
    // Simulate API call delay.
    usleep(200000);

    // Mock delivery status - assume delivered.
    return [
      'message_id' => $message_id,
      'status' => 'delivered',
      'delivered_at' => time() - rand(10, 300),
    ];
  }

}
