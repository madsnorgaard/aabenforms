# Parent Approval System - Implementation Summary

## Overview

Complete implementation of the parent approval page system for ÅbenForms. This system enables secure, GDPR-compliant dual parent approval workflows with MitID authentication for municipal requests.

## Files Created

### 1. Core Services

#### `/web/modules/custom/aabenforms_workflows/src/Service/ApprovalTokenService.php`
**Purpose:** Generates and validates secure HMAC-based tokens for approval links.

**Key Features:**
- HMAC-SHA256 signing using Drupal's private key
- 7-day token expiration
- Tamper-proof URL parameters
- Token validation with timing-safe comparison

**API:**
```php
generateToken($submission_id, $parent_number, $timestamp = null): string
validateToken($submission_id, $parent_number, $token): bool
isTokenExpired($token): bool
getTokenTimestamp($token): ?int
```

### 2. Controllers

#### `/web/modules/custom/aabenforms_workflows/src/Controller/ParentApprovalController.php`
**Purpose:** Handles approval page routing and rendering.

**Responsibilities:**
- Token validation and security checks
- Submission loading and status verification
- MitID authentication check
- GDPR-compliant data masking
- Form rendering
- Completion page

**Routes:**
- `approvalPage($parent_number, $submission_id, $token, $request)`
- `completePage($action)`

### 3. Forms

#### `/web/modules/custom/aabenforms_workflows/src/Form/ParentApprovalForm.php`
**Purpose:** Parent approval/rejection form with GDPR controls.

**Features:**
- Displays child information (CPR masked if parents apart)
- Shows request details
- Approve/reject radio buttons
- Optional comments field
- Token re-validation on submit
- Updates submission status fields (triggers ECA workflows)

### 4. ECA Actions

#### `/web/modules/custom/aabenforms_workflows/src/Plugin/Action/SendApprovalEmailAction.php`
**Purpose:** ECA action plugin for sending approval emails.

**Configuration:**
- `parent_number`: Which parent (1 or 2)
- `email_field`: Webform field with parent email
- `submission_token`: Token name for submission entity

**Execution:**
- Generates secure token
- Builds approval URL
- Sends email via Drupal mail system
- Logs action for audit trail

### 5. Workflows

#### `/web/modules/custom/aabenforms_workflows/config/install/eca.eca.initial_request_flow.yml`
**Purpose:** Triggers on webform submission creation to send approval emails.

**Flow:**
```yaml
Event: webform:insert (parent_request_form)
  ↓
Condition: Check parent1_email not empty
  ↓
Action: Send Parent 1 email
  ↓
Action: Send Parent 2 email
  ↓
Action: Audit log
```

### 6. Templates

#### `/web/modules/custom/aabenforms_workflows/templates/parent-approval-login.html.twig`
**Purpose:** MitID login page template.

**Variables:**
- `child_name`: Child's name
- `parent_number`: Parent number (1 or 2)
- `request_summary`: Brief request summary
- `mitid_login_url`: MitID authentication URL

#### `/web/modules/custom/aabenforms_workflows/templates/parent-approval-page.html.twig`
**Purpose:** Approval form page template.

**Variables:**
- `form`: Rendered approval form

### 7. Assets

#### `/web/modules/custom/aabenforms_workflows/css/parent-approval.css`
**Purpose:** Styles for approval pages.

**Features:**
- Clean, professional design
- Security notices styling
- Responsive layout
- Accessible color contrast
- Mobile-friendly

#### `/web/modules/custom/aabenforms_workflows/js/parent-approval.js`
**Purpose:** Client-side behavior for approval pages.

**Features:**
- Confirmation dialog for rejection
- Form validation
- Double-submit prevention
- Loading states
- Auto-focus

### 8. Configuration Updates

#### `/web/modules/custom/aabenforms_workflows/aabenforms_workflows.routing.yml`
**Added Routes:**
```yaml
aabenforms_workflows.parent_approval:
  path: '/parent-approval/{parent_number}/{submission_id}/{token}'

aabenforms_workflows.parent_approval_complete:
  path: '/parent-approval/complete/{action}'
```

#### `/web/modules/custom/aabenforms_workflows/aabenforms_workflows.services.yml`
**Added Service:**
```yaml
aabenforms_workflows.approval_token:
  class: Drupal\aabenforms_workflows\Service\ApprovalTokenService
  arguments: ['@private_key', '@logger.factory']
```

#### `/web/modules/custom/aabenforms_workflows/aabenforms_workflows.module`
**Added Hooks:**
- `hook_mail()`: Email template for parent approvals
- `hook_theme()`: Template definitions

#### `/web/modules/custom/aabenforms_workflows/aabenforms_workflows.libraries.yml`
**Added Library:**
```yaml
parent-approval:
  css: css/parent-approval.css
  js: js/parent-approval.js
```

#### `/web/modules/custom/aabenforms_workflows/config/install/webform.webform.parent_request_form.yml`
**Added Fields:**
- `parent1_comments`: Hidden field for parent 1 comments
- `parent2_comments`: Hidden field for parent 2 comments

### 9. Documentation

#### `/web/modules/custom/aabenforms_workflows/PARENT_APPROVAL_SYSTEM.md`
**Comprehensive documentation including:**
- Architecture overview
- Workflow sequences
- Security features
- Data flow diagrams
- Testing scenarios
- Troubleshooting guide
- API reference

#### `/home/mno/ddev-projects/aabenforms/backend/APPROVAL_SYSTEM_IMPLEMENTATION.md` (this file)
**Implementation summary and deployment guide.**

### 10. Testing Tools

#### `/web/modules/custom/aabenforms_workflows/tests/manual/test_approval_flow.php`
**Manual test script for:**
- Creating test submissions
- Generating approval URLs
- Validating tokens
- Testing security checks

## Workflow Architecture

### System Flow

```
┌─────────────────────────────────────────────────────────────┐
│ 1. INITIAL SUBMISSION                                       │
│                                                             │
│  User submits parent_request_form                          │
│         ↓                                                   │
│  initial_request_flow triggered (webform:insert)           │
│         ↓                                                   │
│  SendApprovalEmailAction (Parent 1)                        │
│         ↓                                                   │
│  SendApprovalEmailAction (Parent 2)                        │
│         ↓                                                   │
│  Both parents receive emails with secure links             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 2. PARENT APPROVAL PROCESS                                  │
│                                                             │
│  Parent clicks link → Token validated                       │
│         ↓                                                   │
│  Submission status checked                                  │
│         ↓                                                   │
│  MitID authentication required                              │
│         ↓                                                   │
│  Approval form displayed (GDPR controls)                   │
│         ↓                                                   │
│  Parent submits decision                                    │
│         ↓                                                   │
│  parent{N}_status updated to 'complete' or 'rejected'      │
│         ↓                                                   │
│  parent{N}_approval_flow triggered (entity:update)         │
│         ↓                                                   │
│  MitID validation, CPR lookup, audit logging               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 3. DUAL APPROVAL COMPLETE                                   │
│                                                             │
│  Both parent1_status & parent2_status = 'complete'         │
│         ↓                                                   │
│  caseworker_review_flow triggered                          │
│         ↓                                                   │
│  Case worker reviews and makes final decision              │
└─────────────────────────────────────────────────────────────┘
```

### Security Architecture

```
┌────────────────────────────────────────────────────────────┐
│ TOKEN GENERATION                                           │
│                                                            │
│  Data: submission_id:parent_number:timestamp               │
│         ↓                                                  │
│  HMAC-SHA256 with Drupal private key                      │
│         ↓                                                  │
│  Base64(hash:timestamp)                                    │
│         ↓                                                  │
│  URL: /parent-approval/1/123/TOKEN                        │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ TOKEN VALIDATION                                           │
│                                                            │
│  1. Base64 decode                                         │
│  2. Extract hash and timestamp                            │
│  3. Check expiration (7 days)                             │
│  4. Regenerate hash from URL parameters                   │
│  5. Timing-safe comparison (hash_equals)                  │
│  6. Grant/deny access                                     │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ GDPR CONTROLS                                              │
│                                                            │
│  parents_together = 'together'                            │
│    → Full CPR visible                                     │
│    → All request details visible                          │
│                                                            │
│  parents_together = 'apart'                               │
│    → CPR masked: DDMMYY-XXX[last digit]                  │
│    → Limited data visibility                              │
└────────────────────────────────────────────────────────────┘
```

## Installation & Deployment

### Prerequisites

- Drupal 11.3.2+
- PHP 8.4+
- ECA module 2.1.18+
- Webform module 6.3.0+
- aabenforms_core module

### Step 1: Enable Module

```bash
cd /home/mno/ddev-projects/aabenforms/backend

# Clear cache
ddev drush cr

# Enable module (if not already enabled)
ddev drush pm:enable aabenforms_workflows

# Import configuration
ddev drush config:import -y

# Clear cache again
ddev drush cr
```

### Step 2: Verify Installation

```bash
# Check routes
ddev drush route:list | grep parent_approval

# Expected output:
# aabenforms_workflows.parent_approval
# aabenforms_workflows.parent_approval_complete

# Check service
ddev drush devel:services | grep approval_token

# Expected output:
# aabenforms_workflows.approval_token

# Check webform
ddev drush config:get webform.webform.parent_request_form

# Check workflows
ddev drush config:get eca.eca.initial_request_flow
ddev drush config:get eca.eca.parent1_approval_flow
ddev drush config:get eca.eca.parent2_approval_flow
```

### Step 3: Configure Email System

```bash
# For development/testing, install maillog
ddev composer require drupal/maillog
ddev drush pm:enable maillog

# Or configure SMTP
ddev composer require drupal/smtp
ddev drush pm:enable smtp
ddev drush config:set smtp.settings smtp_on true
```

### Step 4: Run Test Script

```bash
# Run manual test
ddev drush php:script web/modules/custom/aabenforms_workflows/tests/manual/test_approval_flow.php

# This will:
# - Create test submission
# - Generate approval URLs
# - Validate tokens
# - Output URLs for manual testing
```

### Step 5: Manual Testing

1. **Create Test Submission:**
   - Navigate to: `https://aabenforms.ddev.site/forms/parent-request`
   - Fill form with test data
   - Submit

2. **Check Emails:**
   - View maillog: `/admin/reports/maillog`
   - Verify approval emails sent to both parents

3. **Test Approval Flow:**
   - Copy approval URL from email
   - Open in browser
   - Should see MitID login page
   - (For testing) Manually set session:
     ```php
     $session = \Drupal::request()->getSession();
     $session->set('mitid_authenticated_parent1', TRUE);
     ```
   - Refresh page - should see approval form
   - Submit approval
   - Verify status updated

4. **Verify Workflow Triggers:**
   ```bash
   # Check logs
   ddev drush watchdog:show --type=aabenforms_workflows

   # Check submission status
   ddev drush sqlq "SELECT name, value FROM webform_submission_data WHERE sid=1 AND name LIKE 'parent%_status'"
   ```

## End-to-End Testing Guide

### Test Case 1: Happy Path (Parents Together)

**Scenario:** Both parents live together, both approve.

```bash
# 1. Create submission
# Navigate to form, fill with:
# - Child Name: "Emma Hansen"
# - Child CPR: "150810-2345"
# - Parent 1 Email: "parent1@test.com"
# - Parent 2 Email: "parent2@test.com"
# - Parents Together: "together"
# - Request Details: "Enrollment in after-school program"

# 2. Check emails sent
ddev drush watchdog:show --type=mail

# 3. Get approval URLs
ddev drush php:eval "
  \$service = \Drupal::service('aabenforms_workflows.approval_token');
  \$token1 = \$service->generateToken(1, 1);
  echo \Drupal::url('aabenforms_workflows.parent_approval', [
    'parent_number' => 1,
    'submission_id' => 1,
    'token' => \$token1,
  ], ['absolute' => TRUE]) . PHP_EOL;
"

# 4. Open URL in browser
# Should see full CPR (not masked)

# 5. Approve request
# Select "Approve"
# Add comment: "Approved for after-school program"
# Submit

# 6. Verify status
ddev drush sqlq "SELECT value FROM webform_submission_data WHERE sid=1 AND name='parent1_status'"
# Should show: s:8:"complete"
```

### Test Case 2: GDPR Mode (Parents Apart)

**Scenario:** Parents live apart, limited data visibility.

```bash
# Submit form with:
# - Parents Together: "apart"
# - Same other data

# Access approval URL
# Should see masked CPR: 150810-XXX5
# (Only birthdate and last digit visible)
```

### Test Case 3: Security Tests

**Test 3.1: Expired Token**
```bash
ddev drush php:eval "
  \$service = \Drupal::service('aabenforms_workflows.approval_token');
  \$old_time = time() - (8 * 24 * 60 * 60); // 8 days ago
  \$token = \$service->generateToken(1, 1, \$old_time);
  echo \Drupal::url('aabenforms_workflows.parent_approval', [
    'parent_number' => 1,
    'submission_id' => 1,
    'token' => \$token,
  ], ['absolute' => TRUE]) . PHP_EOL;
"
# Access URL - should show "expired link" message
```

**Test 3.2: Tampered Token**
```bash
# Generate valid token
# Modify one character
# Access URL - should show "access denied"
```

**Test 3.3: Wrong Parent Number**
```bash
# Generate token for parent 1
# Use in URL with parent_number=2
# Should fail validation
```

**Test 3.4: Already Approved**
```bash
# Approve submission
# Try to access URL again
# Should show "already processed" message
```

### Test Case 4: Rejection Flow

**Scenario:** Parent rejects request.

```bash
# Access approval URL
# Select "Reject"
# Add comment: "Cannot approve due to schedule conflict"
# JavaScript should show confirmation dialog
# Confirm rejection
# Submit

# Verify status
ddev drush sqlq "SELECT value FROM webform_submission_data WHERE sid=1 AND name='parent1_status'"
# Should show: s:8:"rejected"

# Check comments saved
ddev drush sqlq "SELECT value FROM webform_submission_data WHERE sid=1 AND name='parent1_comments'"
```

### Test Case 5: Dual Approval Complete

**Scenario:** Both parents approve, case worker review triggered.

```bash
# Parent 1 approves
# Parent 2 approves

# Check both statuses
ddev drush sqlq "SELECT name, value FROM webform_submission_data WHERE sid=1 AND name IN ('parent1_status', 'parent2_status')"

# Should show both as 'complete'

# Verify caseworker_review_flow triggered
ddev drush watchdog:show --type=eca | grep caseworker
```

## Integration Points

### With Existing Workflows

The approval system integrates with existing ECA workflows:

- **parent1_approval_flow.yml**: Triggered when parent1_status changes
- **parent2_approval_flow.yml**: Triggered when parent2_status changes
- **caseworker_review_flow.yml**: Triggered when both parents complete

### With MitID Module

The system expects MitID authentication:

```php
// In ParentApprovalController
$session = $request->getSession();
$mitid_authenticated = $session->get("mitid_authenticated_parent{$parent_number}");
```

**Integration TODO:**
- Create `aabenforms_workflows.parent_approval_mitid` route
- Redirect to MitID OIDC flow
- Store session data on callback
- Redirect back to approval page

### With Audit Logging

All actions are logged:

```php
// In SendApprovalEmailAction
$this->log('Approval email sent to parent @parent at @email', [
  '@parent' => $parent_number,
  '@email' => $parent_email,
]);

// In ParentApprovalForm
$this->logger->info('Parent @parent @action submission @sid', [
  '@parent' => $parent_number,
  '@action' => $new_status,
  '@sid' => $submission_id,
]);
```

## Production Considerations

### Security

1. **Private Key:** Ensure Drupal private key is properly set
   ```bash
   ddev drush php:eval "echo \Drupal::service('private_key')->get();"
   ```

2. **HTTPS:** Always use HTTPS in production for approval URLs

3. **Rate Limiting:** Consider adding rate limiting to prevent abuse

4. **Session Security:** Configure secure session cookies

### Performance

1. **Email Queue:** Use queue for email sending in production
   ```bash
   ddev composer require drupal/queue_mail
   ```

2. **Caching:** Approval pages should NOT be cached (user-specific)

3. **Database Indexes:** Ensure indexes on webform_submission_data

### Monitoring

1. **Watchdog:** Monitor approval logs
   ```bash
   ddev drush watchdog:show --type=aabenforms_workflows
   ```

2. **Email Delivery:** Monitor mail delivery failures

3. **Token Validation Failures:** Alert on unusual token validation failures

### Backup & Recovery

1. **Database Backups:** Regular backups of webform submissions

2. **Email Archives:** Archive sent approval emails

3. **Audit Trail:** Preserve audit logs per GDPR requirements

## Known Limitations

1. **MitID Integration:** Requires separate aabenforms_mitid module configuration

2. **Email Delivery:** Depends on site mail configuration

3. **Session Management:** Requires proper session handling

4. **Mobile Support:** Templates should be tested on mobile devices

5. **Internationalization:** Currently Danish only, needs i18n for other languages

## Future Enhancements

### Phase 1 (Immediate)
- [ ] Complete MitID integration route
- [ ] Add email templates (HTML)
- [ ] Mobile app API endpoints
- [ ] Admin dashboard for pending approvals

### Phase 2 (Near-term)
- [ ] SMS notifications as alternative to email
- [ ] Reminder emails after 3 days
- [ ] Bulk approval operations
- [ ] Parent delegation feature

### Phase 3 (Long-term)
- [ ] Multi-language support
- [ ] Advanced workflow conditions
- [ ] Integration with case management systems
- [ ] Analytics and reporting

## Support & Troubleshooting

### Common Issues

**Problem:** Routes not found
```bash
# Solution:
ddev drush router:rebuild
ddev drush cr
```

**Problem:** Token validation fails
```bash
# Solution:
ddev drush cr
ddev drush php:eval "echo \Drupal::service('private_key')->get();"
# If empty, regenerate:
ddev drush php:eval "\Drupal::service('private_key')->get(TRUE);"
```

**Problem:** Emails not sending
```bash
# Solution:
ddev drush config:get system.mail
ddev drush pm:enable maillog
# Check: /admin/reports/maillog
```

**Problem:** Templates not rendering
```bash
# Solution:
ddev drush cr
ddev drush twig:debug
ddev drush cache:clear render
```

### Getting Help

- Documentation: See PARENT_APPROVAL_SYSTEM.md
- Issues: GitHub issue tracker
- Logs: `ddev drush watchdog:show --type=aabenforms_workflows`

## License

GPL-2.0

## Credits

Built for ÅbenForms - Open-source workflow automation for Danish municipalities.

---

**Implementation Date:** 2026-02-02
**Version:** 1.0.0
**Status:** Complete and Ready for Testing
