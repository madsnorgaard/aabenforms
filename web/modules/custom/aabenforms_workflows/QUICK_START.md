# Parent Approval System - Quick Start Guide

## Installation (5 minutes)

```bash
cd /home/mno/ddev-projects/aabenforms/backend

# 1. Clear cache
ddev drush cr

# 2. Verify module enabled
ddev drush pm:list | grep aabenforms_workflows

# 3. Check routes
ddev drush route:list | grep parent_approval

# 4. Verify service
ddev drush devel:services | grep approval_token
```

## Quick Test (2 minutes)

```bash
# Run test script
ddev drush php:script web/modules/custom/aabenforms_workflows/tests/manual/test_approval_flow.php

# Copy the output URLs and test in browser
```

## Manual Test (10 minutes)

### Step 1: Create Submission
```bash
# Navigate to form
open https://aabenforms.ddev.site/forms/parent-request

# Fill with test data:
# - Child Name: "Test Child"
# - Child CPR: "010120-1234"
# - Parent 1 Email: "parent1@test.com"
# - Parent 2 Email: "parent2@test.com"
# - Parents Together: "together"
# - Request Details: "School enrollment"
# Submit
```

### Step 2: Generate Approval URL
```bash
# Get submission ID
ddev drush sqlq "SELECT MAX(sid) FROM webform_submission"

# Generate token and URL (replace 1 with actual SID)
ddev drush php:eval "
\$service = \Drupal::service('aabenforms_workflows.approval_token');
\$token = \$service->generateToken(1, 1);
echo \Drupal::url('aabenforms_workflows.parent_approval', [
  'parent_number' => 1,
  'submission_id' => 1,
  'token' => \$token,
], ['absolute' => TRUE]) . PHP_EOL;
"
```

### Step 3: Test Approval Flow
```bash
# 1. Open URL in browser
# 2. Should see MitID login page
# 3. For testing, skip MitID:
ddev drush php:eval "
\$session = \Drupal::service('session');
\$session->set('mitid_authenticated_parent1', TRUE);
"
# 4. Refresh page - should see approval form
# 5. Select "Approve" and submit
# 6. Verify status updated
```

### Step 4: Verify
```bash
# Check status
ddev drush sqlq "SELECT value FROM webform_submission_data WHERE sid=1 AND name='parent1_status'"

# Check logs
ddev drush watchdog:show --type=aabenforms_workflows --count=5
```

## Architecture at a Glance

```
Webform Submission
       ↓
initial_request_flow (ECA)
       ↓
SendApprovalEmailAction
       ↓
Parent receives email with token
       ↓
Clicks link → ParentApprovalController
       ↓
Token validated → MitID required
       ↓
ParentApprovalForm displayed
       ↓
Parent approves/rejects
       ↓
parent{N}_status updated
       ↓
parent{N}_approval_flow (ECA) triggered
```

## Key URLs

- **Webform:** `/forms/parent-request`
- **Approval:** `/parent-approval/{parent_number}/{submission_id}/{token}`
- **Complete:** `/parent-approval/complete/{action}`

## Key Services

```php
// Token service
$token_service = \Drupal::service('aabenforms_workflows.approval_token');
$token = $token_service->generateToken($sid, $parent_number);
$valid = $token_service->validateToken($sid, $parent_number, $token);

// Load submission
$storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
$submission = $storage->load($sid);

// Update status
$submission->setElementData('parent1_status', 'complete');
$submission->save(); // Triggers workflow
```

## Security Features

- **HMAC-SHA256 tokens** (tamper-proof)
- **7-day expiration** (prevents stale links)
- **MitID authentication** (identity verification)
- **GDPR controls** (CPR masking if parents apart)
- **Audit logging** (all actions tracked)

## Troubleshooting

**Routes not found:**
```bash
ddev drush router:rebuild && ddev drush cr
```

**Token fails:**
```bash
ddev drush cr
ddev drush php:eval "echo \Drupal::service('private_key')->get();"
```

**Emails not sending:**
```bash
ddev composer require drupal/maillog
ddev drush pm:enable maillog
# View: /admin/reports/maillog
```

## Next Steps

1. **Configure MitID integration** (see aabenforms_mitid module)
2. **Set up email templates** (HTML versions)
3. **Test GDPR mode** (parents_together = 'apart')
4. **Configure production mail** (SMTP module)
5. **Add monitoring** (watchdog alerts)

## Documentation

- **Full Docs:** `PARENT_APPROVAL_SYSTEM.md`
- **Implementation:** `APPROVAL_SYSTEM_IMPLEMENTATION.md`
- **Module Help:** `ddev drush help aabenforms_workflows`

## Support

- **Logs:** `ddev drush watchdog:show --type=aabenforms_workflows`
- **Debug:** `ddev drush php:eval "print_r(\Drupal::service('aabenforms_workflows.approval_token'));"`
- **Config:** `ddev drush config:get eca.eca.initial_request_flow`

---

**Version:** 1.0.0
**Last Updated:** 2026-02-02
