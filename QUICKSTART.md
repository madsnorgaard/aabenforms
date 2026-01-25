# ÅbenForms Quickstart Guide

**Get from zero to first workflow in 30 minutes**

---

## ✅ Step 1: Verify Core Module (5 minutes)

### 1.1 Check Installation

```bash
# Verify aabenforms_core is enabled
ddev drush pm:list --filter=aabenforms_core

# Expected output: aabenforms_core (Enabled)
```

### 1.2 Test Core Services

```bash
# Test TenantResolver
ddev drush ev "echo 'Tenant: ' . \Drupal::service('aabenforms_core.tenant_resolver')->getTenantName() . PHP_EOL;"
# Expected: Tenant: Default

# Verify configuration
ddev drush config:get aabenforms_core.settings serviceplatformen.urls.SF1520
# Expected: https://exttest.serviceplatformen.dk/service/CPR/CPRBasicInformation/1

# Check audit log table
ddev drush sql:query "SELECT COUNT(*) as table_exists FROM information_schema.tables WHERE table_schema='db' AND table_name='aabenforms_audit_log';"
# Expected: 1
```

### 1.3 Test Audit Logging

```bash
# Create test audit entry
ddev drush ev "
\$audit = \Drupal::service('aabenforms_core.audit_logger');
\$audit->logCprLookup('0101001234', 'quickstart_test', 'success', ['test' => true]);
echo 'Audit entry created!' . PHP_EOL;
"

# Verify entry was created
ddev drush sql:query "SELECT * FROM aabenforms_audit_log ORDER BY id DESC LIMIT 1;"
# Expected: Shows audit entry with action='cpr_lookup'
```

**✅ Core module verified!**

---

## 🎯 Step 2: Enable Webform + ECA (10 minutes)

### 2.1 Enable Required Modules

```bash
# Enable Webform
ddev drush pm:enable webform webform_ui -y

# Enable ECA + BPMN.iO
ddev drush pm:enable eca eca_base eca_ui bpmn_io -y

# Clear cache
ddev drush cr
```

### 2.2 Verify ECA Installation

```bash
# Check ECA is enabled
ddev drush pm:list --filter=eca

# Expected output:
# - eca (Enabled)
# - eca_base (Enabled)
# - eca_ui (Enabled)
# - bpmn_io (Enabled)
```

### 2.3 Access Admin Interfaces

Open in browser:
- **Webform UI**: `https://aabenforms.ddev.site/admin/structure/webform`
- **ECA Models**: `https://aabenforms.ddev.site/admin/config/workflow/eca`
- **BPMN Modeler**: `https://aabenforms.ddev.site/admin/config/workflow/eca/add/bpmn_io`

**✅ Workflow infrastructure ready!**

---

## 📝 Step 3: Create Your First Webform (5 minutes)

### 3.1 Create Test Form via Drush

```bash
# Create simple contact form
ddev drush ev "
\$webform = \Drupal\webform\Entity\Webform::create([
  'id' => 'test_contact',
  'title' => 'Test Contact Form',
  'status' => 'open',
  'elements' => \"
name:
  '#type': textfield
  '#title': 'Your Name'
  '#required': true

email:
  '#type': email
  '#title': 'Email Address'
  '#required': true

message:
  '#type': textarea
  '#title': 'Message'
  '#required': true
\",
]);
\$webform->save();
echo 'Webform created: /form/test_contact' . PHP_EOL;
"
```

### 3.2 Test the Form

Visit: `https://aabenforms.ddev.site/form/test_contact`

Fill out and submit the form to verify it works.

### 3.3 View Submissions

```bash
# List recent submissions
ddev drush ev "
\$submissions = \Drupal::entityTypeManager()
  ->getStorage('webform_submission')
  ->loadByProperties(['webform_id' => 'test_contact']);
echo 'Total submissions: ' . count(\$submissions) . PHP_EOL;
"
```

**✅ Webform working!**

---

## 🔄 Step 4: Create Your First ECA Workflow (10 minutes)

### 4.1 Create Simple ECA Model

```bash
# Create ECA model that logs webform submissions
ddev drush ev "
\$eca = \Drupal\eca\Entity\Eca::create([
  'id' => 'log_contact_submissions',
  'label' => 'Log Contact Form Submissions',
  'modeller' => 'fallback',
  'status' => TRUE,
  'version' => '1.0.0',
]);
\$eca->save();
echo 'ECA model created!' . PHP_EOL;
"
```

### 4.2 Configure ECA Event

**Via Admin UI** (recommended for BPMN):

1. Go to: `https://aabenforms.ddev.site/admin/config/workflow/eca`
2. Click **"Add ECA model"**
3. Select **"BPMN.iO"** modeler
4. Model ID: `contact_form_workflow`
5. Label: `Contact Form Workflow`

### 4.3 Design BPMN Workflow

In the BPMN.iO modeler, create this simple workflow:

```
[Webform Submission] → [Log Audit Entry] → [Send Email] → [End]
```

**Visual Designer**:
1. **Start Event**: Configure to listen for `webform_submission_insert` event
2. **Service Task**: Add ECA action to log submission
3. **End Event**: Workflow completion

**XML Example** (for reference):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL">
  <bpmn:process id="contact_workflow" isExecutable="true">
    <bpmn:startEvent id="start" name="Form Submitted">
      <bpmn:outgoing>flow1</bpmn:outgoing>
    </bpmn:startEvent>

    <bpmn:serviceTask id="log_submission" name="Log to Audit">
      <bpmn:incoming>flow1</bpmn:incoming>
      <bpmn:outgoing>flow2</bpmn:outgoing>
    </bpmn:serviceTask>

    <bpmn:endEvent id="end" name="Complete">
      <bpmn:incoming>flow2</bpmn:incoming>
    </bpmn:endEvent>

    <bpmn:sequenceFlow id="flow1" sourceRef="start" targetRef="log_submission"/>
    <bpmn:sequenceFlow id="flow2" sourceRef="log_submission" targetRef="end"/>
  </bpmn:process>
</bpmn:definitions>
```

### 4.4 Test Workflow Execution

```bash
# Submit test form
ddev drush ev "
\$webform = \Drupal\webform\Entity\Webform::load('test_contact');
\$values = [
  'name' => 'Test User',
  'email' => 'test@example.com',
  'message' => 'Testing ECA workflow',
];

\$submission = \Drupal\webform\Entity\WebformSubmission::create([
  'webform_id' => 'test_contact',
  'data' => \$values,
]);
\$submission->save();

echo 'Test submission created (ID: ' . \$submission->id() . ')' . PHP_EOL;
"

# Check if audit entry was created by workflow
ddev drush sql:query "SELECT * FROM aabenforms_audit_log ORDER BY id DESC LIMIT 3;"
```

**✅ First workflow executed!**

---

## 🚀 Next Steps: Build Danish Government Integrations

### Option 1: Create CPR Lookup Webform Element

```bash
# Create aabenforms_webform module
mkdir -p web/modules/custom/aabenforms_webform/src/Element

# Module will provide:
# - CprField element (10-digit validation)
# - CvrField element (8-digit validation)
# - DawaAddress element (autocomplete)
```

### Option 2: Create First Production Workflow

**"Citizen Complaint Workflow"** with:
1. **Webform**: Collect complaint + CPR number
2. **ECA Workflow**:
   - Validate MitID authentication
   - Lookup CPR data (SF1520)
   - Create SBSYS case
   - Send Digital Post confirmation (SF1601)
   - Assign to caseworker
   - Purge personal data on completion

### Option 3: Set Up Multi-Tenancy

```bash
# Install Domain module
ddev composer require drupal/domain
ddev drush pm:enable domain domain_access -y

# Create test tenant
ddev drush domain:create aarhus.aabenforms.ddev.site "Aarhus Kommune"

# Configure tenant-specific settings
ddev drush config:set aabenforms_core.tenant.aarhus mitid.client_id "aarhus-test-id"
```

---

## 🧪 Testing Your Setup

### Run All Verification Checks

```bash
#!/bin/bash
# Save as: scripts/verify-install.sh

echo "=== ÅbenForms Installation Verification ==="

# 1. Core module
echo -n "✓ aabenforms_core: "
ddev drush pm:list --filter=aabenforms_core --status=enabled --format=list

# 2. Dependencies
echo -n "✓ key: "
ddev drush pm:list --filter=key --status=enabled --format=list
echo -n "✓ encrypt: "
ddev drush pm:list --filter=encrypt --status=enabled --format=list

# 3. Workflow infrastructure
echo -n "✓ webform: "
ddev drush pm:list --filter=webform --status=enabled --format=list
echo -n "✓ eca: "
ddev drush pm:list --filter=eca --status=enabled --format=list
echo -n "✓ bpmn_io: "
ddev drush pm:list --filter=bpmn_io --status=enabled --format=list

# 4. Services
echo -n "✓ Audit logging service: "
ddev drush ev "echo get_class(\Drupal::service('aabenforms_core.audit_logger')) . PHP_EOL;"

echo -n "✓ Encryption service: "
ddev drush ev "echo get_class(\Drupal::service('aabenforms_core.encryption_service')) . PHP_EOL;"

echo -n "✓ Tenant resolver: "
ddev drush ev "echo get_class(\Drupal::service('aabenforms_core.tenant_resolver')) . PHP_EOL;"

# 5. Database
echo -n "✓ Audit log table: "
ddev drush sql:query "SELECT COUNT(*) FROM information_schema.tables WHERE table_name='aabenforms_audit_log';"

echo ""
echo "=== All checks passed! ✅ ==="
```

Run verification:
```bash
chmod +x scripts/verify-install.sh
./scripts/verify-install.sh
```

---

## 📚 Reference Documentation

### Admin URLs

| Interface | URL |
|-----------|-----|
| Webforms | `/admin/structure/webform` |
| ECA Models | `/admin/config/workflow/eca` |
| BPMN Modeler | `/admin/config/workflow/eca/add/bpmn_io` |
| Configuration | `/admin/config` |
| Reports | `/admin/reports` |

### Drush Commands

```bash
# Module management
ddev drush pm:enable <module>
ddev drush pm:uninstall <module>
ddev drush pm:list --filter=aabenforms

# Configuration
ddev drush config:get aabenforms_core.settings
ddev drush config:set aabenforms_core.settings audit.enabled true

# Testing
ddev drush ev "<php code>"
ddev drush sql:query "<sql>"

# Cache
ddev drush cr  # Clear all caches
```

### Service Access

```php
// In custom code
$audit = \Drupal::service('aabenforms_core.audit_logger');
$encryption = \Drupal::service('aabenforms_core.encryption_service');
$client = \Drupal::service('aabenforms_core.serviceplatformen_client');
$tenant = \Drupal::service('aabenforms_core.tenant_resolver');

// Via dependency injection (recommended)
public function __construct(
  AuditLogger $audit_logger,
  EncryptionService $encryption_service
) {
  $this->auditLogger = $audit_logger;
  $this->encryptionService = $encryption_service;
}
```

---

## 🆘 Troubleshooting

### "Service not found"
```bash
# Rebuild container
ddev drush cr

# Verify service definition
cat web/modules/custom/aabenforms_core/aabenforms_core.services.yml
```

### "Table doesn't exist"
```bash
# Reinstall module
ddev drush pm:uninstall aabenforms_core -y
ddev drush pm:enable aabenforms_core -y
```

### "Class not found"
```bash
# Rebuild autoloader
ddev composer dump-autoload
ddev drush cr
```

---

**Ready to build! 🚀**

**Next**: Let's create the aabenforms_webform module with Danish field types (CPR, CVR, DAWA).
