# Workflow Template Reference

**Last Updated**: 2026-02-02

## Overview

ÅbenForms provides 5 pre-built BPMN workflow templates for common Danish municipal use cases. This document describes each template in detail, including when to use it, required fields, and configuration options.

---

## Table of Contents

1. [Building Permit](#1-building-permit)
2. [Contact Form](#2-contact-form)
3. [Company Verification](#3-company-verification)
4. [Address Change](#4-address-change)
5. [FOI Request](#5-freedom-of-information-request)
6. [Template Comparison](#template-comparison)
7. [Customization Guide](#customization-guide)

---

## 1. Building Permit

**File**: `building_permit.bpmn`
**Category**: Municipal Approval
**Complexity**: Advanced
**Authentication**: MitID (High)

### Description

Complex building permit workflow demonstrating full Danish municipal workflow pattern with:
- MitID authentication
- Document validation
- Multi-step approvals
- Case worker review
- SBSYS integration
- GDPR compliance with full audit logging

### Use Cases

**Primary Use Case**:
- Building permits and construction approvals
- Major renovation permits
- Environmental permits requiring detailed review

**Also Suitable For**:
- Business licenses requiring document validation
- Childcare enrollment with dual parent approval
- Complex application processes with multiple review stages
- Any workflow requiring timeout handling and escalation

### Required Webform Fields

```yaml
# Citizen Information
applicant_name: Text (required)
applicant_cpr: CPR Field (required, encrypted)
applicant_email: Email (required)
applicant_phone: Telephone (required)
applicant_address: DAWA Address (required)

# Application Details
property_address: DAWA Address (required)
permit_type: Select (required)
  - new_construction: "Ny Bygning"
  - renovation: "Renovering"
  - extension: "Tilbygning"
  - demolition: "Nedrivning"

# Documents
building_plans: File Upload (required, PDF/DWG)
site_plan: File Upload (required, PDF)
technical_drawings: File Upload (optional, PDF/DWG)
environmental_report: File Upload (conditional)

# Additional Information
project_description: Textarea (required)
estimated_cost: Number (required)
expected_start_date: Date (required)
expected_completion_date: Date (required)
```

### Workflow Steps

```
1. START: Application Submitted
   ↓
2. Authenticate with MitID
   ↓
3. Verify Identity (CPR Lookup via SF1520)
   ↓
4. Validate Address (DAWA)
   ↓
5. Validate Documents (format, completeness)
   ↓
6. GATEWAY: Documents Valid?
   ├─ YES → Continue
   └─ NO → Request Changes → END (Rejected)
   ↓
7. USER TASK: Case Worker Review
   ↓
8. GATEWAY: Decision
   ├─ APPROVE → Create SBSYS Case → Send Approval
   ├─ REJECT → Send Rejection Notice
   └─ REQUEST INFO → Wait for Response (30 days timeout)
       ├─ Info Received → Back to Case Worker Review
       └─ Timeout → Auto-Reject
   ↓
9. Audit Log All Actions
   ↓
10. END: Application Complete
```

### Configurable Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `authentication_level` | High | Low, Substantial, High | MitID authentication strength |
| `document_validation` | Strict | Strict, Lenient, None | Document format checking |
| `review_timeout` | 30 days | 7-90 days | Time for case worker review |
| `info_request_timeout` | 30 days | 7-60 days | Time for applicant to provide info |
| `sbsys_case_type` | Building Permit | Configurable | SBSYS case classification |
| `notification_method` | Digital Post | Email, Digital Post, Both | Notification channel |

### Integration Points

**Danish Services**:
- **MitID**: Citizen authentication (OIDC)
- **SF1520**: CPR person lookup (Serviceplatformen)
- **DAWA**: Address validation (open API)
- **SF1601**: Digital Post notifications (Serviceplatformen)

**Municipal Systems**:
- **SBSYS**: Case creation and management
- **GetOrganized**: Document archiving (ESDH)

### Example: Aarhus Kommune Building Permit

**Scenario**: Citizen applies for renovation permit for residential property.

```yaml
# Configuration
Workflow ID: building_permit_aarhus_residential
Label: Boligrenoveringspermit - Aarhus Kommune
Authentication: MitID High
Review SLA: 21 working days
Auto-assign: Building Department
```

**Process**:
1. Citizen submits application online
2. MitID authentication (High)
3. CPR verification against national registry
4. DAWA validates property address exists
5. System checks uploaded documents (PDF format, max 20MB)
6. Case assigned to building inspector
7. Inspector reviews within 21 days
8. If approved: SBSYS case created, Digital Post sent
9. If more info needed: 14-day deadline for response
10. All actions logged for GDPR compliance

**Timeline**: Average 25 days from submission to decision.

### Limitations

- Requires MitID integration (not suitable for anonymous submissions)
- Document validation basic (format/size only, not content)
- SBSYS integration required for case creation
- Maximum 90-day timeout (configure shorter if needed)

### When to Use vs. Other Templates

**Use Building Permit when**:
- You need multi-step approval (validation → review → decision)
- Document uploads required
- Integration with case management system
- Timeout handling important
- Complex decision tree (approve/reject/request info)

**Use Address Change instead if**:
- Simple update, no approval needed
- Parallel system updates required
- No document uploads

**Use FOI Request instead if**:
- Focused on document retrieval
- Strict legal deadlines (7 days)
- Redaction workflow needed

---

## 2. Contact Form

**File**: `contact_form.bpmn`
**Category**: Citizen Service
**Complexity**: Simple
**Authentication**: None

### Description

Simple citizen inquiry workflow for routing questions to appropriate departments with automatic confirmation. No authentication required, suitable for general inquiries.

### Use Cases

**Primary Use Case**:
- General citizen inquiries
- Contact forms on municipal website
- Question routing to departments

**Also Suitable For**:
- Anonymous tip submissions
- Event registration
- Newsletter signups
- Simple feedback forms

### Required Webform Fields

```yaml
# Contact Information
name: Text (required)
email: Email (required)
phone: Telephone (optional)

# Inquiry Details
inquiry_type: Select (required)
  - general: "Generel Henvendelse"
  - permits: "Byggesager"
  - tax: "Skat og Ejendom"
  - social: "Social og Sundhed"
  - schools: "Skoler og Dagtilbud"
  - environment: "Miljø og Affald"
  - other: "Andet"

subject: Text (required)
message: Textarea (required)

# Internal
priority: Radios (admin only, optional)
  - normal: "Normal"
  - high: "Høj"
```

### Workflow Steps

```
1. START: Form Submitted
   ↓
2. Validate Input (email format, required fields)
   ↓
3. Route to Department (based on inquiry_type)
   ↓
4. Send Confirmation Email to Citizen
   ↓
5. Audit Log Submission
   ↓
6. END: Submission Complete
```

### Configurable Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `auto_response` | Enabled | Enabled, Disabled | Send confirmation email |
| `department_routing` | Yes | Yes, No | Auto-route by type |
| `spam_protection` | Honeypot | Honeypot, reCAPTCHA, None | Bot prevention |
| `response_sla` | 2 days | 1-5 days | Expected response time |

### Department Routing Rules

| Inquiry Type | Routes To | Email | Response SLA |
|--------------|-----------|-------|--------------|
| General | Reception | reception@municipality.dk | 2 days |
| Permits | Building Dept | permits@municipality.dk | 3 days |
| Tax | Tax Office | tax@municipality.dk | 5 days |
| Social | Social Services | social@municipality.dk | 1 day |
| Schools | Education Dept | schools@municipality.dk | 2 days |
| Environment | Environment Dept | environment@municipality.dk | 3 days |
| Other | Reception | reception@municipality.dk | 2 days |

### Integration Points

**Required**:
- Email service (SMTP)
- Spam protection (Honeypot or reCAPTCHA)

**Optional**:
- Ticket system integration (Zendesk, Freshdesk)
- CRM integration (for tracking inquiries)

### Example: Odense Kommune Contact Form

**Scenario**: Citizen asks question about waste collection schedule.

```yaml
# Configuration
Workflow ID: contact_form_odense
Label: Kontaktformular - Odense Kommune
Spam Protection: Honeypot
Auto-Response: Enabled
```

**Process**:
1. Citizen fills out form (no login needed)
2. System validates email format
3. Inquiry type "Environment" → routes to environment@odense.dk
4. Confirmation email sent to citizen (within 1 minute)
5. Submission logged
6. Department responds within 3 days via email

**Timeline**: Instant submission confirmation, response within SLA.

### Limitations

- No authentication (can't verify citizen identity)
- No approval workflow (auto-routes only)
- No document uploads (add custom if needed)
- No complex routing logic (single-level only)

### When to Use vs. Other Templates

**Use Contact Form when**:
- No authentication needed
- Simple inquiry routing
- Fast response expected
- No approval required

**Use Building Permit instead if**:
- Authentication required
- Approval workflow needed
- Document uploads required

**Use FOI Request instead if**:
- Legal requirement for response
- Strict deadlines apply
- Document retrieval involved

---

## 3. Company Verification

**File**: `company_verification.bpmn`
**Category**: Municipal Verification
**Complexity**: Medium
**Authentication**: MitID (High)

### Description

Verify company registration and director authorization using CVR and CPR lookups. Ensures person is authorized to act on behalf of company, issues certificate automatically.

### Use Cases

**Primary Use Case**:
- Verify company director/signatory authority
- Business license applications
- Contract signing verification

**Also Suitable For**:
- Company registration validation
- P-number verification (production units)
- Board member verification
- Power of attorney validation

### Required Webform Fields

```yaml
# Company Information
company_name: Text (required)
cvr_number: CVR Field (required, format: 12345678)
company_address: DAWA Address (optional)

# Person Information
person_name: Text (required)
person_cpr: CPR Field (required, encrypted)
person_email: Email (required)
person_phone: Telephone (optional)

# Verification Type
verification_type: Select (required)
  - director: "Direktør"
  - signatory: "Tegningsberettiget"
  - board_member: "Bestyrelsesmedlem"
  - attorney: "Fuldmægtig"

# Purpose (for audit)
purpose: Textarea (required)
```

### Workflow Steps

```
1. START: Verification Requested
   ↓
2. Authenticate with MitID (person_cpr)
   ↓
3. CVR Lookup (SF1530 - Serviceplatformen)
   - Retrieve company details
   - Get list of directors/signatories
   ↓
4. CPR Lookup (SF1520 - Serviceplatformen)
   - Verify person identity
   - Get current address
   ↓
5. GATEWAY: Is Person Authorized?
   - Check if CPR matches company director/signatory list
   ├─ YES → Issue Certificate
   └─ NO → Send Rejection Notice
   ↓
6. Audit Log All Lookups (GDPR)
   ↓
7. END: Verification Complete
```

### Configurable Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `cvr_cache_duration` | 30 minutes | 5-60 minutes | CVR data cache time |
| `cpr_cache_duration` | 30 minutes | 5-60 minutes | CPR data cache time |
| `certificate_format` | PDF | PDF, JSON | Certificate output format |
| `certificate_validity` | 90 days | 30-365 days | Certificate expiration |

### CVR Data Retrieved

From SF1530 (Serviceplatformen):
- Company name and status
- CVR and P-numbers
- Directors and signatories (with CPR)
- Board members
- Industry classification (NACE code)
- Registration date
- Address and contact info

### CPR Data Retrieved

From SF1520 (Serviceplatformen):
- Full name
- Current address
- Marital status
- Guardian information (if relevant)

**Note**: All CPR lookups are logged for GDPR compliance.

### Integration Points

**Required**:
- **SF1530**: CVR company lookup (Serviceplatformen)
- **SF1520**: CPR person lookup (Serviceplatformen)
- **MitID**: Authentication

**Optional**:
- Certificate storage (ESDH/GetOrganized)
- Email notifications

### Example: Aarhus Kommune Contractor Verification

**Scenario**: Municipality verifies contractor is authorized to sign contract.

```yaml
# Configuration
Workflow ID: contractor_verification_aarhus
Label: Leverandørverifikation - Aarhus Kommune
Certificate Validity: 180 days
```

**Process**:
1. Municipality initiates verification request
2. Person authenticates with MitID (High)
3. System looks up CVR 12345678 via SF1530
4. System retrieves director list from CVR registry
5. System looks up person CPR via SF1520
6. System cross-references: Is CPR in director list?
7. If YES: Generate PDF certificate valid 180 days
8. If NO: Send rejection notice
9. All lookups logged (who, when, why)

**Timeline**: 30 seconds to 2 minutes (real-time verification).

### Certificate Contents

```
═══════════════════════════════════════
   VIRKSOMHEDSVERIFIKATION
═══════════════════════════════════════

Udstedt af: Aarhus Kommune
Dato: 2026-02-02 14:35:00
Gyldighed: 2026-08-01

VIRKSOMHED:
  Navn: Eksempel A/S
  CVR: 12345678
  Adresse: Virksomhedsvej 1, 8000 Aarhus

PERSON:
  Navn: Anders Andersen
  CPR: XXXXXX-1234 (maskeret)

BEKRÆFTELSE:
   Anders Andersen er registreret som
    direktør for Eksempel A/S per 2026-02-02.

   Data verificeret via:
    - CVR-registret (SF1530)
    - CPR-registret (SF1520)
    - MitID High autentifikation

Referenvenummer: VER-2026-12345
═══════════════════════════════════════
```

### Limitations

- Requires Serviceplatformen credentials
- CVR data only for Danish companies (no international)
- Cannot verify power of attorney (unless registered in CVR)
- No historical lookups (current status only)

### When to Use vs. Other Templates

**Use Company Verification when**:
- Need to verify company authorization
- Director/signatory validation required
- Automatic certificate issuance desired

**Use Building Permit instead if**:
- Company applying for permit (use both workflows)
- Document uploads required
- Multi-step approval needed

**Not suitable for**:
- Personal verification (use CPR lookup directly)
- International companies (use manual verification)

---

## 4. Address Change

**File**: `address_change.bpmn`
**Category**: Municipal Update
**Complexity**: Medium
**Authentication**: MitID (High)

### Description

Process address change notifications with parallel system updates. Validates address with DAWA, authenticates citizen, updates multiple municipal systems simultaneously.

### Use Cases

**Primary Use Case**:
- Citizen changes official address
- Update property tax records
- Update waste collection schedule
- Update citizen portal profile

**Also Suitable For**:
- Contact information updates
- Email/phone number changes
- Emergency contact updates
- Any multi-system update scenario

### Required Webform Fields

```yaml
# Person Information
name: Text (required, auto-filled from CPR)
cpr: CPR Field (required, encrypted)
email: Email (required)
phone: Telephone (required)

# Address Change
old_address: DAWA Address (required, auto-filled)
new_address: DAWA Address (required)
move_date: Date (required)

# Additional
residents: Number (optional)
children_moving: Checkboxes (if applicable)
forward_mail: Checkbox (optional)
```

### Workflow Steps

```
1. START: Address Change Submitted
   ↓
2. Authenticate with MitID
   ↓
3. CPR Lookup (verify identity, get current address)
   ↓
4. DAWA Validation (verify new address exists and is valid)
   ↓
5. PARALLEL GATEWAY: Update Systems Simultaneously
   ├─ Update Citizen Portal
   ├─ Update Property Tax System
   └─ Update Waste Collection Schedule
   (All branches run in parallel)
   ↓
6. JOIN GATEWAY: Wait for All Updates
   ↓
7. Send Confirmation (SF1601 Digital Post)
   ↓
8. Audit Log Address Change
   ↓
9. END: Change Complete
```

### Configurable Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `update_systems` | All | Selectable | Which systems to update |
| `validation_strict` | Yes | Yes, No | Strict DAWA validation |
| `effective_date` | Immediate | Immediate, Scheduled | When change takes effect |
| `notify_related` | Yes | Yes, No | Notify related services (school, etc.) |

### System Integrations

**Municipal Systems Updated** (parallel):

1. **Citizen Portal**
   - Profile address
   - Contact preferences
   - Notification settings

2. **Property Tax System**
   - Property assessment
   - Tax billing address
   - Payment reminders

3. **Waste Collection**
   - Collection schedule
   - Bin allocation
   - Notification address

4. **Optional Systems** (configurable):
   - School district assignment
   - Library registration
   - Parking permits
   - Social services records

### Integration Points

**Required**:
- **MitID**: Authentication
- **SF1520**: CPR lookup
- **DAWA**: Address validation
- **SF1601**: Digital Post confirmation

**Municipal Systems** (REST APIs):
- Citizen Portal API
- Property Tax System API
- Waste Management System API

### Example: Copenhagen Municipality Address Update

**Scenario**: Citizen moves within Copenhagen, needs all systems updated.

```yaml
# Configuration
Workflow ID: address_change_copenhagen
Label: Adresseændring - Københavns Kommune
Update Systems:
  - Citizen Portal
  - Property Tax
  - Waste Collection
  - Library
  - Parking
Effective: Immediate
```

**Process**:
1. Citizen logs in, submits new address
2. MitID authentication (High)
3. System retrieves current address from CPR (Bredgade 10)
4. DAWA validates new address (Nørrebrogade 20)
5. System updates (in parallel, ~30 seconds each):
   - Citizen Portal: New address saved
   - Property Tax: Billing address updated
   - Waste Collection: Schedule adjusted
   - Library: Card re-issued with new address
   - Parking: Permit zone updated
6. Digital Post confirmation sent (within 24 hours)
7. All updates logged

**Timeline**: 2-5 minutes for all updates.

### Parallel Update Visualization

```
DAWA Validation 
       │
       ▼
  ┌────────────┐
  │  Trigger   │
  │  Updates   │
  └─────┬──────┘
        │
    ┌───┴───┬───────┬───────┬────────┐
    │       │       │       │        │
    ▼       ▼       ▼       ▼        ▼
┌────────┐ ┌────┐ ┌──────┐ ┌─────┐ ┌──────┐
│Citizen │ │Tax │ │Waste │ │Lib- │ │Park- │
│Portal  │ │    │ │      │ │rary │ │ing   │
└───┬────┘ └─┬──┘ └───┬──┘ └──┬──┘ └───┬──┘
    │        │        │       │        │
    └────────┴────┬───┴───────┴────────┘
                  │
                  ▼
            ┌──────────┐
            │   All    │
            │ Complete │
            └──────────┘
```

**Benefit**: All systems updated simultaneously (faster than sequential).

### Limitations

- Requires DAWA API (Danish addresses only)
- Municipal systems must have REST APIs
- No address history (overwrites current)
- Cannot undo (must submit new change)

### When to Use vs. Other Templates

**Use Address Change when**:
- Multiple systems need updating
- Parallel processing beneficial
- Address validation critical
- Quick turnaround required

**Use Building Permit instead if**:
- Approval workflow needed
- Document uploads required
- Multi-step review process

**Not suitable for**:
- International addresses
- Complex approval chains
- Manual review required

---

## 5. Freedom of Information Request

**File**: `foi_request.bpmn`
**Category**: Municipal Legal
**Complexity**: Medium
**Authentication**: Optional

### Description

Process Freedom of Information (FOI) requests with strict deadline tracking. Supports both authenticated and anonymous requests. Ensures 7-day legal response deadline is met.

### Use Cases

**Primary Use Case**:
- Freedom of Information Act requests (Offentlighedsloven)
- Document access requests
- Transparency compliance

**Also Suitable For**:
- GDPR data access requests
- Public records requests
- Case file access
- Environmental information requests

### Required Webform Fields

```yaml
# Requester Information
requester_name: Text (required)
requester_email: Email (required)
requester_phone: Telephone (optional)
requester_address: Textarea (optional)

# Authentication (optional)
authenticated: Checkbox
cpr: CPR Field (conditional, if authenticated)

# Request Details
request_type: Select (required)
  - documents: "Aktindsigt i Dokumenter"
  - case_file: "Aktindsigt i Sag"
  - personal_data: "Persondata (GDPR)"
  - meeting_minutes: "Mødereferater"
  - other: "Andet"

request_description: Textarea (required)
time_period: Date Range (optional)
specific_documents: Textarea (optional)

# Purpose (optional for tracking)
purpose: Textarea (optional)
```

### Workflow Steps

```
1. START: FOI Request Submitted
   ↓
2. Validate Request (completeness, clarity)
   ↓
3. Route to Department (based on request type)
   ↓
4. USER TASK: Review Documents
   [7-Day Deadline Timer Starts]
   │
   ├─ Deadline Reminder at Day 5
   └─ Deadline Warning at Day 6
   ↓
5. USER TASK: Redact Sensitive Information
   (CPR, personal details, security info)
   ↓
6. GATEWAY: Release Decision
   ├─ APPROVE → Release Documents
   └─ DENY → Send Denial Notice (with reason)
   ↓
7. Notify Citizen (email + optional Digital Post)
   ↓
8. Audit Log FOI Request
   ↓
9. END: Request Complete
```

### Configurable Parameters

| Parameter | Default | Options | Description |
|-----------|---------|---------|-------------|
| `response_deadline` | 7 days | 7-14 days | Legal deadline (7 in Denmark) |
| `reminder_day` | Day 5 | Day 3-6 | Send reminder to reviewer |
| `require_auth` | No | Yes, No | Require MitID for submission |
| `auto_redact` | No | Yes, No | Automatic CPR redaction |
| `appeal_info` | Yes | Yes, No | Include appeal instructions |

### Deadline Management

**7-Day Timeline**:
```
Day 0:  Request submitted
Day 1:  Assigned to department
Day 2:  Department begins review
Day 5:  Reminder sent to reviewer (if not completed)
Day 6:  Warning sent to reviewer + supervisor
Day 7:  DEADLINE - must respond or violate law
Day 8+: Escalated to legal team, requester notified of delay
```

**Deadline Event** (boundary event on review task):
```xml
<bpmn:boundaryEvent id="Event_Deadline"
                     name="7-Day Deadline"
                     attachedToRef="Task_ReviewDocuments">
  <bpmn:timerEventDefinition>
    <bpmn:timeDuration>P7D</bpmn:timeDuration>
  </bpmn:timerEventDefinition>
</bpmn:boundaryEvent>
```

### Redaction Guidelines

**Always Redact**:
- Full CPR numbers (except requester's own)
- Home addresses (except public figures)
- Personal email addresses
- Phone numbers
- Sensitive health information

**Sometimes Redact** (case-by-case):
- Names of public employees (if not case handlers)
- Internal deliberations (if in progress)
- Third-party business information
- Security-sensitive information

**Never Redact**:
- Requester's own information (GDPR right)
- Public meeting minutes
- Final decisions and outcomes
- Statistical data (anonymized)

### Integration Points

**Required**:
- Email service (for notifications and document delivery)
- Document storage (for released files)

**Optional**:
- **MitID**: If requiring authentication
- **SF1601**: Digital Post for official notice
- **ESDH**: GetOrganized for document management

### Example: Aarhus Kommune FOI Request

**Scenario**: Journalist requests meeting minutes from municipal board.

```yaml
# Configuration
Workflow ID: foi_request_aarhus
Label: Aktindsigt - Aarhus Kommune
Deadline: 7 days (legal requirement)
Authentication: Optional
Auto-Redact CPR: Yes
```

**Process**:
1. Journalist submits request (no authentication required)
2. System validates request is clear
3. Request routed to Municipal Secretary's office
4. Day 1: Secretary assigns to legal clerk
5. Day 3: Clerk reviews meeting minutes
6. Day 4: Clerk redacts names of private citizens
7. Day 5: Clerk approves release
8. Day 5: PDF emailed to journalist
9. All actions logged (GDPR audit)

**Timeline**: 5 days (within 7-day legal deadline).

### Denial Reasons (Must Be Documented)

If denying access, must provide legal basis:
- **§27 (1)**: Internal case processing not yet completed
- **§27 (2)**: Confidential information (trade secrets, etc.)
- **§30**: Personal information protected by GDPR
- **§31**: Security or defense concerns
- **§33 (4)**: Attorney-client privilege
- **Other**: Specify legal basis

**Denial Notice Must Include**:
- Legal basis for denial
- Appeal instructions (Ombudsman)
- Partial release option (if applicable)
- Contact for questions

### Limitations

- Only for Danish public administration
- Requires legal expertise for complex cases
- No document translation (Danish only)
- Cannot extend 7-day deadline without legal basis

### When to Use vs. Other Templates

**Use FOI Request when**:
- Legal deadline applies (7 days)
- Document retrieval involved
- Redaction workflow needed
- Appeal process required

**Use Contact Form instead if**:
- Simple question (not document request)
- No legal deadline
- No redaction needed

**Use Building Permit instead if**:
- Approval workflow (not information request)
- Multi-step review with longer timeline

---

## Template Comparison

### Quick Reference Table

| Feature | Building Permit | Contact Form | Company Verification | Address Change | FOI Request |
|---------|----------------|--------------|----------------------|----------------|-------------|
| **Complexity** | Advanced | Simple | Medium | Medium | Medium |
| **Authentication** | Required | None | Required | Required | Optional |
| **Approvals** | Multi-step | None | Automatic | Automatic | Case-by-case |
| **Documents** | Yes (upload) | No | No | No | Yes (release) |
| **Deadline** | 30-90 days | None (SLA) | Real-time | Immediate | 7 days (legal) |
| **Integrations** | SBSYS, ESDH | Email | CVR, CPR | Multiple systems | Email, ESDH |
| **Use Frequency** | Occasional | Frequent | Occasional | Occasional | Rare |

### Complexity Scale

```
Simple       Medium              Advanced
  │            │                    │
  ▼            ▼                    ▼
Contact → FOI Request  →  Building Permit
Form         │
             ▼
        Company Verification
             │
             ▼
        Address Change
```

### Decision Tree: Which Template?

```
Need to route inquiries?
├─ YES → Contact Form
└─ NO
    │
    Need verification only?
    ├─ YES → Company Verification
    └─ NO
        │
        Need document release?
        ├─ YES → FOI Request
        └─ NO
            │
            Multiple system updates?
            ├─ YES → Address Change
            └─ NO → Building Permit
```

---

## Customization Guide

### How to Modify a Template

1. **Copy the template**:
   ```bash
   cp workflows/building_permit.bpmn workflows/my_custom_workflow.bpmn
   ```

2. **Edit metadata**:
   Change process ID and name in XML:
   ```xml
   <bpmn:process id="my_custom_process"
                  name="My Custom Workflow"
                  isExecutable="true">
   ```

3. **Modify workflow steps**:
   - Add/remove tasks
   - Change gateway conditions
   - Adjust timeouts
   - Configure integrations

4. **Test thoroughly**:
   Use test environment before deploying

### Common Customizations

**Add Email Notification**:
```xml
<bpmn:serviceTask id="Task_SendEmail" name="Send Email">
  <bpmn:extensionElements>
    <eca:action type="email">
      <eca:config>
        <eca:to>[citizen:email]</eca:to>
        <eca:subject>Your request status</eca:subject>
      </eca:config>
    </eca:action>
  </bpmn:extensionElements>
</bpmn:serviceTask>
```

**Add Conditional Branch**:
```xml
<bpmn:exclusiveGateway id="Gateway_Amount" name="Amount Check">
  <bpmn:incoming>Flow_1</bpmn:incoming>
  <bpmn:outgoing>Flow_High</bpmn:outgoing>
  <bpmn:outgoing>Flow_Low</bpmn:outgoing>
</bpmn:exclusiveGateway>

<bpmn:sequenceFlow id="Flow_High" sourceRef="Gateway_Amount"
                    targetRef="Task_ManagerApproval">
  <bpmn:conditionExpression>
    ${amount > 100000}
  </bpmn:conditionExpression>
</bpmn:sequenceFlow>
```

**Adjust Timeout**:
```xml
<bpmn:boundaryEvent id="Event_Timeout" name="30 Days">
  <bpmn:timerEventDefinition>
    <bpmn:timeDuration>P30D</bpmn:timeDuration>
  </bpmn:timerEventDefinition>
</bpmn:boundaryEvent>
```

### Creating New Templates

To create a completely new template:

1. **Use BPMN editor**: `/admin/config/workflow/eca/modeler`
2. **Follow BPMN 2.0 standard**
3. **Include required elements**:
   - Start event
   - End event
   - At least one task
   - Process documentation
4. **Test with BpmnTemplateManager**:
   ```php
   $manager->validateTemplate('my_template');
   ```
5. **Add to workflows directory**
6. **Document in this file**

---

## Additional Resources

- **Tutorial**: [Creating an Approval Workflow](tutorials/CREATE_APPROVAL_WORKFLOW.md)
- **Process Guide**: [Approval Process Guide](APPROVAL_PROCESS_GUIDE.md)
- **Admin Guide**: [Municipal Admin Guide](MUNICIPAL_ADMIN_GUIDE.md)
- **BPMN Spec**: [BPMN 2.0 Documentation](https://www.omg.org/spec/BPMN/2.0/)

---

**Questions?** Contact your ÅbenForms administrator or email support@aabenforms.dk
