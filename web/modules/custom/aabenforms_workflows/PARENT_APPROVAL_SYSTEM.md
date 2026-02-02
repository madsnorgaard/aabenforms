# Parent Approval System Documentation

## Overview

The Parent Approval System provides a complete workflow for municipal requests requiring dual parent approval with MitID authentication. This system implements secure, GDPR-compliant approval workflows with field-level data visibility control.

## Architecture

### Components

1. **Routes** (`aabenforms_workflows.routing.yml`)
   - `/parent-approval/{parent_number}/{submission_id}/{token}` - Main approval page
   - `/parent-approval/complete/{action}` - Confirmation page

2. **Controller** (`ParentApprovalController.php`)
   - Token validation
   - MitID authentication check
   - Submission status verification
   - Form rendering with GDPR controls

3. **Form** (`ParentApprovalForm.php`)
   - Display child information (CPR masked if parents apart)
   - Show request details
   - Approval/rejection with comments
   - Status field updates triggering ECA workflows

4. **Token Service** (`ApprovalTokenService.php`)
   - HMAC-SHA256 based token generation
   - 7-day token expiration
   - Tamper-proof URL parameters

5. **Email Action** (`SendApprovalEmailAction.php`)
   - ECA action plugin for sending approval emails
   - Token generation and URL building
   - Configurable parent number and email field

6. **ECA Workflows**
   - `initial_request_flow` - Sends emails on submission creation
   - `parent1_approval_flow` - Triggered by parent1_status field update
   - `parent2_approval_flow` - Triggered by parent2_status field update
   - `caseworker_review_flow` - Final review after both parents approve

## Workflow Sequence

### 1. Initial Submission
```
User submits parent_request_form
    ↓
initial_request_flow triggered (webform:insert event)
    ↓
send_parent1_email action executes
    ↓
send_parent2_email action executes
    ↓
Both parents receive emails with secure links
```

### 2. Parent Approval Process
```
Parent clicks link in email
    ↓
Token validated by ApprovalTokenService
    ↓
Submission loaded and status checked
    ↓
If not authenticated: Show MitID login page
    ↓
After MitID auth: Show approval form
    ↓
Parent submits approval/rejection
    ↓
parent{N}_status field updated
    ↓
parent{N}_approval_flow workflow triggers
    ↓
MitID validation, CPR lookup, audit logging
    ↓
Case worker notified
```

### 3. Dual Approval Complete
```
Both parent1_status and parent2_status = 'complete'
    ↓
caseworker_review_flow triggers
    ↓
Case worker reviews and makes final decision
```

## Security Features

### Token Security
- **HMAC-SHA256**: Uses Drupal's private key for signing
- **Timestamp-based expiration**: 7-day validity
- **Tamper-proof**: Hash validation ensures URL integrity
- **Replay protection**: Tokens tied to specific submission and parent

### GDPR Compliance
- **Data minimization**: CPR numbers masked if parents apart
- **Access control**: Parents only see their own approval status
- **Audit logging**: All actions logged via AuditLogAction
- **Consent**: Explicit approval/rejection recorded with comments

### Session Security
- **MitID authentication**: Required before viewing sensitive data
- **Session validation**: Checked on every page load
- **CSRF protection**: Drupal form API tokens

## Data Flow

### Webform Submission Fields
```yaml
child_name: string
child_cpr: string (CPR field type)
parent1_email: email
parent2_email: email
parents_together: radios (together|apart)
request_details: textarea
caseworker_notes: textarea (admin only)
parent1_status: hidden (pending|complete|rejected)
parent1_comments: hidden
parent2_status: hidden (pending|complete|rejected)
parent2_comments: hidden
caseworker_status: hidden (pending|complete|rejected)
```

### Token Structure
```
Base64(HMAC-SHA256(submission_id:parent_number:timestamp):timestamp)
```

Example:
```
aGFzaF92YWx1ZV9oZXJl:1738483200
```

### Email Template Variables
```php
$params = [
  'parent_number' => 1,
  'child_name' => 'John Doe',
  'request_details' => 'Request description...',
  'approval_url' => 'https://example.com/parent-approval/1/123/token',
  'deadline' => 'February 9, 2026',
  'submission_id' => 123,
];
```

## Testing the System

### Prerequisites
```bash
# Enable module
ddev drush pm:enable aabenforms_workflows

# Clear cache
ddev drush cr

# Check routes
ddev drush route:list | grep parent_approval
```

### Test Scenario 1: Happy Path (Parents Together)

1. **Submit Test Form**
```bash
# Navigate to form
open https://aabenforms.ddev.site/forms/parent-request

# Fill form:
# - Child Name: "Test Child"
# - Child CPR: "010120-1234"
# - Parent 1 Email: "parent1@test.com"
# - Parent 2 Email: "parent2@test.com"
# - Parents Together: "together"
# - Request Details: "School enrollment request"
```

2. **Check Emails Sent**
```bash
# View maillog (if maillog module enabled)
ddev drush watchdog:show --type=mail

# Or check logs
ddev drush watchdog:show --type=aabenforms_workflows
```

3. **Generate Test Approval URL**
```php
// In Drupal console or custom script
$token_service = \Drupal::service('aabenforms_workflows.approval_token');
$token = $token_service->generateToken(1, 1); // submission_id=1, parent_number=1
$url = \Drupal::url('aabenforms_workflows.parent_approval', [
  'parent_number' => 1,
  'submission_id' => 1,
  'token' => $token,
], ['absolute' => TRUE]);
echo $url;
```

4. **Test Approval Flow**
```bash
# Access URL (replace with actual token)
open "https://aabenforms.ddev.site/parent-approval/1/1/TOKEN_HERE"

# Should see MitID login page
# (MitID integration must be configured for full test)

# After "authentication", should see approval form with:
# - Child name visible
# - Full CPR visible (parents together)
# - Request details
# - Approve/Reject radio buttons
# - Comments textarea
```

5. **Submit Approval**
```
Select "Approve"
Add comment: "Approved for school enrollment"
Submit
```

6. **Verify Workflow Triggered**
```bash
# Check submission updated
ddev drush sqlq "SELECT data FROM webform_submission_data WHERE sid=1 AND name='parent1_status'"

# Should show: s:8:"complete"

# Check logs
ddev drush watchdog:show --type=aabenforms_workflows | grep parent1
```

### Test Scenario 2: GDPR Mode (Parents Apart)

1. **Submit Form with Parents Apart**
```
Same as above but select:
- Parents Together: "apart"
```

2. **Access Approval URL**
```
# CPR should be masked: 010120-XXX4
# Only birthdate and last digit visible
```

### Test Scenario 3: Security Tests

**Test Expired Token**
```php
// Generate token with old timestamp
$token_service = \Drupal::service('aabenforms_workflows.approval_token');
$old_timestamp = time() - (8 * 24 * 60 * 60); // 8 days ago
$token = $token_service->generateToken(1, 1, $old_timestamp);

// Access URL - should show "expired" message
```

**Test Tampered Token**
```php
// Generate valid token
$token = $token_service->generateToken(1, 1);

// Modify token
$tampered_token = str_replace('A', 'B', $token);

// Access URL - should show "access denied"
```

**Test Wrong Parent Number**
```php
// Generate token for parent 1
$token = $token_service->generateToken(1, 1);

// Try to access as parent 2
$url = \Drupal::url('aabenforms_workflows.parent_approval', [
  'parent_number' => 2, // Wrong!
  'submission_id' => 1,
  'token' => $token,
]);
// Should fail validation
```

**Test Already Approved**
```php
// Approve submission
$storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
$submission = $storage->load(1);
$submission->setElementData('parent1_status', 'complete');
$submission->save();

// Try to access URL again
// Should show "already processed" message
```

### Test Scenario 4: Email Validation

**Check Email Content**
```bash
# If using maillog or similar
ddev drush sqlq "SELECT * FROM maillog WHERE id='parent_approval'"

# Email should contain:
# - Child name
# - Request details
# - Approval URL with token
# - 7-day deadline
# - MitID authentication notice
```

### Test Scenario 5: ECA Workflow Integration

**Test Workflow Triggers**
```bash
# Check initial_request_flow
ddev drush config:get eca.eca.initial_request_flow

# Check parent1_approval_flow
ddev drush config:get eca.eca.parent1_approval_flow

# Verify events are properly wired
ddev drush watchdog:show --type=eca
```

**Manually Trigger Workflow**
```php
// Load submission
$storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
$submission = $storage->load(1);

// Update status (should trigger workflow)
$submission->setElementData('parent1_status', 'complete');
$submission->save();

// Check logs
ddev drush watchdog:show --type=aabenforms_workflows --count=10
```

## Troubleshooting

### Common Issues

**Issue: Emails not sending**
```bash
# Check mail configuration
ddev drush config:get system.mail

# Check watchdog for mail errors
ddev drush watchdog:show --type=mail --severity=3

# Test mail system
ddev drush php-eval "mail('test@example.com', 'Test', 'Body');"
```

**Issue: Token validation fails**
```bash
# Check private key exists
ddev drush php-eval "echo \Drupal::service('private_key')->get();"

# Check service definition
ddev drush devel:services | grep approval_token

# Clear cache
ddev drush cr
```

**Issue: Routes not found**
```bash
# Rebuild routes
ddev drush router:rebuild

# Check route exists
ddev drush route:list | grep parent_approval

# Re-enable module
ddev drush pm:uninstall aabenforms_workflows
ddev drush pm:enable aabenforms_workflows
```

**Issue: MitID authentication not working**
```bash
# Check session
ddev drush config:get aabenforms_mitid.settings

# For testing, manually set session
# In controller or test:
$session = \Drupal::request()->getSession();
$session->set('mitid_authenticated_parent1', TRUE);
```

**Issue: Form not displaying**
```bash
# Check form exists
ddev drush config:get webform.webform.parent_request_form

# Check templates
ls -la web/modules/custom/aabenforms_workflows/templates/

# Clear Twig cache
ddev drush cache:clear render
```

## API Reference

### ApprovalTokenService

```php
// Generate token
$token_service = \Drupal::service('aabenforms_workflows.approval_token');
$token = $token_service->generateToken($submission_id, $parent_number);

// Validate token
$is_valid = $token_service->validateToken($submission_id, $parent_number, $token);

// Check if expired
$is_expired = $token_service->isTokenExpired($token);

// Get timestamp
$timestamp = $token_service->getTokenTimestamp($token);
```

### SendApprovalEmailAction (ECA)

**Configuration:**
```yaml
actions:
  send_parent1_email:
    label: 'Send Parent 1 Approval Email'
    plugin: aabenforms_send_approval_email
    configuration:
      parent_number: '1'
      email_field: parent1_email
      submission_token: webform_submission
```

### ParentApprovalController

**Access URL programmatically:**
```php
$url = \Drupal::url('aabenforms_workflows.parent_approval', [
  'parent_number' => 1,
  'submission_id' => 123,
  'token' => $token,
], ['absolute' => TRUE]);
```

## Performance Considerations

- **Token validation**: O(1) operation, no database queries
- **Submission loading**: Single entity load, consider caching for high traffic
- **Email sending**: Queued via Drupal mail system
- **Workflow execution**: Async via ECA (no blocking)

## Future Enhancements

1. **SMS notifications**: Alternative to email
2. **Reminder emails**: Auto-send after 3 days if no response
3. **Mobile app support**: API endpoints for native apps
4. **Multi-language**: Translation support for Danish/English
5. **Dashboard**: Admin UI showing all pending approvals
6. **Bulk operations**: Approve/reject multiple requests
7. **Delegation**: Allow parents to delegate approval rights
8. **Partial approval**: Complex workflows with conditional approvals

## License

GPL-2.0

## Support

For issues or questions, contact the ÅbenForms development team or file an issue on GitHub.
