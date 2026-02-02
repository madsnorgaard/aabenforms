# BPMN Workflow Development Guide

## Overview

ÅbenForms uses BPMN 2.0 (Business Process Model and Notation) for workflow automation via the ECA (Event-Condition-Action) module. This guide covers creating, customizing, and deploying BPMN workflows for Danish municipal processes.

## Available Templates

ÅbenForms includes 5 production-ready workflow templates:

### 1. Building Permit (`building_permit`)

**Use Case**: Citizen applies for building permit, municipality reviews and approves.

**Process Flow**:
1. Citizen submits application via webform
2. MitID authentication validates identity
3. CPR lookup retrieves person data from Serviceplatformen
4. Municipality staff reviews application (user task)
5. Decision gateway: Approve or Reject
6. Notification sent via Digital Post
7. Audit log entry created

**ECA Actions Used**:
- `aabenforms_mitid_validate`
- `aabenforms_cpr_lookup`
- `aabenforms_audit_log`

**Template Location**: `web/modules/custom/aabenforms_workflows/templates/bpmn/building_permit.bpmn`

---

### 2. Contact Form (`contact_form`)

**Use Case**: Generic citizen contact/inquiry form with email routing.

**Process Flow**:
1. Citizen submits contact form
2. Email notification sent to department
3. Case created in case management system
4. Auto-reply sent to citizen
5. Workflow complete

**ECA Actions Used**:
- Email notification (core ECA action)
- Case creation (custom action)

**Template Location**: `web/modules/custom/aabenforms_workflows/templates/bpmn/contact_form.bpmn`

---

### 3. Company Verification (`company_verification`)

**Use Case**: Verify business registration for contracts/licenses.

**Process Flow**:
1. Business user submits CVR number
2. MitID Erhverv validates business representative
3. CVR lookup retrieves company data from Serviceplatformen
4. Verify company is active and authorized
5. Decision gateway: Verified or Rejected
6. Result stored in database

**ECA Actions Used**:
- `aabenforms_mitid_validate` (Erhverv mode)
- `aabenforms_cvr_lookup`
- `aabenforms_audit_log`

**Template Location**: `web/modules/custom/aabenforms_workflows/templates/bpmn/company_verification.bpmn`

---

### 4. Address Change (`address_change`)

**Use Case**: Citizen notifies municipality of address change.

**Process Flow**:
1. Citizen submits new address
2. DAWA validates address exists
3. CPR lookup verifies current registered address
4. Update internal records
5. Confirmation sent via Digital Post

**ECA Actions Used**:
- DAWA validation (custom action)
- `aabenforms_cpr_lookup`
- Digital Post notification (custom action)

**Template Location**: `web/modules/custom/aabenforms_workflows/templates/bpmn/address_change.bpmn`

---

### 5. Freedom of Information Request (`foi_request`)

**Use Case**: GDPR/FOI request handling with deadline tracking.

**Process Flow**:
1. Request submitted via webform
2. Auto-acknowledge receipt
3. Assign to case handler (user task)
4. Deadline timer (30 days)
5. Document collection and review
6. Response sent via Digital Post
7. Archive in ESDH system

**ECA Actions Used**:
- Email notification
- Deadline timer (ECA core)
- Digital Post notification
- ESDH archiving (custom action)

**Template Location**: `web/modules/custom/aabenforms_workflows/templates/bpmn/foi_request.bpmn`

---

## Creating Custom Templates

### BPMN 2.0 XML Structure

BPMN templates follow the BPMN 2.0 specification with ECA-specific extensions:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
                  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
                  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
                  xmlns:eca="https://drupal.org/project/eca"
                  id="Definitions_1"
                  targetNamespace="http://bpmn.io/schema/bpmn">

  <bpmn:process id="Process_MyWorkflow" name="My Custom Workflow" isExecutable="true">

    <!-- Start Event -->
    <bpmn:startEvent id="StartEvent_1" name="Workflow Started">
      <bpmn:outgoing>Flow_1</bpmn:outgoing>
    </bpmn:startEvent>

    <!-- Service Task (automated) -->
    <bpmn:serviceTask id="Task_Validate" name="Validate Input"
                      eca:action="aabenforms_mitid_validate">
      <bpmn:incoming>Flow_1</bpmn:incoming>
      <bpmn:outgoing>Flow_2</bpmn:outgoing>
    </bpmn:serviceTask>

    <!-- User Task (manual) -->
    <bpmn:userTask id="Task_Review" name="Review Submission">
      <bpmn:incoming>Flow_2</bpmn:incoming>
      <bpmn:outgoing>Flow_3</bpmn:outgoing>
    </bpmn:userTask>

    <!-- Exclusive Gateway (decision) -->
    <bpmn:exclusiveGateway id="Gateway_1" name="Approved?">
      <bpmn:incoming>Flow_3</bpmn:incoming>
      <bpmn:outgoing>Flow_Approve</bpmn:outgoing>
      <bpmn:outgoing>Flow_Reject</bpmn:outgoing>
    </bpmn:exclusiveGateway>

    <!-- End Event -->
    <bpmn:endEvent id="EndEvent_1" name="Workflow Complete">
      <bpmn:incoming>Flow_Approve</bpmn:incoming>
    </bpmn:endEvent>

    <!-- Sequence Flows -->
    <bpmn:sequenceFlow id="Flow_1" sourceRef="StartEvent_1" targetRef="Task_Validate"/>
    <bpmn:sequenceFlow id="Flow_2" sourceRef="Task_Validate" targetRef="Task_Review"/>
    <bpmn:sequenceFlow id="Flow_3" sourceRef="Task_Review" targetRef="Gateway_1"/>
    <bpmn:sequenceFlow id="Flow_Approve" sourceRef="Gateway_1" targetRef="EndEvent_1">
      <bpmn:conditionExpression>approved == true</bpmn:conditionExpression>
    </bpmn:sequenceFlow>
    <bpmn:sequenceFlow id="Flow_Reject" sourceRef="Gateway_1" targetRef="EndEvent_2">
      <bpmn:conditionExpression>approved == false</bpmn:conditionExpression>
    </bpmn:sequenceFlow>

  </bpmn:process>

  <!-- Visual Diagram (optional, for bpmn.io renderer) -->
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_MyWorkflow">
      <!-- Shape definitions... -->
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>

</bpmn:definitions>
```

### BPMN Element Types

| Element | Type | Purpose |
|---------|------|---------|
| `startEvent` | Event | Triggers workflow (form submission, cron, API call) |
| `serviceTask` | Task | Automated action (ECA plugin execution) |
| `userTask` | Task | Manual task requiring human input |
| `exclusiveGateway` | Gateway | Decision point (if/else logic) |
| `parallelGateway` | Gateway | Split into parallel paths |
| `endEvent` | Event | Workflow completion |
| `sequenceFlow` | Flow | Connects elements, defines process order |

### Using BpmnTemplateManager

The `BpmnTemplateManager` service provides PHP API for template management:

```php
<?php

use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;

// Get service
$templateManager = \Drupal::service('aabenforms_workflows.bpmn_template_manager');

// List available templates
$templates = $templateManager->getAvailableTemplates();
// Returns: ['building_permit', 'contact_form', 'company_verification', ...]

// Load template XML
$xml = $templateManager->loadTemplate('building_permit');

// Validate template
$isValid = $templateManager->validateTemplate($xml);
if (!$isValid) {
  $errors = $templateManager->getValidationErrors();
  foreach ($errors as $error) {
    \Drupal::messenger()->addError($error);
  }
}

// Save new template
$customXml = file_get_contents('/path/to/my_workflow.bpmn');
$templateManager->saveTemplate('my_workflow', $customXml);

// Export template (for sharing or backup)
$exported = $templateManager->exportTemplate('building_permit');
file_put_contents('/tmp/building_permit.bpmn', $exported);

// Delete template
$templateManager->deleteTemplate('obsolete_workflow');
```

### Template Validation

The template manager validates BPMN XML against:

1. **XML Well-Formedness**: Valid XML structure
2. **BPMN 2.0 Schema**: Conforms to BPMN specification
3. **Required Elements**: Must have process, startEvent, endEvent
4. **Sequence Flow Integrity**: All flows have valid source/target
5. **ECA Action Existence**: Referenced ECA plugins must exist

**Common Validation Errors**:

```
Error: Missing required element: bpmn:startEvent
Fix: Add <bpmn:startEvent> to process

Error: Invalid sequence flow: sourceRef="Task_1" does not exist
Fix: Ensure Task_1 is defined before referencing it

Error: Unknown ECA action: aabenforms_invalid_action
Fix: Use valid action plugin ID (check /admin/config/workflow/eca)

Error: Exclusive gateway must have at least 2 outgoing flows
Fix: Add multiple <bpmn:sequenceFlow> from gateway
```

### Importing/Exporting via Admin UI

#### Importing Templates

1. Navigate to: **Configuration > Workflows > BPMN Templates**
   - URL: `/admin/config/workflow/bpmn-templates`

2. Click **"Import Template"** button

3. Choose import method:
   - **Upload File**: Select `.bpmn` file from your computer
   - **Paste XML**: Copy/paste BPMN XML content

4. Preview template:
   - Visual BPMN diagram rendered via bpmn.io
   - Element list showing tasks, gateways, events

5. Configure template:
   - **Template ID**: Unique machine name (e.g., `my_custom_workflow`)
   - **Label**: Human-readable name (e.g., "My Custom Workflow")
   - **Description**: Brief summary of workflow purpose

6. Click **"Save Template"**

#### Exporting Templates

1. Navigate to: **Configuration > Workflows > BPMN Templates**

2. Find template in list

3. Click **"Export"** button

4. Choose export format:
   - **Download .bpmn**: Save as XML file
   - **Copy to Clipboard**: For pasting elsewhere

5. Use exported file:
   - Share with other ÅbenForms installations
   - Commit to version control (Git)
   - Edit in external BPMN modeler (Camunda Modeler, bpmn.io)

## ECA Action Plugins

ÅbenForms provides 4 custom ECA action plugins for Danish government integrations:

### 1. MitID Validate (`aabenforms_mitid_validate`)

**Purpose**: Validates MitID authentication token and retrieves user identity.

**Configuration**:
```php
[
  'token' => '[webform_submission:values:mitid_token]',
  'level' => 'SUBSTANTIAL', // or 'HIGH'
  'mode' => 'private', // or 'business'
]
```

**Returns**:
- `cpr`: User's CPR number (encrypted)
- `name`: Full name
- `validated_at`: ISO 8601 timestamp
- `assurance_level`: Actual level achieved

**Example Usage**:
```xml
<bpmn:serviceTask id="Task_MitID" name="Validate MitID"
                  eca:action="aabenforms_mitid_validate"
                  eca:config='{"token": "[submission:mitid_token]", "level": "SUBSTANTIAL"}'>
```

---

### 2. CPR Lookup (`aabenforms_cpr_lookup`)

**Purpose**: Queries Serviceplatformen SF1520 (CPR) for person master data.

**Configuration**:
```php
[
  'cpr' => '[submission:cpr_number]',
  'fields' => ['address', 'name', 'family'],
]
```

**Returns**:
- `name`: Person's official name
- `address`: Current registered address
- `family`: Family relations (spouse, children)
- `status`: Active/Deceased/Emigrated

**Example Usage**:
```xml
<bpmn:serviceTask id="Task_CPR" name="Lookup Person Data"
                  eca:action="aabenforms_cpr_lookup"
                  eca:config='{"cpr": "[submission:cpr]", "fields": ["address", "name"]}'>
```

**GDPR Compliance**: All CPR lookups are automatically logged via audit system.

---

### 3. CVR Lookup (`aabenforms_cvr_lookup`)

**Purpose**: Queries Serviceplatformen SF1530 (CVR) for company data.

**Configuration**:
```php
[
  'cvr' => '[submission:cvr_number]',
  'include_units' => TRUE, // Include P-numbers
]
```

**Returns**:
- `name`: Company legal name
- `status`: Active/Dissolved/Under liquidation
- `industry_code`: NACE industry classification
- `units`: Array of production units (P-numbers)

**Example Usage**:
```xml
<bpmn:serviceTask id="Task_CVR" name="Lookup Company"
                  eca:action="aabenforms_cvr_lookup"
                  eca:config='{"cvr": "[submission:cvr]", "include_units": true}'>
```

---

### 4. Audit Log (`aabenforms_audit_log`)

**Purpose**: Creates GDPR-compliant audit log entry for workflow actions.

**Configuration**:
```php
[
  'action' => 'cpr_lookup',
  'entity_type' => 'webform_submission',
  'entity_id' => '[submission:id]',
  'metadata' => [
    'cpr' => '[submission:cpr]',
    'purpose' => 'Building permit verification',
  ],
]
```

**Stored Fields**:
- `timestamp`: When action occurred
- `user_id`: User who triggered action
- `action`: Action type (cpr_lookup, cvr_lookup, etc.)
- `entity_type` / `entity_id`: Related entity
- `ip_address`: Client IP (for security)
- `metadata`: JSON blob with context

**Example Usage**:
```xml
<bpmn:serviceTask id="Task_Audit" name="Log Action"
                  eca:action="aabenforms_audit_log"
                  eca:config='{"action": "cpr_lookup", "entity_id": "[submission:id]"}'>
```

**Retention**: Audit logs are retained per GDPR data retention policies (default: 5 years).

---

## Best Practices

### 1. Always Validate Input
Start workflows with MitID validation for citizen identity verification:
```xml
<bpmn:serviceTask id="Task_MitID" name="Validate Identity"
                  eca:action="aabenforms_mitid_validate">
```

### 2. Audit Sensitive Operations
Log all CPR/CVR lookups for GDPR compliance:
```xml
<bpmn:serviceTask id="Task_Audit" name="Log CPR Lookup"
                  eca:action="aabenforms_audit_log"
                  eca:config='{"action": "cpr_lookup"}'>
```

### 3. Use Descriptive Names
Make BPMN elements human-readable:
- Good: `<bpmn:userTask name="Municipality Reviews Application">`
- Bad: `<bpmn:userTask name="Task 3">`

### 4. Add Error Handling
Include boundary events for error scenarios:
```xml
<bpmn:boundaryEvent id="Error_Timeout" attachedToRef="Task_CPR">
  <bpmn:errorEventDefinition errorRef="Error_ServiceTimeout"/>
</bpmn:boundaryEvent>
```

### 5. Version Control Templates
Commit `.bpmn` files to Git for change tracking:
```bash
git add web/modules/custom/aabenforms_workflows/templates/bpmn/
git commit -m "Update building permit workflow: Add CVR validation step"
```

### 6. Test Before Deployment
Use ECA debugger to test workflows:
```bash
ddev drush eca:test building_permit --simulate
```

---

## Troubleshooting

### Template Won't Import

**Error**: "Invalid BPMN XML: Missing required element"

**Solution**: Ensure XML has required elements:
```xml
<bpmn:startEvent id="StartEvent_1"/>
<bpmn:endEvent id="EndEvent_1"/>
<bpmn:process id="Process_1"/>
```

---

### ECA Action Not Found

**Error**: "Unknown ECA action: aabenforms_cpr_lookup"

**Solution**: Enable the module providing the action:
```bash
ddev drush pm:enable aabenforms_cpr
ddev drush cr
```

---

### Workflow Doesn't Trigger

**Error**: Workflow doesn't start when form submitted

**Solution**: Check ECA event configuration:
1. Navigate to **Configuration > Workflows > ECA**
2. Verify event trigger: `webform_submission:create`
3. Check conditions (e.g., form ID matches)

---

## Additional Resources

- **BPMN 2.0 Specification**: https://www.omg.org/spec/BPMN/2.0/
- **ECA Module Documentation**: https://www.drupal.org/docs/contributed-modules/eca
- **bpmn.io Modeler**: https://demo.bpmn.io/ (visual BPMN editor)
- **Camunda BPMN Tutorial**: https://camunda.com/bpmn/ (excellent BPMN learning resource)

---

**Last Updated**: 2026-02-01 (Phase 3 completion)
