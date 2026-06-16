# ÅbenForms Quick Start Demo Guide

**Goal**: Demonstrate ÅbenForms as a no-licence-fee, open-source alternative to proprietary municipal workflow platforms in 15 minutes.

**Status**: Pre-pilot POC. Live demo at https://aabenforms.dk (frontend) and https://api.aabenforms.dk (backend). MitID via Keycloak mock; Serviceplatformen and Digital Post via test/mock endpoints. There are no production municipality deployments.

---

## Important: what is real vs what is a demo mock

Before running this demo, be honest with yourself and the audience about which parts are
real and which are illustrative mocks.

**Real today** (works, though against test/mock endpoints):
- ECA workflow engine, the Workflow Modeler editor, and execution replay
- MitID OIDC sign-in against a Keycloak mock IdP (fails closed by default; demo mode is opt-in)
- CPR (SF1520) and CVR (SF1530) lookup clients (against test/WireMock; need client certs for live)
- Custom webform elements with server-side validation: CPR (modulus-11), CVR, Adressevælger
- Field-level CPR encryption (AES-256) plus audit logging
- Digital Post (SF1601) in fake_db / wiremock test modes (no live MeMo/SOAP or idempotency yet)

**Demo mocks** (NOT production - do not claim these "work in production"):
- Payment, SMS, GIS/zoning, payroll, and calendar/booking actions are demo mocks that return
  canned success responses. They demonstrate the workflow shape, not a live integration.

When you show the demos below, say plainly: "This is a demo mock that returns a simulated
response so we can see the workflow end to end. A live integration is a pilot deliverable."

---

## Quick Demo (15 minutes)

### Prerequisites
```bash
cd /home/mno/ddev-projects/aabenforms/backend
ddev start
ddev drush cr
```

### Demo 1: Payment Processing - demo mock (3 minutes)

**Demonstrate the payment step in a workflow**. The payment service is a demo mock that
returns a simulated success response; it does not contact a live payment provider.

```bash
echo "=== Testing Payment Service (demo mock) ==="
ddev drush php:eval '
$payment = \Drupal::service("aabenforms_workflows.payment_service");

// Process parking permit payment (mock)
$result = $payment->processPayment([
  "amount" => 30000, // 300 DKK
  "currency" => "DKK",
  "order_id" => "DEMO-PARKING-001",
  "payment_method" => "card",
  "description" => "Parking permit - 6 months",
]);

echo "Payment Status: " . $result["status"] . "\n";
echo "Payment ID: " . $result["payment_id"] . "\n";
echo "Transaction ID: " . $result["transaction_id"] . "\n";
echo "Amount: " . ($result["amount"]/100) . " DKK\n";
'
```

**Expected Output**:
```
Payment Status: success
Payment ID: PAY-xxx-1234567890
Transaction ID: TXN-ABCDEF123456
Amount: 300 DKK
```

Note: simulated response from the demo mock. A live payment integration is a pilot deliverable.

---

### Demo 2: SMS Notifications - demo mock (2 minutes)

The SMS service is a demo mock that returns a simulated send result.

```bash
echo "=== Testing SMS Service (demo mock) ==="
ddev drush php:eval '
$sms = \Drupal::service("aabenforms_workflows.sms_service");

$result = $sms->sendSms(
  "+4512345678",
  "Din parkeringslicens er godkendt. Licens-ID: DEMO-001"
);

echo "SMS Status: " . $result["status"] . "\n";
echo "Message ID: " . $result["message_id"] . "\n";
echo "Segments: " . $result["segments"] . "\n";
'
```

**Expected Output**:
```
SMS Status: sent
Message ID: SMS-xxx-1234567890
Segments: 1
```

Note: simulated response from the demo mock.

---

### Demo 3: PDF Generation (2 minutes)

```bash
echo "=== Testing PDF Generation ==="
ddev drush php:eval '
$pdf = \Drupal::service("aabenforms_workflows.pdf_service");

$result = $pdf->generatePdf("parking_permit", [
  "vehicle_registration" => "AB12345",
  "valid_until" => "2026-08-02",
  "zone" => "Zone A",
]);

echo "PDF Status: " . $result["status"] . "\n";
echo "File ID: " . $result["file_id"] . "\n";
echo "Filename: " . $result["filename"] . "\n";
echo "File URL: " . $result["file_url"] . "\n";
'
```

---

### Demo 4: Calendar Booking - demo mock (3 minutes)

The calendar/booking service is a demo mock returning simulated slots and bookings.

```bash
echo "=== Testing Calendar Service (demo mock - Marriage Booking) ==="
ddev drush php:eval '
$calendar = \Drupal::service("aabenforms_workflows.calendar_service");

// Fetch available ceremony slots (mock)
$slots = $calendar->getAvailableSlots("2026-03-01", "2026-03-15", 60);

echo "Available Slots: " . $slots["total_slots"] . "\n";
echo "\nFirst 5 slots:\n";
foreach (array_slice($slots["slots"], 0, 5) as $slot) {
  echo "  - " . $slot["date"] . " " . $slot["start_time"] . "-" . $slot["end_time"] . " at " . $slot["location"] . "\n";
}

// Book a slot (mock)
$booking = $calendar->bookSlot($slots["slots"][0]["slot_id"], [
  ["name" => "Partner 1", "email" => "p1@test.dk"],
  ["name" => "Partner 2", "email" => "p2@test.dk"],
]);

echo "\nBooking Status: " . $booking["status"] . "\n";
echo "Booking ID: " . $booking["booking_id"] . "\n";
'
```

Note: simulated response from the demo mock.

---

### Demo 5: GIS Zoning Validation - demo mock (3 minutes)

The GIS/zoning service is a demo mock. It returns illustrative zoning and neighbour data; it
does not query a live GIS system.

```bash
echo "=== Testing GIS Service (demo mock - Zoning Validation) ==="
ddev drush php:eval '
$gis = \Drupal::service("aabenforms_workflows.gis_service");

// Check if extension is allowed (mock)
$validation = $gis->validateConstructionType(
  "Vestergade 10, 8000 Aarhus C",
  "tilbygning"
);

echo "Address: " . $validation["address"] . "\n";
echo "Zone Type: " . $validation["zone_type"] . "\n";
echo "Construction Type: " . $validation["construction_type"] . "\n";
echo "Allowed: " . ($validation["allowed"] ? "YES" : "NO") . "\n";
echo "Reason: " . $validation["reason"] . "\n";

// Find neighbors to notify (mock)
$neighbors = $gis->findNeighborsInRadius(
  "Vestergade 10, 8000 Aarhus C",
  50
);

echo "\nNeighbors within 50m: " . $neighbors["total_neighbors"] . "\n";
foreach ($neighbors["neighbors"] as $neighbor) {
  echo "  - " . $neighbor["owner_name"] . " (" . $neighbor["distance_meters"] . "m away)\n";
}
'
```

Note: simulated response from the demo mock. GIS integration is a pilot deliverable, not a
shipped feature.

---

### Demo 6: Complete Workflow Test (2 minutes)

This stitches together the payment, PDF, and SMS steps. Payment and SMS are demo mocks.

```bash
echo "=== Testing Complete Parking Permit Workflow ==="
ddev drush php:eval '
// Simulate complete workflow execution (payment + SMS are demo mocks)
$services = [
  "payment" => \Drupal::service("aabenforms_workflows.payment_service"),
  "sms" => \Drupal::service("aabenforms_workflows.sms_service"),
  "pdf" => \Drupal::service("aabenforms_workflows.pdf_service"),
];

echo "Step 1: Process payment (mock)...\n";
$payment_result = $services["payment"]->processPayment([
  "amount" => 30000,
  "currency" => "DKK",
  "order_id" => "WORKFLOW-DEMO-001",
  "payment_method" => "card",
]);
echo "   Payment " . $payment_result["status"] . "\n";

echo "\nStep 2: Generate PDF permit...\n";
$pdf_result = $services["pdf"]->generatePdf("parking_permit", [
  "vehicle_registration" => "DEMO123",
  "valid_until" => "2026-08-02",
]);
echo "   PDF generated: " . $pdf_result["filename"] . "\n";

echo "\nStep 3: Send SMS confirmation (mock)...\n";
$sms_result = $services["sms"]->sendSms(
  "+4512345678",
  "Din parkeringslicens er godkendt! Se vedhæftet PDF."
);
echo "   SMS " . $sms_result["status"] . "\n";

echo "\nWorkflow executed (payment and SMS via demo mocks).\n";
echo "Open source, no per-form or per-integration licence fees.\n";
'
```

---

## Summary: ÅbenForms vs proprietary alternatives

| Area | Proprietary alternatives | ÅbenForms |
|------|--------------------------|-----------|
| Payment step | Production integration | Demo mock today; live integration is a pilot deliverable |
| SMS step | Production integration | Demo mock today |
| PDF generation | Yes | Working |
| Calendar booking | Production integration | Demo mock today |
| GIS validation | Varies by vendor | Demo mock today |
| Licensing | Annual licence fees, often per-integration and per-form | Open source, no licence fees |
| API | Often older SOAP APIs | Modern JSON:API |
| Data control / lock-in | Vendor-controlled | Self-hosted, full source access, no lock-in |

The honest pitch: ÅbenForms already runs real ECA workflows, MitID sign-in, CPR/CVR lookups, and
field-level encryption against test endpoints. The payment, SMS, GIS, payroll, and calendar
actions are demo mocks that show the workflow shape; making them live is pilot work.

---

## Key Talking Points for Demo

1. **No per-form or per-integration licence fees**
   - Proprietary self-service platforms typically charge annual licence fees plus
     per-integration fees. ÅbenForms is open source with no licence fees.

2. **Open source, no vendor lock-in**
   - Full source access, self-hostable, your data stays under your control.

3. **Workflows are real, several integrations are still mocks**
   - The ECA engine, Workflow Modeler, MitID sign-in, CPR/CVR lookup, and encryption are real.
   - Payment, SMS, GIS, payroll, and calendar actions are demo mocks today.

4. **Built on proven technology**
   - Drupal 11 (core 11.3.10), PHP 8.4
   - ECA 3.1.1 workflow engine with the Workflow Modeler editor
   - Modern JSON:API

---

## Workflow Library

- 13 ready-made workflow templates (BPMN source files) under
  `web/modules/custom/aabenforms_workflows/workflows/`.
- 18 ECA flows deployed (config/sync/eca.eca.*).

---

## Status and Next Steps

This is a pre-pilot POC. Outstanding work before a pilot:

1. Replace demo mocks (payment, SMS, GIS, payroll, calendar) with live integrations.
2. Live Serviceplatformen and Digital Post endpoints (client certs, MeMo/SOAP, idempotency).
3. Data retention / right-to-erasure subsystem (planned, issue #91).
4. Frontend components and end-to-end tests.
5. User guides and training materials.

---

## Contact

- **Demo Site (frontend)**: https://aabenforms.dk
- **Demo Site (backend API)**: https://api.aabenforms.dk
- **GitHub**: https://github.com/madsnorgaard/aabenforms

---

*Open source. No vendor lock-in. Danish municipal workflows. That's ÅbenForms.*
