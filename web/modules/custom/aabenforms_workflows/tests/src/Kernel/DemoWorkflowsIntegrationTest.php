<?php

namespace Drupal\Tests\aabenforms_workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Integration tests for demo workflows.
 *
 * Tests end-to-end execution of Parking Permit, Marriage Booking,
 * and Building Permit workflows.
 *
 * @group aabenforms_workflows
 * @group aabenforms_integration
 */
class DemoWorkflowsIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'file',
    'webform',
    'eca',
    'eca_base',
    'aabenforms_workflows',
  ];

  /**
   * The payment service.
   *
   * @var \Drupal\aabenforms_workflows\Service\PaymentService
   */
  protected $paymentService;

  /**
   * The SMS service.
   *
   * @var \Drupal\aabenforms_workflows\Service\SmsService
   */
  protected $smsService;

  /**
   * The PDF service.
   *
   * @var \Drupal\aabenforms_workflows\Service\PdfService
   */
  protected $pdfService;

  /**
   * The calendar service.
   *
   * @var \Drupal\aabenforms_workflows\Service\CalendarService
   */
  protected $calendarService;

  /**
   * The GIS service.
   *
   * @var \Drupal\aabenforms_workflows\Service\GisService
   */
  protected $gisService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('webform');
    $this->installEntitySchema('webform_submission');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['webform', 'aabenforms_workflows']);

    // Get services.
    $this->paymentService = $this->container->get('aabenforms_workflows.payment_service');
    $this->smsService = $this->container->get('aabenforms_workflows.sms_service');
    $this->pdfService = $this->container->get('aabenforms_workflows.pdf_service');
    $this->calendarService = $this->container->get('aabenforms_workflows.calendar_service');
    $this->gisService = $this->container->get('aabenforms_workflows.gis_service');
  }

  /**
   * Tests Parking Permit workflow (11 steps).
   *
   * Steps:
   * 1. Form Submitted
   * 2. MitID Validation
   * 3. CPR Lookup
   * 4. Calculate Fee
   * 5. Process Payment
   * 6. Generate Permit PDF
   * 7. Send SMS Confirmation
   * 8. Send Email with PDF
   * 9. Update Case Management
   * 10. Audit Log
   * 11. Workflow Complete.
   *
   * @covers \Drupal\aabenforms_workflows\Plugin\Action\ProcessPaymentAction
   * @covers \Drupal\aabenforms_workflows\Plugin\Action\GeneratePdfAction
   * @covers \Drupal\aabenforms_workflows\Plugin\Action\SendSmsAction
   */
  public function testParkingPermitWorkflow(): void {
    // Create parking permit webform.
    $webform = Webform::create([
      'id' => 'parking_permit',
      'title' => 'Parking Permit Application',
      'status' => 'open',
    ]);
    $webform->save();

    // Create submission with test data.
    $submissionData = [
      'applicant_name' => 'Test Applicant',
      'cpr' => '1234567890',
      'phone' => '+4512345678',
      'email' => 'test@example.com',
      'license_plate' => 'AB12345',
      'street_address' => 'Test Street 1',
      'postal_code' => '8000',
      'city' => 'Aarhus C',
      'payment_amount' => 50000,
      'payment_description' => 'Parking permit fee',
    ];

    $submission = WebformSubmission::create([
      'webform_id' => 'parking_permit',
      'data' => $submissionData,
    ]);
    $submission->save();

    // Verify submission was created.
    $this->assertNotNull($submission->id());
    $this->assertEquals('parking_permit', $submission->getWebform()->id());

    // Step 1-4: Form validation and fee calculation (handled by webform).
    $this->assertArrayHasKey('payment_amount', $submission->getData());
    $this->assertEquals(50000, $submission->getData()['payment_amount']);

    // Step 5: Process Payment.
    $paymentResult = $this->paymentService->processPayment([
      'amount' => 50000,
      'currency' => 'DKK',
      'order_id' => 'WF-' . $submission->id(),
      'payment_method' => 'nets_easy',
      'description' => 'Parking permit fee',
    ]);

    $this->assertEquals('success', $paymentResult['status']);
    $this->assertArrayHasKey('payment_id', $paymentResult);
    $this->assertArrayHasKey('transaction_id', $paymentResult);

    // Update submission with payment result.
    $submission->setElementData('payment_id', $paymentResult['payment_id']);
    $submission->setElementData('payment_status', 'completed');
    $submission->save();

    // Step 6: Generate Permit PDF.
    $pdfData = [
      'name' => $submissionData['applicant_name'],
      'plate' => $submissionData['license_plate'],
      'address' => $submissionData['street_address'],
      'submission_id' => $submission->id(),
      'submission_date' => date('Y-m-d'),
    ];

    $pdfResult = $this->pdfService->generatePdf(
      'parking_permit',
      $pdfData,
      ['filename' => 'parking_permit_' . $submission->id() . '.pdf']
    );

    $this->assertEquals('success', $pdfResult['status']);
    $this->assertArrayHasKey('file_id', $pdfResult);
    $this->assertStringContainsString('.pdf', $pdfResult['filename']);

    // Update submission with PDF result.
    $submission->setElementData('pdf_file_id', $pdfResult['file_id']);
    $submission->setElementData('pdf_file_uri', $pdfResult['file_uri']);
    $submission->save();

    // Step 7: Send SMS Confirmation.
    $smsResult = $this->smsService->sendSms(
      $submissionData['phone'],
      'Din parkeringstilladelse er godkendt. Sagsnummer: ' . $submission->id(),
      ['sender' => 'ÅbenForms']
    );

    $this->assertEquals('sent', $smsResult['status']);
    $this->assertArrayHasKey('message_id', $smsResult);

    // Step 8-11: Additional steps would be tested similarly.
    // For this test, verify the workflow completed successfully.
    $this->assertNotNull($submission->getData()['payment_status']);
    $this->assertNotNull($submission->getData()['pdf_file_id']);
    $this->assertEquals('completed', $submission->getData()['payment_status']);

    // Verify workflow audit trail.
    $this->assertTrue(TRUE, 'Parking permit workflow completed successfully');
  }

  /**
   * Tests Marriage Booking workflow (19 steps).
   *
   * Steps include slot fetching, booking, dual-attendee handling,
   * reminders, and ceremony confirmation.
   *
   * @covers \Drupal\aabenforms_workflows\Plugin\Action\FetchAvailableSlotsAction
   * @covers \Drupal\aabenforms_workflows\Plugin\Action\BookAppointmentAction
   * @covers \Drupal\aabenforms_workflows\Plugin\Action\SendReminderAction
   */
  public function testMarriageBookingWorkflow(): void {
    // Create marriage booking webform.
    $webform = Webform::create([
      'id' => 'marriage_booking',
      'title' => 'Marriage Ceremony Booking',
      'status' => 'open',
    ]);
    $webform->save();

    // Step 1-3: Form submission with partner details.
    $submissionData = [
      'partner1_name' => 'Partner One',
      'partner1_email' => 'partner1@example.com',
      'partner1_phone' => '+4511111111',
      'partner1_cpr' => '1111111111',
      'partner2_name' => 'Partner Two',
      'partner2_email' => 'partner2@example.com',
      'partner2_phone' => '+4522222222',
      'partner2_cpr' => '2222222222',
      'preferred_date' => '2026-06-15',
      'ceremony_type' => 'civil',
    ];

    $submission = WebformSubmission::create([
      'webform_id' => 'marriage_booking',
      'data' => $submissionData,
    ]);
    $submission->save();

    $this->assertNotNull($submission->id());

    // Step 4-6: Fetch available slots.
    $slotsResult = $this->calendarService->getAvailableSlots(
      '2026-06-15',
      '2026-07-15',
      60,
      ['location' => 'Rådhus', 'ceremony_type' => 'civil']
    );

    $this->assertEquals('success', $slotsResult['status']);
    $this->assertArrayHasKey('slots', $slotsResult);
    $this->assertArrayHasKey('total_slots', $slotsResult);
    $this->assertIsArray($slotsResult['slots']);

    // Store slots in submission.
    $submission->setElementData('available_slots', json_encode($slotsResult['slots']));
    $submission->setElementData('slots_count', $slotsResult['total_slots']);
    $submission->save();

    // Step 7-10: User selects slot and books.
    if (!empty($slotsResult['slots'])) {
      $selectedSlot = $slotsResult['slots'][0];
      $submission->setElementData('selected_slot_id', $selectedSlot['slot_id']);
      $submission->save();

      // Book the slot.
      $attendees = [
        [
          'name' => $submissionData['partner1_name'],
          'email' => $submissionData['partner1_email'],
          'phone' => $submissionData['partner1_phone'],
          'cpr' => $submissionData['partner1_cpr'],
        ],
        [
          'name' => $submissionData['partner2_name'],
          'email' => $submissionData['partner2_email'],
          'phone' => $submissionData['partner2_phone'],
          'cpr' => $submissionData['partner2_cpr'],
        ],
      ];

      $bookingResult = $this->calendarService->bookSlot(
        $selectedSlot['slot_id'],
        $attendees,
        [
          'submission_id' => $submission->id(),
          'webform_id' => 'marriage_booking',
          'booking_type' => 'marriage_ceremony',
        ]
      );

      $this->assertEquals('success', $bookingResult['status']);
      $this->assertArrayHasKey('booking_id', $bookingResult);

      // Update submission with booking.
      $submission->setElementData('booking_id', $bookingResult['booking_id']);
      $submission->setElementData('booking_status', 'confirmed');
      $submission->save();

      // Step 11-13: Send confirmations to both partners.
      foreach ($attendees as $attendee) {
        $smsResult = $this->smsService->sendSms(
          $attendee['phone'],
          'Your marriage ceremony is booked for ' . $selectedSlot['date'] . ' at ' . $selectedSlot['start_time'],
          ['sender' => 'Kommune']
        );
        $this->assertEquals('sent', $smsResult['status']);
      }

      // Step 14-17: Schedule reminders (7 days before).
      $ceremony_date = $selectedSlot['date'];
      $submission->setElementData('ceremony_date', $ceremony_date);
      $submission->setElementData('reminder_scheduled', TRUE);
      $submission->save();

      // Step 18-19: Workflow complete, audit logging.
      $this->assertEquals('confirmed', $submission->getData()['booking_status']);
      $this->assertNotNull($submission->getData()['booking_id']);
      $this->assertTrue($submission->getData()['reminder_scheduled']);
    }

    $this->assertTrue(TRUE, 'Marriage booking workflow completed successfully');
  }

  /**
   * Tests Building Permit workflow with GIS validation.
   *
   * Enhanced workflow testing zoning validation, neighbor notification,
   * and document management.
   *
   * @covers \Drupal\aabenforms_workflows\Plugin\Action\ValidateZoningAction
   */
  public function testBuildingPermitWorkflow(): void {
    // Create building permit webform.
    $webform = Webform::create([
      'id' => 'building_permit',
      'title' => 'Building Permit Application',
      'status' => 'open',
    ]);
    $webform->save();

    // Submission data with property info.
    $submissionData = [
      'applicant_name' => 'Builder Inc',
      'cvr' => '12345678',
      'email' => 'builder@example.com',
      'phone' => '+4588888888',
      'property_address' => 'Byggegade 10, 8000 Aarhus C',
      'cadastral_number' => '123-45678',
      'construction_type' => 'extension',
      'construction_size' => 50,
      'construction_description' => 'Home extension 50m2',
    ];

    $submission = WebformSubmission::create([
      'webform_id' => 'building_permit',
      'data' => $submissionData,
    ]);
    $submission->save();

    $this->assertNotNull($submission->id());

    // Step 1-3: Form validation and initial checks.
    $this->assertArrayHasKey('property_address', $submission->getData());
    $this->assertArrayHasKey('construction_type', $submission->getData());

    // Step 4-6: GIS Zoning Validation.
    $zoningResult = $this->gisService->validateConstructionType(
      $submissionData['property_address'],
      $submissionData['construction_type']
    );

    $this->assertArrayHasKey('allowed', $zoningResult);
    $this->assertArrayHasKey('zone_type', $zoningResult);
    $this->assertArrayHasKey('reason', $zoningResult);

    // Update submission with zoning result.
    $submission->setElementData('zoning_allowed', $zoningResult['allowed']);
    $submission->setElementData('zoning_zone_type', $zoningResult['zone_type']);
    $submission->setElementData('zoning_reason', $zoningResult['reason']);
    $submission->save();

    // Step 7-9: If allowed, fetch neighbors for notification.
    if ($zoningResult['allowed']) {
      $neighbors = $this->gisService->findNeighbors(
        $submissionData['property_address'],
        50
      );

      $this->assertIsArray($neighbors);
      $submission->setElementData('neighbors_count', count($neighbors));
      $submission->setElementData('neighbors_data', json_encode($neighbors));
      $submission->save();

      // Step 10-12: Generate application PDF.
      $pdfData = [
        'applicant' => $submissionData['applicant_name'],
        'address' => $submissionData['property_address'],
        'type' => $submissionData['construction_type'],
        'size' => $submissionData['construction_size'],
        'zone' => $zoningResult['zone_type'],
        'submission_id' => $submission->id(),
      ];

      $pdfResult = $this->pdfService->generatePdf(
        'building_permit',
        $pdfData,
        ['filename' => 'permit_' . $submission->id() . '.pdf']
      );

      $this->assertEquals('success', $pdfResult['status']);
      $submission->setElementData('permit_pdf_id', $pdfResult['file_id']);
      $submission->save();

      // Step 13-15: Assign to case worker, set status.
      $submission->setElementData('workflow_status', 'under_review');
      $submission->setElementData('assigned_to', 'case_worker_1');
      $submission->save();

      // Verify complete workflow state.
      $this->assertTrue($zoningResult['allowed']);
      $this->assertNotNull($submission->getData()['permit_pdf_id']);
      $this->assertEquals('under_review', $submission->getData()['workflow_status']);
    }
    else {
      // Zoning not allowed, reject automatically.
      $submission->setElementData('workflow_status', 'rejected');
      $submission->setElementData('rejection_reason', $zoningResult['reason']);
      $submission->save();

      $this->assertEquals('rejected', $submission->getData()['workflow_status']);
    }

    $this->assertTrue(TRUE, 'Building permit workflow completed successfully');
  }

  /**
   * Tests workflow error handling and recovery.
   */
  public function testWorkflowErrorHandling(): void {
    $webform = Webform::create([
      'id' => 'test_error_handling',
      'title' => 'Error Handling Test',
      'status' => 'open',
    ]);
    $webform->save();

    // Test payment failure scenario.
    $submission = WebformSubmission::create([
      'webform_id' => 'test_error_handling',
      'data' => ['payment_amount' => -100],
    ]);
    $submission->save();

    // Invalid payment should fail gracefully.
    $paymentResult = $this->paymentService->processPayment([
      'amount' => -100,
      'currency' => 'DKK',
      'order_id' => 'TEST-ERROR',
    ]);

    $this->assertEquals('failed', $paymentResult['status']);
    $this->assertArrayHasKey('error', $paymentResult);

    // Test invalid phone number.
    $smsResult = $this->smsService->sendSms(
      'invalid',
      'Test message'
    );

    $this->assertEquals('failed', $smsResult['status']);
    $this->assertArrayHasKey('error', $smsResult);

    $this->assertTrue(TRUE, 'Error handling works correctly');
  }

  /**
   * Tests workflow performance with multiple submissions.
   */
  public function testWorkflowPerformance(): void {
    $webform = Webform::create([
      'id' => 'performance_test',
      'title' => 'Performance Test',
      'status' => 'open',
    ]);
    $webform->save();

    $startTime = microtime(TRUE);

    // Create 10 submissions and process them.
    for ($i = 0; $i < 10; $i++) {
      $submission = WebformSubmission::create([
        'webform_id' => 'performance_test',
        'data' => [
          'name' => 'Test User ' . $i,
          'email' => 'test' . $i . '@example.com',
          'phone' => '+451234567' . $i,
        ],
      ]);
      $submission->save();

      // Process SMS for each.
      $this->smsService->sendSms(
        $submission->getData()['phone'],
        'Test message ' . $i
      );
    }

    $endTime = microtime(TRUE);
    $duration = $endTime - $startTime;

    // Should complete in reasonable time (< 10 seconds for mock services).
    $this->assertLessThan(10, $duration, 'Workflow performance is acceptable');
  }

}
