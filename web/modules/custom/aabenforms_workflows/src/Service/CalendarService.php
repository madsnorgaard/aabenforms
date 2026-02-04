<?php

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Mock calendar and booking service for development and demos.
 *
 * This service simulates a calendar/booking system for marriage ceremonies,
 * meetings, and appointments. In production, this would integrate with a
 * real booking system or calendar service.
 */
class CalendarService {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Static storage for bookings (in-memory for demo).
   *
   * @var array
   */
  protected static array $bookings = [];

  /**
   * Constructs a CalendarService.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Gets available time slots for booking.
   *
   * @param string $start_date
   *   Start date in Y-m-d format.
   * @param string $end_date
   *   End date in Y-m-d format.
   * @param int $slot_duration
   *   Slot duration in minutes (default: 60).
   * @param array $options
   *   Optional settings:
   *   - location: string - Location/venue
   *   - ceremony_type: string - Type of ceremony.
   *
   * @return array
   *   Available slots with metadata.
   */
  public function getAvailableSlots(string $start_date, string $end_date, int $slot_duration = 60, array $options = []): array {
    $start = strtotime($start_date);
    $end = strtotime($end_date);

    if ($start === FALSE || $end === FALSE || $start > $end) {
      return [
        'status' => 'error',
        'error' => 'Invalid date range',
      ];
    }

    $slots = [];
    $current_date = $start;

    // Generate slots for each day in the range.
    while ($current_date <= $end) {
      $day_of_week = date('N', $current_date);

      // Skip weekends (Saturday=6, Sunday=7).
      if ($day_of_week < 6) {
        // Generate slots between 10:00 and 16:00 (ceremony hours).
        $day_start = strtotime(date('Y-m-d', $current_date) . ' 10:00:00');
        $day_end = strtotime(date('Y-m-d', $current_date) . ' 16:00:00');

        $slot_start = $day_start;
        while ($slot_start + ($slot_duration * 60) <= $day_end) {
          $slot_id = 'SLOT-' . date('Ymd-Hi', $slot_start);

          // Check if slot is already booked.
          $is_booked = isset(self::$bookings[$slot_id]);

          if (!$is_booked) {
            $slots[] = [
              'slot_id' => $slot_id,
              'date' => date('Y-m-d', $slot_start),
              'start_time' => date('H:i', $slot_start),
              'end_time' => date('H:i', $slot_start + ($slot_duration * 60)),
              'duration' => $slot_duration,
              'location' => $options['location'] ?? 'Borgerservice',
              'available' => TRUE,
            ];
          }

          $slot_start += ($slot_duration * 60);
        }
      }

      $current_date = strtotime('+1 day', $current_date);
    }

    $this->logger->info('Generated @count available slots for date range @start to @end', [
      '@count' => count($slots),
      '@start' => $start_date,
      '@end' => $end_date,
    ]);

    return [
      'status' => 'success',
      'slots' => $slots,
      'total_slots' => count($slots),
    ];
  }

  /**
   * Books a time slot.
   *
   * @param string $slot_id
   *   The slot ID to book.
   * @param array $attendees
   *   Attendee information:
   *   - name: string
   *   - email: string
   *   - phone: string
   *   - cpr: string (optional, encrypted)
   * @param array $booking_details
   *   Additional booking details.
   *
   * @return array
   *   Booking result.
   */
  public function bookSlot(string $slot_id, array $attendees, array $booking_details = []): array {
    // Check if slot exists and is available.
    if (isset(self::$bookings[$slot_id])) {
      return [
        'status' => 'failed',
        'error' => 'Slot already booked (double booking prevented)',
        'slot_id' => $slot_id,
      ];
    }

    // Validate attendees.
    if (empty($attendees)) {
      return [
        'status' => 'failed',
        'error' => 'At least one attendee required',
      ];
    }

    // Create booking.
    $booking_id = 'BOOK-' . uniqid() . '-' . time();
    $booking = [
      'booking_id' => $booking_id,
      'slot_id' => $slot_id,
      'attendees' => $attendees,
      'details' => $booking_details,
      'booked_at' => time(),
      'status' => 'confirmed',
    ];

    // Store booking.
    self::$bookings[$slot_id] = $booking;

    $this->logger->info('Slot booked successfully: @booking_id (slot: @slot_id, attendees: @count)', [
      '@booking_id' => $booking_id,
      '@slot_id' => $slot_id,
      '@count' => count($attendees),
    ]);

    return [
      'status' => 'success',
      'booking_id' => $booking_id,
      'slot_id' => $slot_id,
      'attendees' => $attendees,
      'booked_at' => $booking['booked_at'],
    ];
  }

  /**
   * Cancels a booking.
   *
   * @param string $booking_id
   *   The booking ID to cancel.
   *
   * @return array
   *   Cancellation result.
   */
  public function cancelBooking(string $booking_id): array {
    // Find booking by ID.
    foreach (self::$bookings as $slot_id => $booking) {
      if ($booking['booking_id'] === $booking_id) {
        unset(self::$bookings[$slot_id]);

        $this->logger->info('Booking cancelled: @booking_id (slot: @slot_id)', [
          '@booking_id' => $booking_id,
          '@slot_id' => $slot_id,
        ]);

        return [
          'status' => 'success',
          'booking_id' => $booking_id,
          'slot_id' => $slot_id,
          'cancelled_at' => time(),
        ];
      }
    }

    return [
      'status' => 'failed',
      'error' => 'Booking not found',
      'booking_id' => $booking_id,
    ];
  }

  /**
   * Gets booking details.
   *
   * @param string $booking_id
   *   The booking ID.
   *
   * @return array|null
   *   Booking details or NULL if not found.
   */
  public function getBooking(string $booking_id): ?array {
    foreach (self::$bookings as $booking) {
      if ($booking['booking_id'] === $booking_id) {
        return $booking;
      }
    }
    return NULL;
  }

  /**
   * Gets all bookings for a date range.
   *
   * @param string $start_date
   *   Start date in Y-m-d format.
   * @param string $end_date
   *   End date in Y-m-d format.
   *
   * @return array
   *   List of bookings.
   */
  public function getBookingsInRange(string $start_date, string $end_date): array {
    $bookings = [];

    foreach (self::$bookings as $slot_id => $booking) {
      // Extract date from slot_id (format: SLOT-YYYYMMDD-HHMM).
      if (preg_match('/SLOT-(\d{8})/', $slot_id, $matches)) {
        $slot_date = $matches[1];
        $formatted_date = substr($slot_date, 0, 4) . '-' . substr($slot_date, 4, 2) . '-' . substr($slot_date, 6, 2);

        if ($formatted_date >= $start_date && $formatted_date <= $end_date) {
          $bookings[] = $booking;
        }
      }
    }

    return $bookings;
  }

  /**
   * Checks if a slot is available.
   *
   * @param string $slot_id
   *   The slot ID to check.
   *
   * @return bool
   *   TRUE if available, FALSE if booked.
   */
  public function isSlotAvailable(string $slot_id): bool {
    return !isset(self::$bookings[$slot_id]);
  }

  /**
   * Reschedules a booking to a new slot.
   *
   * @param string $booking_id
   *   The booking ID to reschedule.
   * @param string $new_slot_id
   *   The new slot ID.
   *
   * @return array
   *   Reschedule result.
   */
  public function rescheduleBooking(string $booking_id, string $new_slot_id): array {
    // Check if new slot is available.
    if (!$this->isSlotAvailable($new_slot_id)) {
      return [
        'status' => 'failed',
        'error' => 'New slot is not available',
        'slot_id' => $new_slot_id,
      ];
    }

    // Find and move booking.
    foreach (self::$bookings as $old_slot_id => $booking) {
      if ($booking['booking_id'] === $booking_id) {
        unset(self::$bookings[$old_slot_id]);
        $booking['slot_id'] = $new_slot_id;
        $booking['rescheduled_at'] = time();
        self::$bookings[$new_slot_id] = $booking;

        $this->logger->info('Booking rescheduled: @booking_id from @old_slot to @new_slot', [
          '@booking_id' => $booking_id,
          '@old_slot' => $old_slot_id,
          '@new_slot' => $new_slot_id,
        ]);

        return [
          'status' => 'success',
          'booking_id' => $booking_id,
          'old_slot_id' => $old_slot_id,
          'new_slot_id' => $new_slot_id,
          'rescheduled_at' => $booking['rescheduled_at'],
        ];
      }
    }

    return [
      'status' => 'failed',
      'error' => 'Booking not found',
      'booking_id' => $booking_id,
    ];
  }

  /**
   * Clears all bookings (for testing).
   */
  public function clearAllBookings(): void {
    self::$bookings = [];
    $this->logger->info('All bookings cleared');
  }

}
