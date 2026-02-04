# Tutorial: Creating an Approval Workflow

**Last Updated**: 2026-02-02
**Duration**: 30 minutes
**Difficulty**: Beginner

## What You'll Learn

By the end of this tutorial, you'll be able to:
- Understand your municipal approval process
- Choose the right workflow template
- Configure a workflow using the wizard
- Set up parent notifications
- Test the workflow before going live
- Deploy to production

## Prerequisites

- Administrator access to ÅbenForms
- "Administer ECA" permission
- Basic understanding of your municipality's approval process

---

## Step 1: Understanding Your Approval Process

Before creating a workflow, map out your approval process on paper.

### Questions to Answer

1. **What triggers the workflow?**
   - Citizen submits a form
   - Case worker creates a request
   - Scheduled event (e.g., annual review)

2. **Who needs to approve?**
   - Parents (both required?)
   - Case worker
   - Department head
   - Legal department

3. **What data is needed?**
   - Personal information (name, CPR)
   - Documents (upload required?)
   - Supporting information

4. **How long should it take?**
   - Response deadline (7 days? 30 days?)
   - What happens if deadline expires?

5. **What happens after approval?**
   - Create case in SBSYS
   - Send Digital Post notification
   - Update other systems

### Example: Daycare Enrollment Approval

Let's use a real example from Aarhus Kommune:

```
Process: Daycare Enrollment with Parent Approval

Trigger: Municipality sends enrollment offer
Approvers: Both parents (must both approve)
Data Needed:
  - Child's name and CPR
  - Parent 1 and Parent 2 CPR
  - Daycare location
  - Start date
Deadline: 14 days to respond
After Approval:
  - Create case in municipal daycare system
  - Send Digital Post confirmation to both parents
  - Update waiting list
```

### Decision Tree Diagram

```
START: What type of approval do you need?

├─ Simple notification (no approval needed)
│  └─ Use: Contact Form template
│
├─ Single approver (citizen or case worker)
│  └─ Use: FOI Request template
│
├─ Dual approval (both parents)
│  └─ Use: Building Permit template (modify)
│     ├─ Parents living together? → Full data visibility
│     └─ Parents living apart? → Limited data visibility
│
├─ Multi-step approval (chain of approvers)
│  └─ Use: Building Permit template
│
└─ System integration (update multiple systems)
   └─ Use: Address Change template
```

**Choose your path based on your requirements**.

---

## Step 2: Choosing the Right Template

ÅbenForms provides 5 templates. Let's compare them:

### Template Comparison

| Template | Use Case | Complexity | Authentication | Approvers |
|----------|----------|------------|----------------|-----------|
| **Contact Form** | Citizen inquiry routing | Simple | None | None (auto-route) |
| **FOI Request** | Freedom of information | Medium | Optional | Case worker + Department |
| **Company Verification** | Verify company authorization | Medium | MitID | Automatic (via CVR) |
| **Address Change** | Update citizen address | Medium | MitID | Automatic (parallel systems) |
| **Building Permit** | Complex approval process | Advanced | MitID | Multiple reviewers + Case worker |

### Which Template for Which Scenario?

#### Scenario 1: Parent Enrollment Approval
**Requirement**: Both parents must approve daycare enrollment

**Choose**: **Building Permit** template (most similar)
- Has dual approval flow
- Supports MitID authentication
- Includes case worker review
- Can be modified for parent approval

#### Scenario 2: Address Update
**Requirement**: Citizen updates address, system auto-updates tax/waste/etc.

**Choose**: **Address Change** template (perfect match)
- Pre-configured parallel system updates
- MitID authentication included
- DAWA address validation built-in

#### Scenario 3: Company Registration Check
**Requirement**: Verify person is authorized to act on behalf of company

**Choose**: **Company Verification** template (perfect match)
- CVR lookup included
- CPR cross-reference for directors
- Issues certificate automatically

#### Scenario 4: General Inquiry Routing
**Requirement**: Route citizen questions to departments

**Choose**: **Contact Form** template (perfect match)
- Simple, no authentication
- Department routing included
- Auto-confirmation email

### For Our Example (Daycare Enrollment)

We'll use the **Building Permit** template because:
-  Has dual approval (we need both parents)
-  Includes MitID authentication
-  Has case worker review step
-  Sends notifications

We'll modify it to:
- Remove document validation (not needed)
- Add daycare-specific fields
- Configure parent notifications

---

## Step 3: Using the Workflow Wizard

### Access the Wizard

1. Navigate to: `/admin/config/workflow/eca`
2. Click **"Create from Template"**
3. Select **"Building Permit"** template
4. Click **"Next"**

### Wizard Step 1: Basic Information

```
Workflow ID*: daycare_enrollment_aarhus
  (Must be unique, lowercase, underscores only)

Workflow Label*: Daycare Enrollment - Aarhus Kommune

Description:
  Two-parent approval workflow for daycare enrollment offers.
  Authenticates both parents with MitID, requires both approvals,
  and creates case in daycare system upon completion.

Category: Municipal Approval

Status: Draft
  (Keep as Draft until testing complete)
```

Click **"Next"**.

### Wizard Step 2: Webform Selection

```
Webform to Trigger This Workflow*:
  [Select existing] daycare_enrollment_form
  [or]
  [Create new form]
```

**If creating new form**, provide:
- Form ID: `daycare_enrollment_form`
- Form Title: Daycare Enrollment Request

**Required Fields** (auto-created):
- child_name (Text)
- child_cpr (CPR Field)
- parent1_email (Email)
- parent2_email (Email)
- parents_together (Radios: Together/Apart)
- daycare_location (Select)
- requested_start_date (Date)

Click **"Next"**.

### Wizard Step 3: Authentication Settings

```
Require MitID Authentication: ☑ Yes

Authentication Level:
  ○ Low (Password)
  ○ Substantial (SMS code)
  ● High (App or hardware token) ← Recommended

Who Must Authenticate:
  ☑ Parent 1
  ☑ Parent 2
  ☐ Case Worker (optional, depends on internal policy)
```

**GDPR Note**: MitID High provides strongest identity assurance.

Click **"Next"**.

### Wizard Step 4: Approval Flow

```
Approval Pattern:
  ○ Sequential (Parent 1 → Parent 2 → Case Worker)
  ● Parallel (Both parents at same time, then Case Worker)
  ○ Single (Only one approver)

Approval Timeout:
  Duration: [14] days
  Action on Timeout:
    ● Auto-reject and notify
    ○ Escalate to supervisor
    ○ Extend deadline by [7] days

Both Parents Must Approve: ☑ Yes
  (If one rejects, entire request is rejected)
```

Click **"Next"**.

### Wizard Step 5: Parent Notification Settings

This is where you configure how parents are notified.

```
Notification Method:
  ☑ Email (always sent)
  ☑ Digital Post (SF1601) (if configured)
  ☐ SMS (future feature)

Email Template for Parent 1:
  Subject: Daycare Enrollment - Action Required

  Body:
    Kære [parent1:name],

    [municipality_name] har tilbudt dit barn [child:name] en plads i
    [daycare:location] med startdato [start_date].

    Du skal godkende dette tilbud inden [deadline].

    Log ind med MitID: [approval_link]

    Venlig hilsen,
    [municipality_name]

Email Template for Parent 2:
  [Same template with parent2:name]
```

**Data Visibility Settings**:

```
If Parents Living Together:
  ☑ Show full child information
  ☑ Show both parents' names
  ☑ Show shared address
  ☑ Show full case details

If Parents Living Apart:
  ☑ Show child name and masked CPR (XXXXXX-1234)
  ☑ Show only requesting parent's information
  ☐ Hide other parent's contact info
  ☑ Show limited case details
```

Click **"Next"**.

### Wizard Step 6: Case Worker Review

```
Require Case Worker Review After Parent Approvals:
  ● Yes (Recommended for quality control)
  ○ No (Auto-approve if both parents approve)

Assign To:
  ○ Specific user: [Select case worker]
  ● Role: [Case Worker - Daycare]
  ○ Round-robin (distribute evenly)

Case Worker Can:
  ☑ Approve
  ☑ Reject (with reason)
  ☑ Request additional information
  ☐ Modify enrollment details
```

Click **"Next"**.

### Wizard Step 7: Integration & Actions

```
After Final Approval:

☑ Create Case in External System
  System: [Select] SBSYS Daycare Module
  Case Type: Daycare Enrollment
  Priority: Normal

☑ Send Confirmation Notifications
  Via: Digital Post (SF1601)
  Recipients: Both parents
  Message Template: [Select] Daycare Confirmation

☑ Update Waiting List
  Action: Remove child from waiting list
  System: Municipal Daycare Portal

☐ Generate PDF Certificate
  (Not needed for daycare)

☑ Archive Documents
  System: GetOrganized ESDH
  Document Type: Daycare Enrollment
```

Click **"Next"**.

### Wizard Step 8: GDPR & Audit Settings

```
Data Retention:
  After Approval: [5] years
  After Rejection: [2] years

Audit Logging:
  ☑ Log all MitID authentications
  ☑ Log all CPR lookups
  ☑ Log all approvals/rejections
  ☑ Log case worker decisions
  ☑ Log system integrations

Privacy Settings:
  ☑ Encrypt CPR numbers at rest (AES-256)
  ☑ Mask CPR in UI (show XXXXXX-1234)
  ☑ Support Right to Erasure requests
  ☑ Support Data Export requests (JSON/PDF)
```

Click **"Next"**.

### Wizard Step 9: Review & Create

Review all settings:

```
 Workflow ID: daycare_enrollment_aarhus
 Webform: daycare_enrollment_form
 Authentication: MitID High for both parents
 Approval Flow: Parallel → Case Worker Review
 Notifications: Email + Digital Post
 Integrations: SBSYS, Waiting List, ESDH
 GDPR: Fully compliant
```

Click **"Create Workflow (as Draft)"**.

---

## Step 4: Configuring Parent Notifications

After workflow is created, fine-tune parent notifications.

### Email Template Customization

1. Navigate to: `/admin/structure/webform/templates`
2. Find template: `daycare_enrollment_parent_notification`
3. Click **"Edit"**

**Customize Subject**:
```
[municipality:name] - Plads i [daycare:name] - Godkendelse Nødvendig
```

**Customize Body**:
```
Kære [parent:name],

[municipality:name] har tilbudt dit barn [child:name] en plads i
børnehaven [daycare:name] med startdato [start_date:formatted].

Det kræves at begge forældre godkender dette tilbud inden [deadline:formatted].

Log ind med MitID for at godkende:
[approval_link]

Hvis du har spørgsmål, kontakt [caseworker:email].

Med venlig hilsen,
[municipality:name]
[municipality:phone]
```

**Available Tokens**:
- `[parent:name]` - Parent's name from CPR lookup
- `[child:name]` - Child's name from form
- `[child:cpr]` - Masked CPR (XXXXXX-1234)
- `[daycare:name]` - Selected daycare location
- `[start_date:formatted]` - Formatted date (01-06-2026)
- `[deadline:formatted]` - Response deadline
- `[approval_link]` - Unique MitID login URL
- `[municipality:*]` - Municipality info
- `[caseworker:*]` - Assigned case worker info

### Digital Post Settings

If Digital Post is configured:

1. Navigate to: `/admin/config/services/digital-post`
2. Configure message settings:

```
Message Type: Standard
Priority: Normal
Delivery Method: Digital (fallback to physical mail)

Message Template:
  Title: Dagreplads - Godkendelse Nødvendig
  Body: [Same as email template]

Legal Notice Required: ☐ No
  (Only for official decisions)
```

### Testing Notifications

1. Navigate to: `/admin/config/workflow/eca/daycare_enrollment_aarhus/test`
2. Click **"Test Notifications"**
3. Provide test email addresses:
   ```
   Parent 1: test-parent1@example.com
   Parent 2: test-parent2@example.com
   ```
4. Click **"Send Test"**
5. Check both inboxes - emails should arrive within 1 minute

---

## Step 5: Testing the Workflow

**Never deploy untested workflows to production!**

### Test Environment

ÅbenForms provides a test environment:
- Test MitID credentials (no real authentication)
- Mock CPR/CVR data
- Emails sent to test addresses only

### Creating Test Data

1. Navigate to: `/admin/config/aabenforms/test-data`
2. Click **"Create Test Parent Profile"**

**Parent 1 (Test)**:
```
Name: Test Forælder En
CPR: 010190-1234 (fake CPR, valid format)
Email: test-parent1@municipality.dk
Address: Testvej 1, 8000 Aarhus
```

**Parent 2 (Test)**:
```
Name: Test Forælder To
CPR: 020290-5678 (fake CPR, valid format)
Email: test-parent2@municipality.dk
Address: Testvej 1, 8000 Aarhus
```

**Child (Test)**:
```
Name: Test Barn
CPR: 150320-9999 (fake CPR, valid format)
Parents: Linked to Test Forælder En & To
```

### Test Scenario 1: Happy Path (Both Parents Approve)

**Goal**: Verify both parents can approve and case is created.

1. Submit form as municipality admin:
   ```
   Child: Test Barn (CPR: 150320-9999)
   Parent 1: test-parent1@municipality.dk
   Parent 2: test-parent2@municipality.dk
   Parents Living Together: Yes
   Daycare: Testvej Børnehave
   Start Date: 2026-06-01
   ```

2. Check emails sent:
   ```
   ☑ Parent 1 received notification
   ☑ Parent 2 received notification
   ```

3. **As Parent 1**:
   - Open email
   - Click approval link
   - Login with test MitID (use test credentials)
   - Review information displayed (should show FULL data)
   - Click **"Godkend"** (Approve)
   - See confirmation message

4. **As Parent 2**:
   - Open email
   - Click approval link
   - Login with test MitID
   - Review information displayed (should show FULL data)
   - Click **"Godkend"** (Approve)
   - See confirmation message

5. **As Case Worker**:
   - Navigate to: `/admin/workflows/my-tasks`
   - Find task: "Daycare Enrollment - Test Barn"
   - Review both parent approvals (timestamps shown)
   - Add case worker notes if needed
   - Click **"Approve and Create Case"**

6. **Verify Results**:
   ```
   ☑ SBSYS case created (check case number)
   ☑ Digital Post sent to both parents
   ☑ Child removed from waiting list
   ☑ Documents archived in ESDH
   ☑ Audit log shows all actions
   ```

**Expected Timeline**: 2-5 minutes for full completion.

### Test Scenario 2: Parent Rejects

**Goal**: Verify rejection flow and notifications.

1. Submit new form (different child)

2. **As Parent 1**: Approve (same as above)

3. **As Parent 2**:
   - Click approval link
   - Review information
   - Click **"Afvis"** (Reject)
   - Provide reason: "Start date ikke mulig"
   - Submit

4. **Verify Results**:
   ```
   ☑ Workflow stopped (not sent to case worker)
   ☑ Municipality notified of rejection
   ☑ Parent 1 notified that Parent 2 rejected
   ☑ No SBSYS case created
   ☑ Audit log shows rejection with reason
   ```

### Test Scenario 3: Parents Living Apart

**Goal**: Verify limited data visibility.

1. Submit new form:
   ```
   Child: Test Barn Skilsmisse
   Parents Living Together: No
   Parent 1 Address: Adresse 1
   Parent 2 Address: Adresse 2
   ```

2. **As Parent 1**:
   - Login and review data
   - **Verify**: CPR masked (XXXXXX-1234)
   - **Verify**: Only Parent 1 address shown
   - **Verify**: No Parent 2 contact info visible
   - Approve

3. **As Parent 2**:
   - Login and review data
   - **Verify**: CPR masked (XXXXXX-1234)
   - **Verify**: Only Parent 2 address shown
   - **Verify**: No Parent 1 contact info visible
   - Approve

4. **Verify**:
   ```
   ☑ Data visibility respected
   ☑ GDPR compliance maintained
   ☑ Both approvals recorded
   ```

### Test Scenario 4: Timeout

**Goal**: Verify deadline handling.

**Note**: For testing, reduce deadline to 5 minutes instead of 14 days.

1. Edit workflow temporarily:
   - Change timeout: 14 days → 5 minutes
   - Save

2. Submit form

3. **As Parent 1**: Approve

4. **As Parent 2**: Don't respond (wait 5 minutes)

5. **After 5 minutes, verify**:
   ```
   ☑ Workflow auto-rejected
   ☑ Municipality notified
   ☑ Parent 1 notified of timeout
   ☑ Parent 2 notified of missed deadline
   ☑ Audit log shows timeout event
   ```

6. **Restore workflow**:
   - Change timeout: 5 minutes → 14 days
   - Save

### Testing Checklist

Before going live, verify:

- [ ] All notifications sent correctly
- [ ] MitID authentication works
- [ ] CPR lookups return correct data
- [ ] Data visibility rules respected
- [ ] Both approval and rejection flows work
- [ ] Timeout handling works
- [ ] SBSYS case creation succeeds
- [ ] Digital Post sends successfully
- [ ] Audit logging captures all events
- [ ] Error handling graceful (e.g., if SBSYS down)

---

## Step 6: Going Live

### Pre-Launch Checklist

- [ ] All test scenarios passed
- [ ] Case workers trained on new workflow
- [ ] Email templates reviewed by communications team
- [ ] Legal department approved data handling
- [ ] GDPR assessment completed
- [ ] Privacy policy updated
- [ ] Citizen-facing documentation prepared
- [ ] Support team briefed on common questions

### Activating the Workflow

1. Navigate to: `/admin/config/workflow/eca`
2. Find workflow: `daycare_enrollment_aarhus`
3. Change status: **Draft** → **Active**
4. Click **"Activate"**
5. Confirm activation

**The workflow is now live!**

### Monitoring After Launch

**First Week**:
- Check dashboard daily: `/admin/workflows/dashboard`
- Review all audit logs: `/admin/reports/aabenforms-audit`
- Collect feedback from case workers
- Monitor email delivery rates
- Watch for error patterns

**First Month**:
- Review completion rates (% of parents who respond)
- Identify bottlenecks (where do workflows get stuck?)
- Adjust deadlines if needed
- Refine email templates based on feedback

### Common Post-Launch Adjustments

**Issue**: Parents don't understand emails
- **Fix**: Simplify language, add screenshots
- **Action**: Edit email template

**Issue**: Too many timeout rejections
- **Fix**: Extend deadline from 14 to 21 days
- **Action**: Edit workflow settings

**Issue**: Case workers overwhelmed
- **Fix**: Add more reviewers to round-robin
- **Action**: Edit assignment rules

---

## Advanced Customization

### Adding Custom Fields

To add fields to the webform:

1. Navigate to: `/admin/structure/webform/manage/daycare_enrollment_form`
2. Click **"Build"**
3. Click **"Add element"**
4. Choose element type (Text, Select, etc.)
5. Configure field settings
6. Click **"Save"**

**Example**: Add "Special dietary needs" field
```
Element Type: Textarea
Key: dietary_needs
Label: Særlige kostregler
Description: Angiv eventuelle allergier eller kostpræferencer
Required: No
```

### Modifying Approval Logic

To change who must approve:

1. Navigate to: `/admin/config/workflow/eca/daycare_enrollment_aarhus/edit`
2. Find section: **Approval Flow**
3. Modify conditions:

**Example**: Make case worker approval conditional
```
Condition: approval_amount > 100000
Action if True: Require department head approval
Action if False: Auto-approve
```

### Adding Integrations

To integrate with additional systems:

1. Navigate to: `/admin/config/workflow/eca/daycare_enrollment_aarhus/edit`
2. Scroll to: **Actions**
3. Click **"Add Action"**
4. Select action type:
   - REST API Call
   - Webform Submission
   - Custom PHP Action
   - Email Notification
5. Configure integration details

**Example**: Notify daycare directly
```
Action: Send Email
To: [daycare:email]
Subject: Ny indskrivning: [child:name]
Body: [Full enrollment details]
```

---

## Troubleshooting Guide

### Workflow Not Triggering

**Check**:
1. Workflow status is **Active** (not Draft)
2. Webform ID matches exactly
3. No syntax errors in configuration
4. Cache cleared: `/admin/config/development/performance`

### Parent Links Not Working

**Check**:
1. Approval links expire after 30 days
2. Link can only be used once
3. Parent must use correct email address
4. MitID integration configured correctly

### Data Not Displaying Correctly

**Check**:
1. Token syntax correct: `[child:name]` not `{child:name}`
2. Field exists in webform
3. CPR lookup succeeded (check audit log)
4. Visibility rules configured correctly

---

## Next Steps

Congratulations! You've created and deployed your first approval workflow.

**What's next?**

1. **Create more workflows**: Try other templates
2. **Advanced features**: Explore conditional logic, parallel approvals
3. **Reporting**: Build custom reports on workflow metrics
4. **Optimization**: Improve completion rates and response times

**Additional Resources**:
- [Approval Process Guide](../APPROVAL_PROCESS_GUIDE.md)
- [Workflow Templates Reference](../WORKFLOW_TEMPLATES.md)
- [Quick Reference Card](../QUICK_REFERENCE.md)
- [Video Script](../VIDEO_SCRIPT.md)

---

**Questions?** Contact your ÅbenForms administrator or email support@aabenforms.dk
