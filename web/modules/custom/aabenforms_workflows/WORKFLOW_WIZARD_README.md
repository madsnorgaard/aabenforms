# Workflow Template Wizard

A municipality-friendly visual interface for creating approval workflows without writing YAML.

## Overview

The Workflow Template Wizard allows non-technical municipality staff to create complex approval workflows by selecting from pre-built templates and configuring them through a simple 5-step wizard.

## Features

- **Visual Template Browser**: Browse available workflow templates with descriptions and previews
- **Multi-Step Wizard**: Guided 5-step process for workflow creation
- **Field Mapping**: Connect webform fields to workflow variables visually
- **Action Configuration**: Customize email templates and approval pages without code
- **GDPR Compliance**: Built-in data visibility controls
- **Preview Before Activation**: Review complete workflow before going live
- **Instance Management**: View, edit, and delete active workflows

## Architecture

### Services

#### 1. WorkflowTemplateMetadata
**File**: `src/Service/WorkflowTemplateMetadata.php`

Extracts configuration metadata from BPMN templates:
- Template parameters (fields, emails, deadlines, etc.)
- Configurable actions (emails, approvals, notifications)
- Field mapping requirements
- Validation rules

**Key Methods**:
- `getTemplateParameters($template_id)`: Returns all configurable parameters
- `getConfigurableActions($template_id)`: Returns customizable workflow actions
- `validateConfiguration($template_id, $config)`: Validates user configuration
- `getTemplatePreview($template_id)`: Returns workflow preview information

#### 2. WorkflowTemplateInstantiator
**File**: `src/Service/WorkflowTemplateInstantiator.php`

Generates live workflows from templates:
- Creates ECA workflow configurations
- Generates approval page routes
- Creates email template configs
- Manages workflow lifecycle (create/update/delete)

**Key Methods**:
- `instantiate($template_id, $configuration)`: Creates workflow instance
- `deleteInstance($workflow_id)`: Removes workflow instance
- `getInstances()`: Lists all active workflow instances

#### 3. BpmnTemplateManager
**File**: `src/Service/BpmnTemplateManager.php` (existing)

Manages BPMN template files:
- Discovers available templates
- Loads and validates BPMN XML
- Extracts template metadata

### Forms

#### WorkflowTemplateWizardForm
**File**: `src/Form/WorkflowTemplateWizardForm.php`

Multi-step wizard with 5 steps:

1. **Select Template**: Browse and choose from available templates
2. **Configure Webform**: Map webform fields to workflow variables
3. **Configure Actions**: Customize emails, notifications, and approval pages
4. **Data Visibility**: Set GDPR-compliant data access rules
5. **Preview & Activate**: Review and activate the workflow

**Features**:
- AJAX-enabled field mapping updates
- Step validation before proceeding
- Configuration persistence across steps
- Accessibility compliant (WCAG 2.1 AA)

#### WorkflowInstanceDeleteForm
**File**: `src/Form/WorkflowInstanceDeleteForm.php`

Confirmation form for deleting workflow instances with safety checks.

### Controllers

#### TemplateBrowserController
**File**: `src/Controller/TemplateBrowserController.php`

Admin interface at `/admin/aabenforms/workflow-templates`:
- Grid view of available templates
- Table of active workflow instances
- Quick actions (Use Template, Edit, Delete, View Form)

## BPMN Template Metadata

Templates can include configuration parameters in their BPMN documentation:

```xml
<bpmn:process id="building_permit_process" name="Building Permit Application">
  <bpmn:documentation>[category: municipal]
  Building permit workflow description.

  <parameters>
    <parameter id="applicant_email_field" label="Applicant Email Field"
               type="webform_field" required="true" default="email">
      <description>Webform field containing applicant email</description>
    </parameter>

    <parameter id="caseworker_email" label="Case Worker Email"
               type="email" required="true" default="">
      <description>Email for case worker notifications</description>
    </parameter>

    <parameter id="approval_deadline_days" label="Approval Deadline (days)"
               type="integer" required="false" default="30">
      <description>Days before auto-rejection</description>
    </parameter>
  </parameters>
  </bpmn:documentation>
</bpmn:process>
```

### Parameter Types

- `webform_field`: Dropdown of webform fields
- `email`: Email address input
- `integer`: Numeric input
- `boolean`: Checkbox
- `text`: Single-line text
- `textarea`: Multi-line text

## How Municipalities Use It

### Example: Creating a Building Permit Workflow

**Step 1: Access Template Browser**
```
Navigate to: Administration > Configuration > Workflow > Workflow Templates
```

**Step 2: Select Template**
- Click "Use This Template" on "Building Permit Application"
- Preview shows all workflow steps

**Step 3: Configure Webform**
- Select webform: "building_permit_request"
- Map fields:
  - CPR Number Field → `cpr_number`
  - Applicant Email → `email`
  - Property Address → `address`

**Step 4: Configure Actions**
- Set case worker email: `caseworker@municipality.dk`
- Customize approval page:
  - Title: "Building Permit Review"
  - Instructions: "Please review the attached documents..."
- Customize email templates:
  - Subject: "Building Permit Application Received"
  - Body: "Dear [applicant_name], we received your application..."

**Step 5: Data Visibility**
- Select "Restricted" mode for GDPR compliance
- CPR data automatically encrypted

**Step 6: Preview & Activate**
- Review workflow steps
- Name workflow: "Building Permits 2026"
- Click "Create Workflow"

**Result**: Workflow is now active! Form submissions will trigger the complete approval process.

## Configuration Storage

### Template Instances
Stored in config: `aabenforms_workflows.template_instance.{workflow_id}`

```yaml
id: building_permit_2026
label: Building Permits 2026
template_id: building_permit
webform_id: building_permit_request
status: true
created: 1738425600
updated: 1738425600
configuration:
  parameters:
    applicant_email_field: email
    cpr_field: cpr_number
    caseworker_email: caseworker@municipality.dk
    approval_deadline_days: 30
  actions:
    Task_SendApproval:
      subject: Building Permit Approved
      body: Your building permit has been approved...
      recipient: '[submission:values:email]'
  visibility_mode: restricted
routes:
  Task_CaseWorkerReview:
    path: /workflow/building_permit_2026/approval/caseworker/{token}
    controller: Drupal\aabenforms_workflows\Controller\WorkflowApprovalController::approvalPage
    title: Case Worker Review
```

### Generated ECA Workflow
Stored in config: `eca.eca.{workflow_id}`

```yaml
id: building_permit_2026
label: Building Permits 2026
modeller: fallback
version: 1.0.0
events:
  webform_submit:
    plugin: 'content_entity:insert'
    configuration:
      type: webform_submission
actions:
  mitid_validate:
    plugin: aabenforms_mitid_validate
  cpr_lookup:
    plugin: aabenforms_cpr_lookup
  send_email:
    plugin: eca_base_mail
    configuration:
      to: caseworker@municipality.dk
      subject: Building Permit Application Received
```

## Routing

| Route | Path | Purpose |
|-------|------|---------|
| `aabenforms_workflows.template_browser` | `/admin/aabenforms/workflow-templates` | Template browser page |
| `aabenforms_workflows.template_wizard` | `/admin/aabenforms/workflow-templates/wizard` | Wizard form |
| `aabenforms_workflows.instance_delete` | `/admin/aabenforms/workflow-templates/delete/{workflow_id}` | Delete confirmation |
| `aabenforms_workflows.template_select` | `/admin/config/workflow/aabenforms/templates` | Legacy BPMN browser |

## Permissions

All routes require `administer workflows` permission.

## Styling

Custom CSS in `css/workflow-wizard.css` provides:
- Wizard progress indicator
- Template card grid layout
- Status badges and indicators
- Responsive design for mobile/tablet

Library: `aabenforms_workflows/workflow_wizard`

## Testing

### Manual Testing Steps

1. **Access Template Browser**
   ```
   Visit: /admin/aabenforms/workflow-templates
   Verify: Templates display in grid layout
   ```

2. **Start Wizard**
   ```
   Click: "Use This Template" on any template
   Verify: Wizard Step 1 loads
   ```

3. **Navigate Through Steps**
   ```
   Select template → Next
   Select webform → Map fields → Next
   Configure actions → Next
   Set visibility → Next
   Review and activate
   ```

4. **Verify Instance Creation**
   ```
   Check: Template Browser shows new workflow in "Active Workflows"
   Verify: Config exists at /admin/config/development/configuration/single/export
   Check: ECA workflow appears at /admin/config/workflow/eca
   ```

5. **Test Form Submission**
   ```
   Submit webform
   Verify: Workflow executes
   Check: Emails sent
   Verify: Audit logs created
   ```

### Unit Testing

Test classes to create:
- `WorkflowTemplateMetadataTest.php`: Test metadata extraction
- `WorkflowTemplateInstantiatorTest.php`: Test workflow generation
- `WorkflowTemplateWizardFormTest.php`: Test form validation

## Extending the Wizard

### Adding New Parameter Types

In `WorkflowTemplateMetadata::buildParameterElement()`:

```php
case 'custom_type':
  $element = [
    '#type' => 'custom_form_element',
    '#title' => $param['label'],
    '#required' => $param['required'],
  ];
  break;
```

### Creating New Templates

1. Create BPMN file in `workflows/` directory
2. Add metadata in `<bpmn:documentation>` element
3. Define parameters with types and defaults
4. Add default parameters in `WorkflowTemplateMetadata::addDefaultParameters()`

### Custom Instantiation Logic

Override `WorkflowTemplateInstantiator::mapTaskToEcaAction()` for custom task mappings.

## Troubleshooting

### Template Not Appearing
- Check BPMN file is in `workflows/` directory
- Verify BPMN namespace is correct
- Clear Drupal cache: `drush cr`

### Field Mapping Not Updating
- Check webform exists and has fields
- Verify AJAX callback is working (browser console)
- Clear form cache

### Workflow Not Executing
- Check ECA config was created: `/admin/config/workflow/eca`
- Verify webform_id matches configured webform
- Check ECA is enabled and configured

### Configuration Validation Errors
- Check required parameters are filled
- Verify email addresses are valid
- Ensure integer fields contain numbers only

## Future Enhancements

- [ ] Visual BPMN diagram preview (SVG generation)
- [ ] Template import/export functionality
- [ ] Workflow testing sandbox
- [ ] Analytics dashboard (submission rates, approval times)
- [ ] Template marketplace (share templates between municipalities)
- [ ] Multi-language support for wizard
- [ ] Workflow version control
- [ ] Rollback to previous workflow version

## Security Considerations

- All workflow configurations stored in Drupal config (version controlled)
- CPR and sensitive data automatically encrypted via `aabenforms_gdpr`
- Access controlled via permissions
- CSRF protection on all forms
- Input validation on all user-provided data
- Email addresses validated before use
- Token-based approval URLs (cryptographically secure)

## Support

For issues or questions:
- Check BPMN template syntax: https://www.bpmn.org/
- Drupal Form API: https://api.drupal.org/api/drupal/elements
- ECA Documentation: https://www.drupal.org/docs/contributed-modules/eca

## License

GPL-2.0 (same as Drupal)
