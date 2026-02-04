# Visual Workflow Builder Guide
## Creating Workflows with BPMN.io for Danish Municipalities

**Version**: 1.0
**Date**: February 2026
**Target Audience**: Municipal administrators, workflow designers, citizen service managers

---

## Table of Contents

1. [Introduction](#introduction)
2. [What is BPMN?](#what-is-bpmn)
3. [Getting Started](#getting-started)
4. [BPMN.io Visual Editor](#bpmnio-visual-editor)
5. [Danish Municipal Action Palette](#danish-municipal-action-palette)
6. [Creating Your First Workflow](#creating-your-first-workflow)
7. [Advanced Workflow Patterns](#advanced-workflow-patterns)
8. [Testing and Validation](#testing-and-validation)
9. [Best Practices](#best-practices)
10. [Common Patterns and Templates](#common-patterns-and-templates)
11. [Troubleshooting](#troubleshooting)

---

## Introduction

ÅbenForms uses visual workflow design based on BPMN 2.0 (Business Process Model and Notation), an international standard for business process modeling. This means you can create and modify workflows without writing code - using drag-and-drop visual design.

### Why Visual Workflows?

**Benefits**:
- **No programming required**: Citizen service staff can design workflows
- **Self-documenting**: The diagram IS the documentation
- **International standard**: BPMN 2.0 is used by Fortune 500 companies worldwide
- **Easy to modify**: Change workflows without developer involvement
- **Collaborative**: Stakeholders understand visual diagrams better than code
- **Version control**: Track changes over time with visual diffs

**Who Can Create Workflows?**:
- Municipal administrators
- Citizen service managers
- Process analysts
- Department heads
- Anyone who understands the business process (no IT background required)

---

## What is BPMN?

BPMN (Business Process Model and Notation) is an international standard for drawing business process diagrams. Think of it as a flowchart with specific symbols that have precise meanings.

### Core BPMN Elements

#### 1. Events (Circles)

Events represent something that happens during the workflow.

**Start Event** (Green circle with thin border):
- Where the workflow begins
- Usually: "Citizen submits form" or "Application received"
- Every workflow has exactly ONE start event

**End Event** (Red circle with thick border):
- Where the workflow ends
- Can have multiple end events (success, failure, timeout)
- Examples: "Permit issued", "Application rejected", "Timeout"

**Intermediate Events** (Yellow circle with double border):
- Events that happen during the workflow
- Timer events: Wait for a specific time
- Message events: Wait for external trigger
- Examples: "Wait 30 days", "Receive payment confirmation"

#### 2. Tasks (Rectangles)

Tasks represent work that needs to be done.

**Service Task** (Blue rectangle with gear icon):
- Automated actions performed by the system
- Examples: "Send email", "Process payment", "Validate address"
- Most common task type in ÅbenForms

**User Task** (Purple rectangle with person icon):
- Manual work performed by a human
- Examples: "Case worker reviews application", "Select ceremony date"
- Creates a task in the admin interface

**Send Task** (Blue rectangle with filled envelope icon):
- Sends a message (email, SMS, notification)
- Specialized type of service task

**Receive Task** (Blue rectangle with empty envelope icon):
- Waits to receive a message
- Used for external confirmations

#### 3. Gateways (Diamonds)

Gateways control the flow of the process (decisions and merging).

**Exclusive Gateway** (Yellow diamond with X):
- Makes a decision: "If payment successful, then..."
- Only ONE path is taken
- Most common gateway type
- Example: "Payment successful?" → Yes or No

**Parallel Gateway** (Yellow diamond with +):
- Splits flow into multiple parallel paths
- ALL paths are taken simultaneously
- Example: Send email to Partner 1 AND Partner 2 at the same time
- Merges parallel paths back together

**Event-Based Gateway** (Yellow diamond with pentagon):
- Waits for one of several possible events
- First event to occur determines the path
- Example: Wait for "Info received" OR "30-day timeout"

#### 4. Sequence Flows (Arrows)

Arrows connect elements and show the order of execution.

**Solid Arrow**:
- Normal flow from one element to the next
- Most common connector

**Conditional Arrow** (with mini-diamond):
- Only followed if condition is true
- Example: Flow from gateway with condition "${payment_status == 'completed'}"

**Default Arrow** (with slash):
- Followed if no other condition matches
- Used as fallback from exclusive gateway

#### 5. Pools and Lanes (Swimlanes)

**Pool** (Large container):
- Represents a participant (e.g., "Citizen", "Municipality")
- Optional in ÅbenForms (we usually use single-pool workflows)

**Lane** (Horizontal subdivision of pool):
- Represents a role or department within a pool
- Example: "Citizen Services", "Case Worker", "Finance"
- Helps visualize who performs which tasks

---

## Getting Started

### Accessing the Workflow Builder

1. **Login** to ÅbenForms admin interface:
   ```
   https://[your-municipality].aabenforms.dk/admin
   ```

2. **Navigate** to Workflow Management:
   ```
   Configuration → Workflows → BPMN Designer
   OR
   https://[your-municipality].aabenforms.dk/admin/config/workflow/bpmn
   ```

3. **Choose an option**:
   - **Create New Workflow**: Start from blank canvas
   - **Use Template**: Start from pre-built workflow
   - **Import Workflow**: Upload existing BPMN file

### Browser Requirements

**Recommended**:
- Chrome 100+ (best performance)
- Firefox 100+
- Edge 100+

**Not Recommended**:
- Safari (has some rendering issues with BPMN.io)
- Internet Explorer (not supported)

**Screen Resolution**:
- Minimum: 1366 x 768
- Recommended: 1920 x 1080 or higher
- Large monitor recommended for complex workflows

---

## BPMN.io Visual Editor

### Editor Interface

The BPMN.io editor has four main areas:

```
┌─────────────────────────────────────────────────────────────┐
│  Menu Bar: File | Edit | View | Help              [Save]    │
├─────┬───────────────────────────────────────────────────────┤
│     │                                                         │
│  P  │                                                         │
│  a  │              Canvas                                    │
│  l  │           (Drag and drop elements here)                │
│  e  │                                                         │
│  t  │                                                         │
│  t  │                                                         │
│  e  │                                                         │
│     │                                                         │
├─────┴───────────────────────────────────────────────────────┤
│  Properties Panel (appears when element is selected)         │
│  [ Name: _________________ ]                                 │
│  [ Action: ______________ ▼ ]                                │
│  [ Configuration: ... ]                                      │
└─────────────────────────────────────────────────────────────┘
```

### Palette (Left Sidebar)

The palette contains all elements you can add to your workflow:

**Basic Elements**:
- Start Event
- End Event
- Service Task
- User Task
- Exclusive Gateway

**Danish Municipal Actions** (when expanded):
- Authenticate with MitID
- CPR Lookup (SF1520)
- CVR Lookup (SF1530)
- Validate Address (DAWA)
- Process Payment (Nets Easy)
- Generate PDF
- Send Email
- Send SMS
- Send Digital Post (SF1601)
- Book Appointment
- Send Reminder
- Create SBSYS Case
- Audit Log
- ... (16 total actions)

### Canvas (Center Area)

The canvas is where you design your workflow:

**Basic Operations**:
- **Add element**: Drag from palette to canvas
- **Connect elements**: Click element, then click connection point, drag to next element
- **Move element**: Click and drag
- **Delete element**: Select and press Delete key
- **Undo**: Ctrl+Z (Windows) or Cmd+Z (Mac)
- **Redo**: Ctrl+Y or Cmd+Y
- **Zoom**: Mouse wheel or zoom controls
- **Pan**: Hold Spacebar and drag
- **Select multiple**: Ctrl+Click (Windows) or Cmd+Click (Mac)

**Canvas Tools** (Bottom-right):
- Zoom in (+)
- Zoom out (-)
- Reset zoom (1:1)
- Fit to screen
- Hand tool (pan)
- Lasso tool (select multiple)

### Properties Panel (Right Sidebar or Bottom)

When you select an element, the properties panel appears:

**Common Properties**:
- **Name**: Human-readable label (shown on diagram)
- **ID**: Unique identifier (auto-generated, don't change unless necessary)
- **Documentation**: Description of the element's purpose

**Service Task Properties**:
- **Action**: Which ÅbenForms action to execute
- **Action Configuration**: Settings specific to the action
  - Field names
  - Integration settings
  - Message templates
  - Conditional logic

**Gateway Properties**:
- **Default Flow**: Which arrow to follow if no condition matches
- **Conditional Expressions**: Logic for each outgoing arrow

**User Task Properties**:
- **Assignee**: Which user or role gets the task
- **Form**: Which form to display (if applicable)
- **Due Date**: Task deadline

---

## Danish Municipal Action Palette

ÅbenForms includes 16 pre-built actions for Danish municipalities. Each action integrates with a specific service or performs a common task.

### Authentication & Identity Actions

#### 1. Authenticate with MitID

**What it does**: Validates MitID authentication session

**Use case**: Verify citizen or business identity at workflow start

**Configuration**:
- Workflow ID token: Token containing workflow instance ID
- Result token: Where to store validation result (TRUE/FALSE)
- Session data token: Where to store session data (CPR, name, address)

**Example**:
```
Name: Authenticate Citizen
Action: aabenforms_mitid_validate
Configuration:
  - workflow_id_token: workflow_id
  - result_token: mitid_valid
  - session_data_token: mitid_session
```

**When to use**:
- Start of workflows requiring identity verification
- Before accessing sensitive personal data
- For legally binding submissions

**When NOT to use**:
- Anonymous contact forms
- Public information requests
- Low-security workflows

---

#### 2. CPR Lookup (SF1520)

**What it does**: Retrieves person data from Serviceplatformen

**Use case**: Get citizen's name, address, family relations

**Configuration**:
- CPR field: Which form field contains the CPR number
- Store result in: Token to store retrieved data

**Example**:
```
Name: Retrieve Applicant Data
Action: aabenforms_cpr_lookup
Configuration:
  - cpr_field: cpr
  - store_in: person_data
```

**Retrieved Data**:
- Full name
- Current address
- Address history (last 5 years)
- Marital status
- Family relations
- Date of birth

**GDPR Note**: CPR lookups are automatically logged for audit compliance

**When to use**:
- After MitID authentication (CPR from MitID session)
- Marriage booking (verify both partners)
- Building permits (verify property owner)
- Social services (family verification)

---

#### 3. CVR Lookup (SF1530)

**What it does**: Retrieves company data from Serviceplatformen

**Use case**: Get company name, address, ownership, industry codes

**Configuration**:
- CVR field: Which form field contains the CVR number
- Include units: Include P-numbers (production units)
- Store result in: Token to store retrieved data

**Example**:
```
Name: Retrieve Company Information
Action: aabenforms_cvr_lookup
Configuration:
  - cvr_field: cvr_number
  - include_units: true
  - store_in: company_data
```

**Retrieved Data**:
- Company name
- Status (active, dissolved, under bankruptcy)
- Address
- Industry codes (NACE)
- P-numbers (production units)
- Ownership structure
- Contact information

**When to use**:
- Business license applications
- Company verification workflows
- Contract submissions requiring company data

---

### Data Validation Actions

#### 4. Validate Address (DAWA)

**What it does**: Validates Danish address using DAWA API

**Use case**: Ensure valid address before processing application

**Configuration**:
- Address field: Which form field contains the address
- Validate only: TRUE = just validate, FALSE = also retrieve additional data
- Store result in: Token to store validation result and data

**Example**:
```
Name: Validate Property Address
Action: aabenforms_dawa_validate
Configuration:
  - address_field: address
  - validate_only: false
  - store_in: address_data
```

**Returned Data**:
- Validation result (valid/invalid)
- Formatted address (standardized)
- GPS coordinates
- Municipality code
- Region
- Postal district

**When to use**:
- All workflows involving addresses
- Before property-related lookups (zoning, GIS)
- For geolocation-based services (parking zones)

---

### Payment Actions

#### 5. Process Payment (Nets Easy)

**What it does**: Processes payment via Nets Easy gateway

**Use case**: Collect fees for permits, bookings, services

**Configuration**:
- Amount field: Form field containing amount in øre (1 DKK = 100 øre)
- Currency: DKK, EUR, or USD
- Payment method: nets_easy, mobilepay, or bank_transfer
- Description field: Optional payment description
- Store payment ID in: Token to store payment ID
- Store status in: Token to store payment status

**Example**:
```
Name: Process Booking Fee
Action: aabenforms_process_payment
Configuration:
  - amount_field: booking_fee
  - currency: DKK
  - payment_method: nets_easy
  - description_field: payment_description
  - store_payment_id_in: payment_id
  - store_status_in: payment_status
```

**Payment Flow**:
1. Redirects citizen to Nets Easy payment page
2. Citizen enters card details
3. Payment processed
4. Citizen redirected back to workflow
5. Payment confirmation stored in submission

**Payment Status Values**:
- `completed`: Payment successful
- `pending`: Awaiting confirmation
- `failed`: Payment declined or error
- `cancelled`: User cancelled payment

**When to use**:
- Parking permits (fees)
- Marriage bookings (booking fees)
- Building permits (application fees)
- Any paid service

---

### Document Generation Actions

#### 6. Generate PDF

**What it does**: Generates PDF document from template

**Use case**: Create permits, certificates, confirmations

**Configuration**:
- Template: Which PDF template to use
- Filename pattern: Pattern for filename (supports tokens)
- Include attachments: Attach to email or store only

**Example**:
```
Name: Generate Parking Permit
Action: aabenforms_generate_pdf
Configuration:
  - template: parking_permit
  - filename_pattern: parking_permit_{submission_id}.pdf
  - include_qr_code: true
```

**PDF Templates**:
Templates are created in admin interface:
```
Configuration → Workflows → PDF Templates
```

**Token Replacement**:
Templates can use tokens like:
- `{submission:id}`: Submission ID
- `{submission:created}`: Submission date
- `{submission:field_name}`: Any form field
- `{user:name}`: Current user name

**When to use**:
- Permits (parking, building)
- Certificates (marriage, completion)
- Receipts (payment confirmations)
- Official letters (approvals, rejections)

---

### Communication Actions

#### 7. Send Email

**What it does**: Sends email to specified recipient

**Use case**: Notifications, confirmations, official correspondence

**Configuration**:
- To field: Form field containing recipient email, or hardcoded email
- CC field: Optional CC recipients
- Subject: Email subject (supports tokens)
- Body template: Email body HTML template
- Attach PDF: Attach PDF generated in previous step

**Example**:
```
Name: Email Permit to Citizen
Action: email_send
Configuration:
  - to_field: email
  - subject: Din parkeringslicens er klar
  - body_template: parking_permit_email
  - attach_pdf: true
```

**Email Templates**:
Created in admin interface:
```
Configuration → Workflows → Email Templates
```

**When to use**:
- All workflows (confirmation emails)
- Caseworker notifications
- Multi-party workflows (send to multiple people)

---

#### 8. Send SMS

**What it does**: Sends SMS notification via Danish SMS gateway

**Use case**: Instant notifications, reminders, confirmations

**Configuration**:
- Phone field: Form field containing phone number (+45 format)
- Message template: SMS text (max 160 chars for single SMS)
- Sender name: Sender name shown to recipient (max 11 chars)
- Store message ID in: Token to store message ID

**Example**:
```
Name: SMS Confirmation
Action: aabenforms_send_sms
Configuration:
  - phone_field: phone
  - message_template: Din parkeringslicens er godkendt. Sagsnr: [submission:id]
  - sender_name: Kommune
  - store_message_id_in: sms_message_id
```

**SMS Best Practices**:
- Keep messages under 160 characters (single SMS)
- Use tokens for personalization: `[submission:id]`, `[submission:field_name]`
- Include municipality name as sender
- Don't include sensitive data (CPR, passwords)
- Use for time-sensitive notifications

**When to use**:
- Payment confirmations
- Appointment reminders
- Status updates
- Urgent notifications

---

#### 9. Send Digital Post (SF1601)

**What it does**: Sends legally binding notification via Serviceplatformen Digital Post

**Use case**: Official municipal communications (approvals, rejections, invoices)

**Configuration**:
- CPR field: Recipient's CPR number
- Subject: Letter subject
- Document: PDF document to send
- Fallback to physical mail: If recipient doesn't have digital mailbox

**Example**:
```
Name: Send Building Permit Approval
Action: aabenforms_send_digital_post
Configuration:
  - cpr_field: cpr
  - subject: Godkendelse af byggetilladelse
  - document: building_permit_approval.pdf
  - fallback_physical_mail: true
```

**Digital Post Features**:
- Legally binding notifications
- Delivery receipt (when opened)
- Automatic fallback to physical mail if no digital mailbox
- Stored in recipient's e-Boks or Mit.dk mailbox

**When to use**:
- Official approvals/rejections
- Legal notices
- Invoices
- Any legally binding communication

**When NOT to use**:
- Informal notifications (use email)
- Reminders (use email/SMS)
- Marketing (not allowed)

---

### Calendar & Reminder Actions

#### 10. Fetch Available Slots

**What it does**: Retrieves available appointment slots from calendar

**Use case**: Display available dates/times for booking

**Configuration**:
- Start date field: Earliest date to show
- Date range days: How many days ahead to search
- Slot duration: Appointment duration in minutes
- Location: Which calendar/location to query

**Example**:
```
Name: Fetch Ceremony Slots
Action: aabenforms_fetch_available_slots
Configuration:
  - start_date_field: preferred_date
  - date_range_days: 90
  - slot_duration: 60
  - location: Rådhus - Vielsessal
```

**Returns**:
- Array of available slots with:
  - Date
  - Time
  - Duration
  - Location
  - Officiant/resource
  - Slot ID (for booking)

**When to use**:
- Marriage ceremony bookings
- Caseworker appointments
- Resource booking (rooms, equipment)

---

#### 11. Book Appointment

**What it does**: Reserves selected appointment slot

**Use case**: Confirm booking after payment

**Configuration**:
- Slot ID field: Which slot to book (from Fetch Available Slots)
- Attendee name field: Booking person's name
- Attendee email field: Email for calendar invite
- Attendee phone field: Phone for reminders

**Example**:
```
Name: Book Ceremony Slot
Action: aabenforms_book_appointment
Configuration:
  - slot_id_field: selected_slot_id
  - attendee_name_field: partner1_name
  - attendee_email_field: partner1_email
  - attendee_phone_field: partner1_phone
```

**Booking Actions**:
- Marks slot as reserved in calendar
- Creates calendar event
- Sends calendar invite (ICS) via email
- Optionally sends SMS confirmation

**When to use**:
- After payment confirmation
- After both partners authenticate (marriage)
- Before sending confirmation communications

---

#### 12. Send Reminder

**What it does**: Schedules future reminder (email or SMS)

**Use case**: Remind citizens of upcoming appointments

**Configuration**:
- Reminder type: email, sms, or both
- Delay days: Days before event to send reminder
- Event date field: Field containing event date
- Recipient email field: Email address
- Recipient phone field: Phone number
- Message: Reminder text

**Example**:
```
Name: 7-Day Reminder
Action: aabenforms_send_reminder
Configuration:
  - reminder_type: both
  - delay_days: 7
  - event_date_field: ceremony_date
  - recipient_email_field: partner1_email
  - recipient_phone_field: partner1_phone
  - message: Påmindelse: Din vielse finder sted om 7 dage
```

**Reminder Calculation**:
- System calculates: `event_date - delay_days = send_date`
- Reminder sent automatically on send_date
- No manual intervention required

**When to use**:
- Marriage ceremony reminders (7 days, 1 day before)
- Appointment reminders
- Deadline reminders (e.g., "Complete application in 3 days")

---

### Case Management Actions

#### 13. Create SBSYS Case

**What it does**: Creates case in SBSYS case management system

**Use case**: Integrate workflow with municipal case system

**Configuration**:
- Case type: SBSYS case type code
- Subject: Case subject/title
- Description: Case description
- Attachments: Documents to attach
- Store case ID in: Token to store SBSYS case ID

**Example**:
```
Name: Create Building Permit Case
Action: aabenforms_create_sbsys_case
Configuration:
  - case_type: BP_001
  - subject: Byggetilladelse - [submission:address]
  - description: Ansøgning om byggetilladelse
  - attachments: [uploaded_plans, uploaded_drawings]
  - store_case_id_in: sbsys_case_id
```

**Case Creation**:
- Creates case in SBSYS
- Attaches uploaded documents
- Maps form fields to case fields
- Returns SBSYS case ID

**When to use**:
- Building permits
- Social services applications
- Complex approvals requiring case management
- Any workflow that needs case tracking

---

### Audit & Compliance Actions

#### 14. Audit Log

**What it does**: Creates audit log entry for compliance

**Use case**: Log workflow actions for GDPR compliance

**Configuration**:
- Action: Description of action performed
- Log level: info, warning, or error
- Metadata: Additional context (key-value pairs)

**Example**:
```
Name: Log Permit Issuance
Action: aabenforms_audit_log
Configuration:
  - action: parking_permit_issued
  - log_level: info
  - metadata:
      - permit_id: [submission:id]
      - license_plate: [submission:vehicle_registration]
      - zone: [submission:parking_zone]
```

**Audit Log Storage**:
- Stored in database table `aabenforms_audit_log`
- Includes:
  - Timestamp
  - User ID (if applicable)
  - Action description
  - Submission ID
  - Metadata JSON
  - IP address

**GDPR Compliance**:
Audit logs are required for:
- CPR lookups (who accessed what, when, why)
- Payment processing
- Document access
- Caseworker decisions

**When to use**:
- After CPR/CVR lookups
- After caseworker approvals/rejections
- After payment processing
- At workflow completion

---

### Advanced Actions

#### 15. Validate Zoning (Custom)

**What it does**: Checks property zoning restrictions via GIS

**Use case**: Building permit pre-validation

**Configuration**:
- Address field: Property address
- Zoning type field: Desired use (residential, commercial)
- Store result in: Validation result token

**Example**:
```
Name: Validate Building Zone
Action: aabenforms_validate_zoning
Configuration:
  - address_field: property_address
  - zoning_type_field: building_type
  - store_result_in: zoning_valid
```

**When to use**:
- Building permits
- Business licenses
- Property-related applications

---

#### 16. Notify Neighbors (Custom)

**What it does**: Sends notifications to neighboring properties

**Use case**: Building permits requiring neighbor notification

**Configuration**:
- Property address field: Central property
- Radius meters: How far to search for neighbors
- Notification type: Letter, email, or Digital Post

**Example**:
```
Name: Notify Neighbors of Construction
Action: aabenforms_notify_neighbors
Configuration:
  - property_address_field: building_address
  - radius_meters: 100
  - notification_type: digital_post
```

**When to use**:
- Building permits (structural changes)
- Noise permits
- Property line changes

---

## Creating Your First Workflow

Let's create a simple workflow step-by-step: **Contact Form with Email Notification**

### Step 1: Create New Workflow

1. Navigate to: `Configuration → Workflows → BPMN Designer`
2. Click **Create New Workflow**
3. Enter workflow details:
   - Name: Contact Form
   - ID: contact_form (auto-generated)
   - Category: citizen_service
   - Description: Simple contact form with email notification to staff

4. Click **Create**

### Step 2: Add Start Event

The canvas starts with a default start event. Let's configure it:

1. Click the **Start Event** (green circle)
2. In Properties Panel:
   - Name: "Citizen Submits Inquiry"
   - Documentation: "Citizen submits contact form with name, email, subject, and message"

### Step 3: Add Email Notification Task

1. From Palette, drag **Service Task** to canvas (right of start event)
2. Click the new task
3. In Properties Panel:
   - Name: "Email to Staff"
   - Action: Select "email_send" from dropdown
4. Configure email action:
   - To field: hardcoded@your-municipality.dk (or use webform field)
   - Subject: New contact inquiry from [submission:name]
   - Body template: Select "contact_form_notification" (create template first)

### Step 4: Add End Event

1. From Palette, drag **End Event** to canvas (right of email task)
2. Click the end event
3. In Properties Panel:
   - Name: "Inquiry Sent"

### Step 5: Connect Elements

1. Click **Start Event**
2. Click the small circle connector that appears on the right
3. Drag arrow to **Email Task** - release mouse
4. Click **Email Task**
5. Drag arrow to **End Event**

Your workflow now looks like:
```
(Start) ─→ [Email to Staff] ─→ (Inquiry Sent)
```

### Step 6: Save Workflow

1. Click **Save** button (top-right)
2. Workflow is saved and ready for testing

### Step 7: Create Webform

Now create the contact form that triggers this workflow:

1. Navigate to: `Configuration → Webforms`
2. Click **Add Webform**
3. Add fields:
   - Name (textfield, required)
   - Email (email, required)
   - Subject (textfield, required)
   - Message (textarea, required)
4. In **Settings** tab:
   - Workflow: Select "Contact Form" workflow
   - Submission handling: Execute workflow on submit
5. Save webform

### Step 8: Test Workflow

1. Navigate to the webform URL: `/form/contact-form`
2. Fill out the form:
   - Name: Test User
   - Email: test@example.com
   - Subject: Test Inquiry
   - Message: This is a test message
3. Click Submit
4. Check staff email inbox for notification

**Congratulations!** You've created your first workflow.

---

## Advanced Workflow Patterns

### Pattern 1: Conditional Branching

**Use Case**: Different actions based on payment amount

**Workflow**:
```
(Start) ─→ [Calculate Fee] ─→ <Fee > 1000?> ──Yes──→ [Manager Approval] ─→ (End)
                                      │
                                      No
                                      ↓
                                [Auto-Approve] ─→ (End)
```

**Steps**:

1. Add **Exclusive Gateway** after Calculate Fee task
2. Add two outgoing flows from gateway:
   - **Flow 1**: To Manager Approval task
     - Condition: `${fee_amount > 1000}`
   - **Flow 2**: To Auto-Approve task
     - Mark as **Default Flow**

3. Configure gateway:
   - Click gateway
   - Name: "Fee > 1000 DKK?"

4. Configure conditional flow:
   - Click arrow from gateway to Manager Approval
   - In Properties Panel:
     - Name: "Yes"
     - Condition: `${fee_amount > 1000}`
     - Expression Language: JavaScript

5. Configure default flow:
   - Click arrow from gateway to Auto-Approve
   - In Properties Panel:
     - Name: "No"
     - Default Flow: Checked

---

### Pattern 2: Parallel Tasks

**Use Case**: Send email and SMS simultaneously

**Workflow**:
```
                    ┌─→ [Send Email] ─┐
(Start) ─→ <Split> ─┤                 ├─→ <Merge> ─→ (End)
                    └─→ [Send SMS] ───┘
```

**Steps**:

1. Add **Parallel Gateway** (with + symbol) after Start Event
2. Add two Service Tasks:
   - Send Email
   - Send SMS
3. Add another **Parallel Gateway** after both tasks
4. Connect:
   - Start → First Parallel Gateway
   - First Parallel Gateway → Send Email
   - First Parallel Gateway → Send SMS
   - Send Email → Second Parallel Gateway
   - Send SMS → Second Parallel Gateway
   - Second Parallel Gateway → End Event

**Result**: Email and SMS are sent simultaneously (parallel execution), workflow waits for both to complete before proceeding.

---

### Pattern 3: User Task with Decision

**Use Case**: Caseworker reviews and approves/rejects

**Workflow**:
```
(Start) ─→ [Auto-Validate] ─→ [Caseworker Review] ─→ <Decision> ──Approve──→ [Create Case] ─→ (Approved)
                                                           │
                                                         Reject
                                                           ↓
                                                    [Send Rejection] ─→ (Rejected)
```

**Steps**:

1. Add **User Task**:
   - Name: "Caseworker Review"
   - Assignee: `${caseworker}` or hardcoded user/role
   - Form: Select caseworker decision form

2. Add **Exclusive Gateway** after User Task:
   - Name: "Decision"

3. Add two outgoing flows:
   - **Approve Flow**:
     - Name: "Approve"
     - Condition: `${decision == 'approve'}`
     - Target: Create Case task
   - **Reject Flow**:
     - Name: "Reject"
     - Condition: `${decision == 'reject'}`
     - Target: Send Rejection task

4. Configure User Task form:
   - Create webform with:
     - Decision field (select): Approve, Reject, Request Info
     - Comments field (textarea)
   - Assign form to User Task in workflow

**Caseworker Experience**:
1. Caseworker logs into admin interface
2. Sees "Caseworker Review" task in task list
3. Clicks task to view application details
4. Reviews data and makes decision
5. Workflow continues based on decision

---

### Pattern 4: Timer Event (Timeout)

**Use Case**: Auto-reject application after 30 days if no response

**Workflow**:
```
(Start) ─→ [Request Info] ─→ <Event-Based Gateway> ──Info Received──→ [Process Info] ─→ (End)
                                        │
                                  30-Day Timer
                                        ↓
                                 [Auto-Reject] ─→ (Timeout)
```

**Steps**:

1. Add **Event-Based Gateway** after Request Info task
2. Add **Intermediate Message Event**:
   - Name: "Info Received"
   - Message: "applicant_response"
3. Add **Intermediate Timer Event**:
   - Name: "30 Days"
   - Timer Definition: Duration
   - Duration: `P30D` (ISO 8601 format for 30 days)
4. Connect:
   - Event-Based Gateway → Info Received (message event)
   - Event-Based Gateway → 30 Days (timer event)
   - Info Received → Process Info task → End
   - 30 Days → Auto-Reject task → Timeout End Event

**How it works**:
- After Request Info, workflow waits at Event-Based Gateway
- If applicant responds within 30 days: Message Event triggers, flow goes to Process Info
- If 30 days pass with no response: Timer Event triggers, flow goes to Auto-Reject
- Only ONE of the two paths is taken (whichever happens first)

---

### Pattern 5: Multi-Party Approval (Marriage Booking)

**Use Case**: Both partners must authenticate and approve

**Workflow**:
```
                        ┌─→ [Partner 1 MitID] ─→ [Partner 1 CPR] ─┐
(Start) ─→ <Split> ─────┤                                          ├─→ <Merge> ─→ [Payment] ─→ (End)
                        └─→ [Partner 2 MitID] ─→ [Partner 2 CPR] ─┘
```

**Steps**:

1. Add **Parallel Gateway** (split)
2. Add two parallel paths:
   - **Path 1**: Partner 1 MitID → Partner 1 CPR
   - **Path 2**: Partner 2 MitID → Partner 2 CPR
3. Add **Parallel Gateway** (merge) after both CPR lookups
4. Continue with shared tasks (payment, booking, etc.)

**How it works**:
- Both partners authenticate simultaneously (can use different devices)
- Workflow waits for BOTH authentications to complete
- Once both are verified, workflow proceeds to payment

**Implementation Notes**:
- Each partner receives unique workflow link (via email)
- Links contain partner identifier: `?partner=1` or `?partner=2`
- Workflow tracks authentication status for each partner
- Merge gateway ensures both are complete before proceeding

---

## Testing and Validation

### Before Publishing

Before deploying a workflow to production, validate it thoroughly.

### 1. Visual Validation

**Check for Common Errors**:

- [ ] Every Start Event has at least one outgoing flow
- [ ] Every task has exactly one incoming and one outgoing flow
- [ ] Gateways have correct number of flows:
  - Exclusive Gateway: 2+ outgoing flows (one must be default)
  - Parallel Gateway: 2+ outgoing AND 2+ incoming (for merge)
  - Event-Based Gateway: 2+ outgoing flows to events
- [ ] Every path leads to an End Event (no dead ends)
- [ ] No unreachable elements (disconnected from main flow)
- [ ] All elements have meaningful names (not "Task 1", "Task 2")

**BPMN.io Validation**:
The editor highlights errors with red icons:
- Missing connections
- Invalid gateway configurations
- Syntax errors in conditions

**Fix errors before proceeding.**

---

### 2. Configuration Validation

**Check Each Task**:

- [ ] Service Tasks have action selected
- [ ] Actions have required configuration fields filled
- [ ] Field names match webform field names (case-sensitive)
- [ ] Email templates exist (for email tasks)
- [ ] PDF templates exist (for PDF generation tasks)
- [ ] Conditions use valid syntax: `${variable_name == 'value'}`
- [ ] Token syntax is correct: `[submission:field_name]`

**Common Mistakes**:
- Field name typo: `email` vs. `e_mail`
- Condition syntax: `${payment_status = 'completed'}` (should be `==`, not `=`)
- Missing quotes in condition: `${status == approved}` (should be `${status == 'approved'}`)

---

### 3. Test with Sample Data

**Create Test Webform Submission**:

1. Navigate to workflow's webform
2. Fill out form with test data:
   - Use realistic Danish names, addresses, phone numbers
   - Use test MitID credentials (if MitID is configured)
   - Use test payment card: 4111 1111 1111 1111 (Nets Easy test mode)
3. Submit form

**Monitor Workflow Execution**:

1. Navigate to: `Configuration → Workflows → Execution Log`
2. Find your test submission
3. Check execution status:
   - Green checkmarks: Tasks completed successfully
   - Yellow warning: Tasks with warnings (check logs)
   - Red X: Tasks failed (check error messages)

4. Click each task to see:
   - Execution time
   - Input data
   - Output data
   - Error messages (if failed)

**Verify Results**:

- [ ] Email received (check inbox)
- [ ] SMS received (check test phone)
- [ ] PDF generated (check submission attachments)
- [ ] Payment processed (check payment logs)
- [ ] Audit log created (check audit log table)

---

### 4. Error Handling

**Common Errors**:

**Error**: "Field 'email' not found in submission"
**Solution**: Check field name spelling in webform. Field names are case-sensitive.

**Error**: "Payment processing failed: Invalid amount"
**Solution**: Check amount field contains numeric value in øre (not DKK). Example: 1000 øre = 10 DKK.

**Error**: "MitID session expired"
**Solution**: MitID sessions expire after 30 minutes. User must re-authenticate.

**Error**: "CPR lookup failed: Invalid CPR number"
**Solution**: CPR must be 10 digits (DDMMYYNNNN). Check format.

**Error**: "Condition evaluation failed"
**Solution**: Check condition syntax. Use `${variable_name == 'value'}`. Ensure variable exists.

**Error**: "Email template 'contact_form' not found"
**Solution**: Create email template in `Configuration → Workflows → Email Templates` with ID 'contact_form'.

---

### 5. Load Testing

**For High-Volume Workflows** (> 1,000 submissions/month):

1. Create test script to submit forms automatically
2. Use Drush command:
   ```bash
   ddev drush aabenforms:test-workflow contact_form --count=100
   ```
3. Monitor performance:
   - Average execution time (should be < 10 seconds)
   - Error rate (should be < 1%)
   - Database load (check with `ddev drush watchdog:show`)

**Performance Benchmarks**:
- Simple workflow (3-5 tasks): < 5 seconds
- Medium workflow (6-10 tasks): < 10 seconds
- Complex workflow (11+ tasks): < 20 seconds

If exceeding these times, optimize:
- Reduce external API calls
- Use parallel gateways for independent tasks
- Cache frequently accessed data
- Add database indexes

---

## Best Practices

### 1. Naming Conventions

**Use Clear, Descriptive Names**:

Good:
- "Authenticate Citizen with MitID"
- "Validate Property Address"
- "Email Permit to Applicant"
- "Caseworker Reviews Application"

Bad:
- "Task 1"
- "Service Task"
- "Gateway"
- "Do stuff"

**Naming Pattern**:
- Tasks: Start with verb (Validate, Send, Process, Create, Generate)
- Gateways: Use questions (Payment Successful?, Documents Valid?)
- Events: Use past tense (Citizen Submitted Form, Timeout Reached)

---

### 2. Workflow Organization

**Keep Workflows Simple**:
- Maximum 20 tasks per workflow (if more, split into sub-workflows)
- Maximum 3 decision gateways per workflow
- Avoid deeply nested conditions

**Use Swimlanes for Complex Workflows**:
```
┌────────────────────────────────────────────┐
│ Citizen Lane                               │
│  (Start) → [Fill Form] → [Pay Fee]        │
├────────────────────────────────────────────┤
│ System Lane                                │
│  [Validate] → [Process Payment] → [Email] │
├────────────────────────────────────────────┤
│ Caseworker Lane                            │
│  [Review] → [Approve/Reject]               │
└────────────────────────────────────────────┘
```

**Visual Layout Tips**:
- Left-to-right flow (like reading)
- Align tasks horizontally
- Keep arrows straight (avoid crossing lines)
- Use consistent spacing
- Group related tasks close together

---

### 3. Error Handling

**Always Handle Errors**:

Add error boundary events to critical tasks:

```
[Process Payment] ───Success──→ [Generate PDF]
       │
    Error
       ↓
[Email Payment Failed] ─→ (Payment Failed End)
```

**Implementation**:
1. Click Service Task (e.g., Process Payment)
2. Click **Boundary Event** icon (circle with wrench)
3. Select **Error Boundary Event**
4. Add error handling path (email, notification, alternative flow)

**When to Add Error Handlers**:
- Payment processing (can fail due to declined card)
- External API calls (can timeout or fail)
- Document generation (can fail if template error)
- Email/SMS sending (can fail if invalid address/phone)

---

### 4. Documentation

**Add Documentation to Elements**:

Click any element → Properties Panel → Documentation field

**What to Document**:
- **Workflow**: Purpose, trigger, expected outcome
- **Tasks**: What data is required, what it does, what it outputs
- **Gateways**: Decision logic, conditions
- **User Tasks**: Who should perform it, what they should check

**Example**:
```
Task: Validate Address
Documentation:
Validates property address using DAWA API.
Input: {address} field from webform
Output: {address_valid} (boolean), {address_data} (object with GPS, municipality code)
Error handling: If DAWA API fails, workflow continues but logs warning
```

---

### 5. Reusable Patterns

**Create Workflow Templates for Common Patterns**:

Save workflows as templates:
1. Open workflow
2. Click **File** → **Export as Template**
3. Name: "Payment and Notification Pattern"
4. Save

**Common Reusable Patterns**:
- **MitID + CPR Lookup**: Authentication pattern
- **Payment + PDF + Email**: Payment confirmation pattern
- **Caseworker Review + Decision**: Approval pattern
- **Multi-Reminder Schedule**: Reminder pattern

---

### 6. Version Control

**Track Workflow Changes**:

ÅbenForms automatically versions workflows:
- Each save creates a new version
- View version history: `Configuration → Workflows → [Workflow Name] → Versions`
- Restore previous version if needed
- Compare versions (visual diff)

**Best Practices**:
- Add version notes when saving major changes
- Test new versions in staging before deploying to production
- Keep staging and production workflows in sync

---

### 7. Security

**Protect Sensitive Data**:

- [ ] Use MitID authentication for workflows with personal data
- [ ] Add Audit Log tasks after CPR/CVR lookups
- [ ] Don't log sensitive data (CPR, passwords) in plain text
- [ ] Use encrypted tokens for sensitive field storage
- [ ] Restrict caseworker task assignment to specific roles
- [ ] Don't send sensitive data via email (use Digital Post)

**GDPR Checklist**:
- [ ] Consent obtained (if required)
- [ ] Data minimization (only collect necessary fields)
- [ ] CPR encryption enabled
- [ ] Audit logging configured
- [ ] Retention policy set
- [ ] Right to erasure workflow exists

---

## Common Patterns and Templates

### Template 1: Simple Approval Workflow

**Use Case**: Contact form, information request, simple application

**Workflow**:
```
(Start) → [Validate Input] → [Email to Staff] → [Audit Log] → (End)
```

**Configuration**:
1. Validate Input: Check required fields, format validation
2. Email to Staff: Send notification to staff email
3. Audit Log: Log submission for tracking

**Setup Time**: 30 minutes

---

### Template 2: Payment Workflow

**Use Case**: Parking permit, booking fee, service fee

**Workflow**:
```
(Start) → [Calculate Fee] → [Process Payment] → <Payment OK?> ──Yes──→ [Generate PDF] → [Email PDF] → [SMS Confirmation] → (End)
                                                        │
                                                       No
                                                        ↓
                                                 [Email Payment Failed] → (Failed End)
```

**Setup Time**: 1-2 hours

---

### Template 3: Dual Authentication Workflow

**Use Case**: Marriage booking, joint application

**Workflow**:
```
                    ┌─→ [Partner 1 MitID] → [Partner 1 CPR] ─┐
(Start) → <Split> ──┤                                         ├─→ <Merge> → [Continue...]
                    └─→ [Partner 2 MitID] → [Partner 2 CPR] ─┘
```

**Setup Time**: 2-3 hours

---

### Template 4: Caseworker Approval Workflow

**Use Case**: Building permit, complex approval

**Workflow**:
```
(Start) → [Validate] → [Auto-Check] → [Assign to Caseworker] → [Caseworker Review] → <Decision> ──Approve──→ [Create Case] → [Send Approval] → (Approved)
                                                                                            │
                                                                                          Reject
                                                                                            ↓
                                                                                      [Send Rejection] → (Rejected)
```

**Setup Time**: 3-4 hours

---

### Template 5: Appointment Booking Workflow

**Use Case**: Marriage ceremony, caseworker meeting

**Workflow**:
```
(Start) → [Authenticate] → [Fetch Available Slots] → [User Selects Slot] → [Process Payment] → [Book Appointment] → [Email + SMS Confirmation] → [Schedule Reminders] → (End)
```

**Setup Time**: 3-4 hours

---

## Troubleshooting

### Problem: Workflow doesn't start when form is submitted

**Possible Causes**:
1. Webform not linked to workflow
2. Workflow not published/active
3. Start event not configured

**Solutions**:
1. Check webform settings: `Configuration → Webforms → [Webform] → Settings → Workflow`
2. Ensure workflow is marked as "Active"
3. Verify start event exists and has outgoing flow

---

### Problem: Task executes but action doesn't work

**Possible Causes**:
1. Field name mismatch
2. Missing configuration
3. Integration not configured (MitID, payment gateway)
4. External service error

**Solutions**:
1. Check field names match exactly (case-sensitive)
2. Review task configuration (all required fields filled)
3. Check integration credentials: `Configuration → Integrations`
4. Check execution log for error messages
5. Test external service independently (e.g., send test email)

---

### Problem: Gateway always takes same path

**Possible Causes**:
1. Condition always evaluates to true/false
2. Variable doesn't exist
3. Condition syntax error

**Solutions**:
1. Debug condition: Add audit log before gateway to log variable value
2. Check variable name matches token used
3. Verify condition syntax: `${variable == 'value'}` (use `==`, not `=`)
4. Check quotes around string values

---

### Problem: Workflow execution is slow

**Possible Causes**:
1. Too many sequential external API calls
2. Large document processing
3. Database queries in loops

**Solutions**:
1. Use parallel gateways for independent external calls
2. Optimize document templates (reduce images, compress)
3. Batch database operations
4. Add database indexes for frequently queried fields
5. Enable caching for repeated lookups

---

### Problem: Payment fails but workflow continues

**Possible Causes**:
1. Gateway condition doesn't check payment status
2. Payment action doesn't return status correctly

**Solutions**:
1. Add gateway after Process Payment task with condition: `${payment_status == 'completed'}`
2. Check Process Payment task configuration: "Store status in" field is set
3. Add error boundary event to Process Payment task

---

### Problem: User task not appearing in caseworker's task list

**Possible Causes**:
1. Task not assigned to caseworker
2. Caseworker doesn't have role/permission
3. Workflow hasn't reached user task yet

**Solutions**:
1. Check user task configuration: Assignee field
2. Verify caseworker has role specified in assignee
3. Check workflow execution log to see where workflow is paused
4. Ensure user task has incoming flow from previous task

---

## Resources

### Learning Resources

**BPMN 2.0 Specification**:
- https://www.omg.org/spec/BPMN/2.0/

**BPMN.io Documentation**:
- https://bpmn.io/toolkit/bpmn-js/

**ÅbenForms Documentation**:
- Workflow Templates: [/docs/WORKFLOW_TEMPLATES.md](/docs/WORKFLOW_TEMPLATES.md)
- Municipal Admin Guide: [/docs/MUNICIPAL_ADMIN_GUIDE.md](/docs/MUNICIPAL_ADMIN_GUIDE.md)

**Video Tutorials**:
- Creating Your First Workflow (10 minutes)
- Advanced Gateway Patterns (15 minutes)
- Multi-Party Approval Workflows (20 minutes)
- Available: https://aabenforms.dk/tutorials

---

### Community Support

**Forum**: https://forum.aabenforms.dk
- Ask questions
- Share workflows
- See examples from other municipalities

**GitHub**: https://github.com/madsnorgaard/aabenforms
- Report bugs
- Request features
- Contribute code

**Email Support**: support@aabenforms.dk
- Technical questions
- Bug reports
- Feature requests

---

## Appendix: BPMN Quick Reference

### Event Types

| Symbol | Name | Usage |
|--------|------|-------|
| ⚪ (thin) | Start Event | Workflow begins |
| ⚪ (thick) | End Event | Workflow ends |
| ⚪⚪ (double) | Intermediate Event | Event during workflow |
| ⚪⏰ | Timer Event | Wait for time period |
| ⚪✉️ | Message Event | Wait for message |

### Task Types

| Symbol | Name | Usage |
|--------|------|-------|
| ▭ | Service Task | Automated action |
| ▭👤 | User Task | Manual work |
| ▭✉️ | Send Task | Send message |
| ▭📥 | Receive Task | Receive message |

### Gateway Types

| Symbol | Name | Usage |
|--------|------|-------|
| ◇✖️ | Exclusive Gateway | ONE path taken (decision) |
| ◇➕ | Parallel Gateway | ALL paths taken (split/merge) |
| ◇⬠ | Event-Based Gateway | First event determines path |

### Flow Types

| Symbol | Name | Usage |
|--------|------|-------|
| → | Sequence Flow | Normal flow |
| →◇ | Conditional Flow | Flow with condition |
| →/ | Default Flow | Fallback if no condition matches |

---

**Document Version**: 1.0
**Last Updated**: February 2026
**Next Review**: May 2026

**Questions?** Contact support@aabenforms.dk or visit https://forum.aabenforms.dk
