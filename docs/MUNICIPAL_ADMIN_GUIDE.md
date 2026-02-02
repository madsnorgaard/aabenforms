# ÅbenForms Municipal Administrator Guide

**Last Updated**: 2026-02-02

## Table of Contents
1. [Introduction](#introduction)
2. [Key Concepts](#key-concepts)
3. [Getting Started](#getting-started)
4. [Creating Your First Workflow](#creating-your-first-workflow)
5. [Managing Workflows](#managing-workflows)
6. [Monitoring Approvals](#monitoring-approvals)
7. [Troubleshooting](#troubleshooting)
8. [GDPR Compliance](#gdpr-compliance)

---

## Introduction

Welcome to ÅbenForms, a modern workflow automation platform designed specifically for Danish municipalities. This guide will help you understand and use the workflow system to automate citizen-facing processes like building permits, address changes, and parent approvals.

### What is the ÅbenForms Workflow System?

ÅbenForms provides a visual workflow builder that allows non-technical administrators to:
- Create automated approval processes without programming
- Integrate with Danish government services (MitID, CPR, CVR, Digital Post)
- Track all submissions and approvals in real-time
- Ensure GDPR compliance with automatic audit logging

### Who is This Guide For?

This guide is for municipal administrators who need to:
- Set up approval workflows for citizens
- Manage existing workflows
- Monitor pending approvals
- Review audit logs for compliance

**You do not need programming knowledge** to use this system.

---

## Key Concepts

### Workflows

A **workflow** is an automated process that handles a citizen request from start to finish. Think of it as a flowchart that the system follows automatically.

**Example**: A building permit workflow might:
1. Receive the citizen's application
2. Authenticate them with MitID
3. Validate their documents
4. Assign the case to a case worker
5. Send approval/rejection via Digital Post

### Templates

**Templates** are pre-built workflows for common municipal use cases. Instead of building from scratch, you can start with a template and customize it.

Available templates:
- **Building Permit**: Complex approval with document validation and case worker review
- **Address Change**: Update citizen address across multiple systems
- **Company Verification**: Verify company director authorization
- **Contact Form**: Simple citizen inquiry routing
- **FOI Request**: Freedom of information request with deadline tracking

### ECA (Event-Condition-Action)

ECA is the workflow engine that powers ÅbenForms. It works by:
- **Event**: Something happens (e.g., citizen submits form)
- **Condition**: Check if something is true (e.g., is this an urgent request?)
- **Action**: Do something (e.g., send notification email)

You don't need to understand the technical details - the workflow wizard handles this for you.

### BPMN (Business Process Model and Notation)

BPMN is an international standard for drawing workflow diagrams. ÅbenForms uses BPMN behind the scenes, but you'll interact with it through a user-friendly wizard.

---

## Getting Started

### Accessing the Admin Interface

1. Navigate to your municipality's ÅbenForms installation
   ```
   https://[your-municipality].aabenforms.dk/admin
   ```

2. Log in with your administrator credentials

3. Click **Configuration** → **Workflow** → **ECA**
   ```
   /admin/config/workflow/eca
   ```

### System Requirements

Before creating workflows, ensure your municipality has:
- [ ] MitID integration configured (for citizen authentication)
- [ ] Serviceplatformen credentials (for CPR/CVR lookups)
- [ ] Digital Post integration (for notifications)
- [ ] GDPR encryption enabled

Contact your system administrator if any of these are missing.

### User Permissions

To create and manage workflows, you need the **"Administer ECA"** permission. Check your permissions at:
```
/admin/people/permissions#module-eca
```

---

## Creating Your First Workflow

We'll walk through creating a simple contact form workflow.

### Step 1: Choose Your Approach

You have two options:
1. **Use a Template** (Recommended): Start with a pre-built workflow
2. **Build from Scratch**: Create a custom workflow using the wizard

For your first workflow, **we recommend using a template**.

### Step 2: Access the Workflow Library

1. Navigate to: `/admin/config/workflow/eca`
2. Click **"Browse Templates"**
3. You'll see 5 templates categorized by use case

### Step 3: Select a Template

Let's use the **Contact Form** template:

```
Template: Contact Form Workflow
Category: Citizen Service
Complexity: Simple
Description: Routes citizen inquiries to the appropriate department
             with automatic confirmation email.
```

**Why this template?**
- No authentication required
- No complex approvals
- Good learning example

### Step 4: Preview the Workflow

Click **"Preview"** to see the workflow diagram:

```
[Citizen Submits Form]
        ↓
[Validate Input]
        ↓
[Route to Department]
        ↓
[Send Confirmation Email]
        ↓
[Log Submission]
        ↓
[Complete]
```

### Step 5: Configure the Template

Click **"Use This Template"** and provide:

**Workflow Settings**:
- **Workflow ID**: `contact_form_aarhus`
- **Label**: Contact Form for Aarhus Kommune
- **Description**: Routes citizen inquiries

**Email Settings**:
- **Confirmation Email Template**: Select from dropdown
- **From Address**: `noreply@aarhus.dk`

**Department Routing**:
| Inquiry Type | Department Email |
|--------------|------------------|
| General | `general@aarhus.dk` |
| Permits | `permits@aarhus.dk` |
| Tax | `tax@aarhus.dk` |

### Step 6: Test the Workflow

Before going live, test with sample data:

1. Click **"Test Workflow"**
2. Fill in the test form:
   ```
   Name: Test Bruger
   Email: test@example.com
   Inquiry Type: General
   Message: This is a test
   ```
3. Click **"Submit Test"**
4. Check the results:
   - [ ] Email sent to `general@aarhus.dk`
   - [ ] Confirmation sent to `test@example.com`
   - [ ] Log entry created

### Step 7: Activate the Workflow

Once testing succeeds:

1. Click **"Activate Workflow"**
2. The workflow is now live
3. Citizens can submit forms at: `/form/contact-aarhus`

**Congratulations!** You've created your first workflow.

---

## Managing Workflows

### Viewing All Workflows

Navigate to: `/admin/config/workflow/eca`

You'll see a list of all workflows with:
- **Status**: Active, Disabled, Draft
- **Last Modified**: Date of last change
- **Submissions**: Number of times workflow has run
- **Actions**: Edit, Disable, Clone, Delete

### Editing an Existing Workflow

1. Find the workflow in the list
2. Click **"Edit"**
3. Make your changes
4. Click **"Save"**

**Important**: Changes take effect immediately for new submissions. Existing in-progress approvals use the old version.

### Disabling a Workflow

If you need to temporarily stop a workflow:

1. Find the workflow
2. Click **"Disable"**
3. Confirm the action

**What happens?**
- New submissions are blocked
- Existing in-progress approvals continue
- Citizens see a "temporarily unavailable" message

### Cloning a Workflow

To create a variation of an existing workflow:

1. Find the workflow
2. Click **"Clone"**
3. Provide a new ID and label
4. Make your modifications
5. Test and activate

**Use case**: Create separate building permit workflows for commercial vs. residential properties.

### Deleting a Workflow

**Warning**: This is permanent!

1. Find the workflow
2. Click **"Delete"**
3. Type the workflow ID to confirm
4. Click **"Delete Permanently"**

**What happens?**
- Workflow is removed from the system
- Historical data is preserved in audit logs
- In-progress approvals are cancelled

---

## Monitoring Approvals

### Approval Dashboard

View all pending approvals at: `/admin/workflows/dashboard`

The dashboard shows:
- **Pending Approvals**: Waiting for citizen or case worker action
- **In Progress**: Currently being processed
- **Completed Today**: Successfully finished workflows
- **Failed**: Workflows that encountered errors

### Filtering Approvals

Filter by:
- **Status**: Pending, In Progress, Completed, Failed
- **Workflow Type**: Building Permit, Address Change, etc.
- **Date Range**: Last 7 days, Last 30 days, Custom
- **Assigned To**: Specific case worker

### Viewing Individual Approvals

Click on any approval to see:
- **Current Status**: Where in the workflow
- **Citizen Information**: Name, CPR (masked), contact info
- **Submitted Data**: Form responses
- **Timeline**: All actions taken with timestamps
- **Next Steps**: What needs to happen next

### Case Worker Tasks

Case workers see their assigned tasks at: `/admin/workflows/my-tasks`

Each task shows:
- **Priority**: Normal, Urgent
- **Due Date**: When action is required
- **Citizen**: Name and case reference
- **Action Required**: Approve, Request More Info, Reject

### Audit Logs

View complete audit trail at: `/admin/reports/aabenforms-audit`

Logs include:
- **Event Type**: MitID Authentication, CPR Lookup, Approval, etc.
- **User**: Who performed the action
- **Timestamp**: Exact time
- **CPR Number**: (If applicable, encrypted)
- **Details**: Full context

**GDPR Note**: All CPR lookups are automatically logged.

---

## Troubleshooting

### Common Issues

#### Issue: "Workflow not triggering"

**Symptoms**: Citizen submits form but nothing happens

**Checklist**:
1. Is the workflow **Active**? (Not Disabled or Draft)
2. Is the form ID correct? Check at `/admin/structure/webform`
3. Are there any failed jobs? Check at `/admin/config/system/queue-ui`
4. Check logs at `/admin/reports/dblog` for errors

**Solution**: Enable the workflow and clear cache at `/admin/config/development/performance`

#### Issue: "MitID authentication fails"

**Symptoms**: Citizens see error when logging in with MitID

**Checklist**:
1. Check MitID configuration at `/admin/config/services/openid-connect`
2. Verify Serviceplatformen credentials
3. Check if test environment is down (broker.aabenforms.dk)
4. Review error logs

**Solution**: Contact your system administrator to verify MitID integration.

#### Issue: "Email notifications not sent"

**Symptoms**: Citizens don't receive confirmation emails

**Checklist**:
1. Check email configuration at `/admin/config/system/smtp`
2. Verify email templates exist at `/admin/structure/webform/templates`
3. Check spam filters (both yours and citizen's)
4. Review mail logs at `/admin/reports/maillog`

**Solution**: Test email sending at `/admin/config/system/smtp/test`

#### Issue: "CPR lookup returns no data"

**Symptoms**: Workflow fails at CPR lookup step

**Checklist**:
1. Is CPR number valid format (DDMMYY-XXXX)?
2. Is Serviceplatformen connection active?
3. Check SF1520 service status at servicestatus.kombit.dk
4. Review audit logs for error details

**Solution**: Retry after verifying CPR format and service availability.

### Getting Help

1. **Check Documentation**: See [Workflow Creation Tutorial](tutorials/CREATE_APPROVAL_WORKFLOW.md)
2. **View Error Logs**: `/admin/reports/dblog`
3. **Contact Support**: Your municipality's ÅbenForms administrator
4. **Community**: ÅbenForms user forum (coming soon)

---

## GDPR Compliance

ÅbenForms is designed with GDPR compliance built-in. Here's what you need to know:

### Data Visibility

#### For Citizens
- Can view their own submissions and approvals
- Cannot see other citizens' data
- Can request data export (Right to Access)
- Can request data deletion (Right to Erasure)

#### For Case Workers
- Can only see cases assigned to them
- Cannot see cases from other departments (unless granted permission)
- All data access is logged

#### For Administrators
- Can see all data (with appropriate permissions)
- All admin actions are logged
- Cannot bypass audit logging

### Audit Trails

Every action is logged with:
- **Who**: User ID and name
- **What**: Action performed
- **When**: Timestamp (Copenhagen timezone)
- **Why**: Workflow context
- **Where**: IP address and session ID

**Important**: Audit logs cannot be deleted or modified.

### Data Retention

Configure retention policies at: `/admin/config/aabenforms/gdpr`

Default policies:
- **Active Cases**: Retained indefinitely
- **Completed Cases**: 5 years (configurable per workflow)
- **Rejected Cases**: 2 years
- **Audit Logs**: 10 years (required by law)

**Note**: Citizen can request deletion before retention period expires.

### CPR Number Handling

CPR numbers are sensitive data under GDPR Article 9.

**Automatic protections**:
1. **Encryption**: All CPR fields are encrypted at rest (AES-256)
2. **Masking**: CPR displayed as XXXXXX-1234 in most contexts
3. **Logging**: All CPR lookups logged with purpose
4. **Access Control**: Only authorized users can view CPR

**When displayed in full**:
- Case worker review (with legitimate interest)
- Citizen viewing their own data
- Legal compliance audits

### GDPR Checklist for Workflows

When creating a workflow, ensure:

- [ ] **Legal Basis**: Document why you're collecting data
- [ ] **Data Minimization**: Only collect necessary fields
- [ ] **Consent**: Obtain explicit consent where required
- [ ] **Purpose Limitation**: Only use data for stated purpose
- [ ] **Retention**: Set appropriate deletion timeline
- [ ] **Access Control**: Limit who can view sensitive data
- [ ] **Audit Logging**: Verify logging is enabled
- [ ] **Citizen Rights**: Support export and deletion requests

### Privacy Policy

Your municipality must maintain a privacy policy explaining:
- What data is collected
- Why it's collected
- How long it's retained
- Who has access
- How to request access/deletion

**Template available**: `/admin/config/aabenforms/gdpr/privacy-template`

---

## Next Steps

Now that you understand the basics:

1. **Read**: [Workflow Creation Tutorial](tutorials/CREATE_APPROVAL_WORKFLOW.md)
2. **Review**: [Approval Process Guide](APPROVAL_PROCESS_GUIDE.md)
3. **Explore**: [Workflow Templates Reference](WORKFLOW_TEMPLATES.md)
4. **Create**: Your first production workflow

### Training Resources

- **Video Tutorial**: [Creating Your First Workflow](https://aabenforms.dk/tutorials) (5 minutes)
- **Webinar**: Monthly administrator training sessions
- **User Forum**: Share tips and ask questions
- **Office Hours**: Thursday 14:00-16:00 CET

### Questions?

Contact your municipal ÅbenForms administrator or email: support@aabenforms.dk

---

**Glossary of Terms**: See [Quick Reference Guide](QUICK_REFERENCE.md)
