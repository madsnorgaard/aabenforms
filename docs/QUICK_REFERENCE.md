# ÅbenForms Quick Reference Card

**Last Updated**: 2026-02-02
**Print this page for desk reference**

---

## Common Tasks

### Access Admin Interface
```
URL: https://[municipality].aabenforms.dk/admin
Login: Use your municipal credentials
```

### Create New Workflow
1. Navigate to: `/admin/config/workflow/eca`
2. Click **"Create from Template"**
3. Follow wizard (8 steps)
4. Test before activating

### View Pending Approvals
```
Dashboard: /admin/workflows/dashboard
My Tasks: /admin/workflows/my-tasks
All Submissions: /admin/workflows/submissions
```

### Check Workflow Status
1. Go to: `/admin/config/workflow/eca`
2. Find workflow in list
3. Status shown: Active / Disabled / Draft

### Test a Workflow
1. Edit workflow
2. Click **"Test Workflow"** tab
3. Fill test form
4. Review results

### Activate/Disable Workflow
**Activate**:
1. Find workflow (must be Draft)
2. Change status: Draft → Active
3. Confirm

**Disable**:
1. Find active workflow
2. Click **"Disable"**
3. Confirm (existing submissions continue)

### Export Audit Logs
```
Navigate to: /admin/reports/aabenforms-audit
Select date range
Click "Export" → CSV or PDF
```

---

## Workflow Status Meanings

| Status | Meaning | Actions Available |
|--------|---------|-------------------|
| **Draft** | Not active, still testing | Edit, Test, Activate, Delete |
| **Active** | Live and accepting submissions | Edit, Disable, Clone |
| **Disabled** | Temporarily off, can reactivate | Edit, Activate, Delete |
| **In Progress** | Being edited by another admin | View only, lock expires after 30 min |

---

## Submission Status

| Status | Meaning | Next Step |
|--------|---------|-----------|
| **Pending** | Waiting for citizen/parent action | Monitor, send reminder |
| **In Progress** | Being processed | Wait or check current step |
| **Awaiting Review** | With case worker | Case worker must review |
| **Approved** | Successfully completed | None (archived) |
| **Rejected** | Denied or failed | Can resubmit if needed |
| **Expired** | Deadline passed, no response | Closed, can resubmit |

---

## Where to Find Things

### Workflows
```
List all: /admin/config/workflow/eca
Create: /admin/config/workflow/eca/add
Templates: /admin/config/workflow/eca/templates
Test: /admin/config/workflow/eca/[workflow-id]/test
```

### Forms
```
List: /admin/structure/webform
Create: /admin/structure/webform/add
Results: /admin/structure/webform/manage/[form-id]/results
```

### Approvals
```
Dashboard: /admin/workflows/dashboard
My Tasks: /admin/workflows/my-tasks
All Submissions: /admin/workflows/submissions
Audit Log: /admin/reports/aabenforms-audit
```

### Configuration
```
ECA Settings: /admin/config/workflow/eca/settings
Email Templates: /admin/structure/webform/templates
GDPR Settings: /admin/config/aabenforms/gdpr
Integrations: /admin/config/services
```

### Logs & Monitoring
```
Recent Logs: /admin/reports/dblog
Email Logs: /admin/reports/maillog
Audit Logs: /admin/reports/aabenforms-audit
System Status: /admin/reports/status
```

---

## Essential Commands (for Developers)

```bash
# Clear cache
ddev drush cr

# Export configuration
ddev drush config:export -y

# View workflow list
ddev drush eca:list

# Validate template
ddev drush aabenforms:validate-template [template-id]

# Generate one-time login
ddev drush user:login

# Check database updates
ddev drush updatedb -y
```

---

## Workflow Timeline Examples

### Simple Contact Form
```
Submit → Route → Notify → Complete
(< 1 minute total)
```

### Parent Approval (Happy Path)
```
Submit (T+0) → Parent 1 Approves (T+2h) → Parent 2 Approves (T+1d)
→ Case Worker Reviews (T+2d) → Complete (T+3d)
```

### Building Permit (Complex)
```
Submit (T+0) → Validate (T+1h) → Case Worker (T+7d)
→ Request Info (T+10d) → Citizen Responds (T+15d)
→ Final Review (T+20d) → Approved (T+21d)
```

---

## Keyboard Shortcuts

### ECA Workflow Editor
```
Ctrl + S       Save workflow
Ctrl + Z       Undo
Ctrl + Y       Redo
Ctrl + F       Find/search
Delete         Delete selected element
Ctrl + C/V     Copy/paste elements
```

### Admin Interface (Gin theme)
```
G + D          Go to Dashboard
G + C          Go to Content
G + W          Go to Workflows
G + S          Go to Structure
/              Focus search
Esc            Close modal/overlay
```

---

## Error Messages & Solutions

### "Workflow not found"
**Cause**: Workflow ID incorrect or deleted
**Fix**: Check workflow list, verify spelling

### "MitID authentication failed"
**Cause**: MitID service down or config wrong
**Fix**: Check `/admin/config/services/openid-connect`, test connection

### "CPR lookup error"
**Cause**: Serviceplatformen connection issue
**Fix**: Check SF1520 credentials, verify CPR format (DDMMYY-XXXX)

### "Email not sent"
**Cause**: SMTP configuration or email template issue
**Fix**: Test at `/admin/config/system/smtp/test`, check template

### "Task not assigned"
**Cause**: No case workers with required role
**Fix**: Assign role at `/admin/people/permissions` or change routing

### "Deadline exceeded"
**Cause**: Case worker didn't review in time
**Fix**: Configure deadline extension or auto-escalation

### "SBSYS case creation failed"
**Cause**: SBSYS integration error or credentials
**Fix**: Check integration logs, verify SBSYS credentials

---

## Field Types Reference

### Standard Webform Fields
- **Text**: Single-line text
- **Textarea**: Multi-line text
- **Email**: Email address (validated)
- **Telephone**: Phone number
- **Number**: Numeric value
- **Date**: Date picker
- **Checkbox**: Yes/No
- **Radios**: Single choice
- **Select**: Dropdown
- **File**: Document upload

### ÅbenForms Custom Fields
- **CPR Field**: Danish CPR number (encrypted)
- **CVR Field**: Danish CVR number (validated)
- **DAWA Address**: Address with autocomplete
- **MitID Auth**: MitID authentication trigger

---

## GDPR Checklist

When creating workflows, ensure:

- [ ] Legal basis documented
- [ ] Only necessary data collected
- [ ] Consent obtained where required
- [ ] Data retention configured
- [ ] CPR numbers encrypted
- [ ] Audit logging enabled
- [ ] Privacy policy updated
- [ ] Citizen rights supported (export, delete)

---

## Support Contacts

### Technical Issues
- **Email**: support@aabenforms.dk
- **Phone**: [Your municipality's IT support]
- **Hours**: Monday-Friday 8:00-16:00 CET

### Workflow Design Help
- **Documentation**: [MUNICIPAL_ADMIN_GUIDE.md](MUNICIPAL_ADMIN_GUIDE.md)
- **Tutorial**: [CREATE_APPROVAL_WORKFLOW.md](tutorials/CREATE_APPROVAL_WORKFLOW.md)
- **Community**: [aabenforms.dk/forum](https://aabenforms.dk/forum)

### GDPR Questions
- **Email**: privacy@aabenforms.dk
- **Guide**: See GDPR section in [MUNICIPAL_ADMIN_GUIDE.md](MUNICIPAL_ADMIN_GUIDE.md)

### Emergency (System Down)
- **Escalation**: [Your municipality's on-call number]
- **Status Page**: [status.aabenforms.dk](https://status.aabenforms.dk)

---

## Integration Status Codes

### MitID Authentication
- `200 OK`: Authenticated successfully
- `401 Unauthorized`: Invalid credentials
- `403 Forbidden`: User canceled or denied
- `500 Server Error`: MitID service unavailable

### CPR/CVR Lookup (Serviceplatformen)
- `200 OK`: Data retrieved
- `404 Not Found`: CPR/CVR not in registry
- `429 Too Many Requests`: Rate limit exceeded
- `500 Server Error`: Serviceplatformen unavailable

### Digital Post (SF1601)
- `200 OK`: Message sent
- `202 Accepted`: Queued for delivery
- `400 Bad Request`: Invalid CPR or message
- `500 Server Error`: Digital Post unavailable

---

## Performance Benchmarks

### Expected Response Times
- **Contact Form**: < 5 seconds
- **MitID Authentication**: < 30 seconds
- **CPR/CVR Lookup**: < 5 seconds (cached) or < 15 seconds (fresh)
- **Email Notification**: < 1 minute
- **Digital Post**: < 24 hours
- **SBSYS Case Creation**: < 30 seconds

### Acceptable Limits
- **Workflow Execution**: < 60 seconds per step
- **File Upload**: Max 20MB per file
- **Concurrent Users**: 100+ (platform dependent)

---

## Troubleshooting Flowchart

```
Problem?
   │
   ├─ Workflow not triggering?
   │  ├─ Check: Is workflow Active?
   │  ├─ Check: Is webform ID correct?
   │  └─ Clear cache: /admin/config/development/performance
   │
   ├─ Email not sent?
   │  ├─ Check: Email template exists?
   │  ├─ Check: SMTP configured?
   │  └─ Test: /admin/config/system/smtp/test
   │
   ├─ Authentication fails?
   │  ├─ Check: MitID configured?
   │  ├─ Check: Credentials valid?
   │  └─ Test: /admin/config/services/openid-connect
   │
   ├─ Integration error?
   │  ├─ Check: Service credentials valid?
   │  ├─ Check: Service status online?
   │  └─ Review: /admin/reports/dblog
   │
   └─ Other issue?
      └─ Contact: support@aabenforms.dk
```

---

## Version Information

- **ÅbenForms Workflows**: 1.0.0
- **Drupal Core**: 11.3.2
- **ECA Module**: 2.1.18
- **PHP**: 8.4

Check version at: `/admin/reports/status`

---

## Glossary

**Approval**: Decision by citizen or case worker (approve/reject)
**BPMN**: Business Process Model and Notation (workflow standard)
**Case Worker**: Municipal employee who reviews submissions
**CPR**: Central Person Registry (Danish social security number)
**CVR**: Central Business Registry (Danish company number)
**DAWA**: Danish Address Web API
**ECA**: Event-Condition-Action (workflow engine)
**ESDH**: Electronic Document and Records Management
**Gateway**: Decision point in workflow (if/else)
**MitID**: Danish national authentication system
**Serviceplatformen**: Danish government service integration platform
**SF1520**: CPR lookup service
**SF1530**: CVR lookup service
**SF1601**: Digital Post service
**Webform**: Dynamic form builder module
**Workflow**: Automated process from start to finish

---

**Print or bookmark this page for quick access!**
