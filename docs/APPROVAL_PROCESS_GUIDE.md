# Approval Process Guide

**Last Updated**: 2026-02-02

## Overview

This guide documents the end-to-end approval flow in ÅbenForms, explaining what happens at each step and who is involved.

## Audience

- **Municipality Staff**: Understand how to initiate requests
- **Parents/Citizens**: Know what to expect and how to respond
- **Case Workers**: Learn how to manage and review approvals
- **Administrators**: Understand the technical flow

---

## Table of Contents

1. [For Municipality Staff](#for-municipality-staff)
2. [For Parents/Citizens](#for-parentscitizens)
3. [For Case Workers](#for-case-workers)
4. [Technical Flow](#technical-flow)
5. [Approval Scenarios](#approval-scenarios)

---

## For Municipality Staff

### How to Submit an Approval Request

**Step 1: Access the Form**

Navigate to the appropriate webform:
- Building permits: `/form/building-permit`
- Daycare enrollment: `/form/daycare-enrollment`
- Address changes: `/form/address-change`

Or access from the admin menu: **Content** → **Webforms** → **Submit**

**Step 2: Fill Out Required Information**

Required fields typically include:
- **Citizen Information**: Name, CPR, contact details
- **Request Details**: What approval is needed
- **Parent Information** (if applicable): Both parents' contact info
- **Documents**: Upload supporting files
- **Case Notes**: Internal notes for case workers

**Step 3: Review and Submit**

1. Review all information for accuracy
2. Check CPR numbers are formatted correctly (DDMMYY-XXXX)
3. Verify parent email addresses
4. Click **"Submit"**

**Step 4: Confirmation**

You'll see a confirmation screen with:
- Submission ID (e.g., `#12345`)
- Expected timeline (e.g., "Response within 14 days")
- Next steps
- Who to contact with questions

### Tracking Submitted Requests

**View Your Submissions**:
Navigate to: `/admin/workflows/submissions`

Filter by:
- **Status**: Pending, In Progress, Completed
- **Date Submitted**: Last 7 days, Last 30 days, Custom range
- **Request Type**: Building Permit, Daycare, etc.

**Submission Details**:
Click on any submission to see:
- Current status and timeline
- Who has approved/rejected
- Pending approvals
- Case worker notes
- Audit trail

### Common Questions

**Q: How long does approval take?**
A: Typical timelines:
- Simple requests: 7 days
- Parent approvals: 14 days
- Complex reviews: 30 days

**Q: Can I cancel a submission?**
A: Yes, if no approvals have been received yet:
1. Find submission at `/admin/workflows/submissions`
2. Click **"Cancel Request"**
3. Provide cancellation reason

**Q: What if a parent doesn't respond?**
A: After the deadline (typically 14 days):
- Request is auto-rejected
- You receive notification
- You can resubmit if needed

**Q: How are parents notified?**
A: Parents receive:
- Email notification (immediate)
- Digital Post message (within 24 hours)
- SMS reminder (if configured, at 7-day mark)

---

## For Parents/Citizens

### What to Expect

When the municipality submits an approval request on your behalf:

1. **You'll receive an email** with:
   - Explanation of what needs approval
   - Deadline to respond
   - Link to review and approve
   - Contact information if you have questions

2. **You'll need MitID** to log in and approve
   - Have your MitID app or hardware token ready
   - Authentication takes 1-2 minutes

3. **You'll review the information** and decide:
   - Approve
   - Reject (with reason)
   - Request more information

### How to Approve a Request

**Step 1: Open the Email**

Look for an email from:
```
From: [Your Municipality]
Subject: Action Required - [Request Type]
```

**Important**: Check your spam folder if you don't see it within 15 minutes.

**Step 2: Click the Approval Link**

Click the button: **"Review and Approve with MitID"**

This opens a secure page on your municipality's website.

**Step 3: Authenticate with MitID**

1. Enter your CPR number
2. Choose authentication method:
   - MitID app (recommended)
   - Hardware token
   - Code display
3. Complete authentication
4. You're logged in

**Step 4: Review the Information**

You'll see:
- **Request Details**: What the municipality is asking
- **Your Information**: From CPR registry (name, address)
- **Timeline**: When action is needed
- **Supporting Documents**: Any attachments (if applicable)

**Data Visibility**:
- If you live with the other parent: You see all information
- If you live separately: You see limited information (GDPR protection)

**Step 5: Make Your Decision**

Three options:

**Option A: Approve**
1. Click **"I Approve"** (Jeg Godkender)
2. Confirm your decision
3. See confirmation message
4. You'll receive confirmation email

**Option B: Reject**
1. Click **"I Reject"** (Jeg Afviser)
2. Provide reason (required):
   - Not accurate information
   - Need more time
   - Other reason (explain)
3. Submit
4. Municipality is notified

**Option C: Request More Information**
1. Click **"I Have Questions"** (Jeg Har Spørgsmål)
2. Type your question
3. Submit
4. Case worker will respond within 2 business days
5. You'll get new approval link when answered

**Step 6: Confirmation**

After approving, you'll see:
- Confirmation screen with timestamp
- Explanation of next steps
- Expected timeline for completion
- Reference number for follow-up

### Common Questions for Parents

**Q: What if both parents must approve?**
A: Both parents receive separate emails. The request proceeds only after both have approved. If one rejects, the entire request is rejected.

**Q: How long do I have to respond?**
A: Typically 14 days from when the email is sent. The exact deadline is shown in the email and on the approval page.

**Q: What if I miss the deadline?**
A: The request is automatically rejected. The municipality can resubmit if needed. You'll receive notification about the missed deadline.

**Q: Can I approve on behalf of the other parent?**
A: No. Each parent must authenticate with their own MitID and approve separately. This is required by GDPR.

**Q: What if I don't have MitID?**
A: Contact your municipality. They may have alternative approval methods (phone, in-person visit). However, MitID is strongly recommended for security.

**Q: Is my information secure?**
A: Yes. All communications are encrypted, CPR numbers are protected, and all access is logged. See the [GDPR section](#gdpr-protections) for details.

**Q: Can I see what the other parent saw?**
A: Only if you live together. If you live separately, each parent sees limited information (GDPR protection).

**Q: What happens after I approve?**
A: Your approval is recorded. If both parents have approved (if required), the request goes to a case worker for final review. You'll be notified of the outcome via Digital Post.

### Troubleshooting for Citizens

**Problem**: Can't open the approval link
- Check if link expired (links expire after 30 days)
- Try copying and pasting the full URL
- Contact municipality for new link

**Problem**: MitID authentication fails
- Ensure you're using the correct CPR number
- Check if MitID app is up to date
- Try alternative authentication method
- Contact MitID support: mitid.dk

**Problem**: Don't understand what's being requested
- Click "I Have Questions" on the approval page
- Call your municipality (number in email)
- Don't approve if you're unsure

---

## For Case Workers

### Your Role

As a case worker, you:
- Review requests after parent/citizen approvals
- Verify information accuracy
- Make final approval decision
- Communicate outcomes to citizens
- Escalate complex cases

### Accessing Your Task Queue

Navigate to: `/admin/workflows/my-tasks`

You'll see tasks assigned to you:

| Task | Type | Status | Priority | Due Date | Actions |
|------|------|--------|----------|----------|---------|
| #12345 | Daycare Enrollment | Pending Review | Normal | 2026-02-10 | Review |
| #12344 | Building Permit | Awaiting Info | Urgent | 2026-02-05 | View |
| #12343 | Address Change | Ready to Approve | Normal | 2026-02-12 | Review |

**Task Statuses**:
- **Pending Review**: Parents approved, waiting for your review
- **Awaiting Info**: You requested more information
- **Ready to Approve**: All information received
- **Escalated**: Requires supervisor review

### Reviewing a Task

**Step 1: Open the Task**

Click on any task to see full details.

**Step 2: Review Information**

You'll see:

**Citizen Information**:
- Name, CPR (full), contact info
- Address and registration details
- Previous interactions (if any)

**Request Details**:
- What they're requesting
- Supporting documents
- Submitted date
- Parent approvals (timestamps and decisions)

**Approval Timeline**:
```
2026-02-01 09:15 - Request submitted by municipality
2026-02-01 09:16 - Email sent to Parent 1
2026-02-01 09:16 - Email sent to Parent 2
2026-02-01 14:30 - Parent 1 approved (MitID: High)
2026-02-02 10:20 - Parent 2 approved (MitID: High)
2026-02-02 10:21 - Task assigned to you
```

**Verification Checklist**:
- [ ] Parent approvals authentic (MitID High)
- [ ] CPR information matches CPR registry
- [ ] All required documents attached
- [ ] Information is consistent
- [ ] No red flags or anomalies

**Step 3: Make Your Decision**

Four options:

**Option A: Approve**
1. Click **"Approve"**
2. Add case notes (optional but recommended)
3. Confirm approval
4. System creates case in external system (SBSYS, etc.)
5. Digital Post sent to citizens
6. Task marked complete

**Option B: Reject**
1. Click **"Reject"**
2. **Must provide reason** (will be sent to citizen):
   - Incomplete information
   - Does not meet requirements
   - Other (explain)
3. Add internal notes
4. Confirm rejection
5. Digital Post sent to citizens
6. Task marked complete

**Option C: Request More Information**
1. Click **"Request Information"**
2. Specify what you need:
   - Additional documents
   - Clarification on specific field
   - Verification from third party
3. Set new deadline (default: 7 days)
4. Submit
5. Citizen notified via email and Digital Post
6. Task status: **Awaiting Info**

**Option D: Escalate**
1. Click **"Escalate to Supervisor"**
2. Explain why escalation needed:
   - Complex legal issue
   - Conflicting information
   - Policy exception required
   - Outside your authority
3. Select supervisor
4. Submit
5. Task reassigned

**Step 4: Document Your Decision**

Always add case notes explaining:
- Why you approved/rejected
- Any concerns or observations
- Follow-up actions needed
- Reference to relevant policies

**Good example**:
```
Approved. Both parents authenticated with MitID High.
CPR information matches registry. All required documents
attached and verified. Start date confirmed available
with daycare coordinator. Case created in SBSYS #98765.
```

### Task Management Best Practices

**Prioritization**:
1. **Urgent** tasks (red flag) - Review immediately
2. Tasks nearing deadline - Review within 24 hours
3. **Normal** priority - Review within 48 hours
4. **Low** priority - Review within 5 days

**Quality Control**:
- Always verify CPR information
- Check for duplicate submissions
- Review audit trail for anomalies
- Document your reasoning
- Don't rush complex cases

**Communication**:
- Respond to citizen questions within 2 business days
- Keep citizens informed of delays
- Explain rejections clearly and constructively
- Use plain language, avoid jargon

### Common Case Worker Scenarios

**Scenario 1: One Parent Approved, One Rejected**
- Task won't reach you (auto-rejected)
- Both parents must approve
- Municipality can resubmit if circumstances change

**Scenario 2: Suspicious Activity**
- Multiple submissions with same details
- MitID authentication from unusual location
- Inconsistent information
**Action**: Escalate to supervisor, don't approve

**Scenario 3: Parent Can't Use MitID**
- Citizen has disability preventing MitID use
- Temporary access issue
**Action**: Request alternative verification, document exception

**Scenario 4: Deadline Already Passed**
- Request submitted months ago but just reached you
- Original timeline no longer valid
**Action**: Reject with explanation, advise resubmission with new dates

### Reporting and Metrics

View your performance at: `/admin/workflows/my-stats`

Metrics tracked:
- **Tasks Completed**: Count per week/month
- **Average Review Time**: From assignment to decision
- **Approval Rate**: % approved vs rejected
- **Tasks Pending**: Current backlog
- **Escalations**: How many cases escalated

**Goal targets** (suggested):
- Average review time: < 24 hours
- Tasks pending: < 10
- Response to citizen questions: < 2 business days

---

## Technical Flow

### Complete Workflow Architecture

```
┌─────────────────────────────────────────────────┐
│  1. Municipality Submits Request (Webform)     │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  2. Workflow Triggered (ECA Event)              │
│     Event: webform_submission:insert            │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  3. Generate Parent Approval Links              │
│     - Create unique tokens                      │
│     - Set expiration (30 days)                  │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│  4. Send Notifications to Parents               │
│     - Email (immediate)                         │
│     - Digital Post (within 24h)                 │
└────────────────┬────────────────────────────────┘
                 │
                 ├─────────────────┬───────────────┤
                 │                 │               │
                 ▼                 ▼               ▼
        ┌─────────────┐   ┌─────────────┐
        │  Parent 1   │   │  Parent 2   │
        │  Click Link │   │  Click Link │
        └──────┬──────┘   └──────┬──────┘
               │                 │
               ▼                 ▼
        ┌─────────────┐   ┌─────────────┐
        │  MitID Auth │   │  MitID Auth │
        │  (High)     │   │  (High)     │
        └──────┬──────┘   └──────┬──────┘
               │                 │
               ▼                 ▼
        ┌─────────────┐   ┌─────────────┐
        │  CPR Lookup │   │  CPR Lookup │
        │  (SF1520)   │   │  (SF1520)   │
        └──────┬──────┘   └──────┬──────┘
               │                 │
               ▼                 ▼
        ┌─────────────┐   ┌─────────────┐
        │  Review &   │   │  Review &   │
        │  Decide     │   │  Decide     │
        └──────┬──────┘   └──────┬──────┘
               │                 │
               │                 │
               └────────┬────────┘
                        │
                        ▼
               ┌─────────────────┐
               │  Both Approved? │
               └────────┬────────┘
                        │
            ┌───────────┼───────────┐
            │ Yes                   │ No
            ▼                       ▼
  ┌──────────────────┐     ┌────────────────┐
  │  Assign to Case  │     │  Auto-Reject   │
  │  Worker          │     │  & Notify      │
  └────────┬─────────┘     └────────────────┘
           │
           ▼
  ┌──────────────────┐
  │  Case Worker     │
  │  Reviews         │
  └────────┬─────────┘
           │
           ▼
  ┌──────────────────┐
  │  Decision?       │
  └────────┬─────────┘
           │
  ┌────────┼────────┬────────────┐
  │        │        │            │
  │ Approve│ Reject │ Request    │
  │        │        │ Info       │
  ▼        ▼        ▼            │
┌──────┐ ┌──────┐ ┌───────┐     │
│Create│ │Notify│ │Notify │     │
│SBSYS │ │      │ │Citizen│     │
│Case  │ │      │ └───┬───┘     │
└──┬───┘ └──┬───┘     │         │
   │        │         │         │
   │        │         └─────────┘
   │        │         (loop back)
   │        │
   └────────┼─────────┐
            │         │
            ▼         ▼
   ┌──────────────────────┐
   │  Send Digital Post   │
   │  to Citizens         │
   └──────────┬───────────┘
              │
              ▼
   ┌──────────────────────┐
   │  Audit Log All       │
   │  Actions             │
   └──────────┬───────────┘
              │
              ▼
   ┌──────────────────────┐
   │  Complete Workflow   │
   └──────────────────────┘
```

### System Components

**1. Webform Module**
- Collects initial submission data
- Validates input (CPR format, required fields)
- Triggers ECA workflow on submit

**2. ECA (Event-Condition-Action) Engine**
- Orchestrates workflow steps
- Manages state and transitions
- Handles error recovery

**3. MitID Integration (`aabenforms_mitid`)**
- Authenticates citizens
- Retrieves identity attributes (CPR, name)
- Supports authentication levels (Low, Substantial, High)

**4. CPR/CVR Lookup (`aabenforms_cpr`, `aabenforms_cvr`)**
- Queries Serviceplatformen (SF1520, SF1530)
- Caches results (30 minutes)
- Validates against submitted data

**5. Digital Post (`aabenforms_digital_post`)**
- Sends secure notifications (SF1601)
- Handles delivery confirmations
- Falls back to physical mail if needed

**6. Audit Logging (`aabenforms_gdpr`)**
- Records all actions
- Encrypts sensitive data
- Supports compliance reporting

**7. SBSYS Integration (`aabenforms_sbsys`)**
- Creates cases after approval
- Syncs status updates
- Archives documents

---

## Approval Scenarios

### Scenario 1: Happy Path (Both Parents Approve)

**Timeline**: ~3-5 minutes for parents, 24-48 hours for case worker

```
T+0m:   Municipality submits request
T+0m:   Emails sent to both parents
T+0m:   Digital Post queued (delivered within 24h)
T+15m:  Parent 1 opens email, authenticates, approves
T+2h:   Parent 2 opens email, authenticates, approves
T+2h:   Task assigned to case worker
T+24h:  Case worker reviews and approves
T+24h:  SBSYS case created
T+24h:  Digital Post sent to both parents
T+24h:  Workflow complete
```

**Audit Log**:
```
2026-02-01 09:15:00 - Webform submission #12345 created
2026-02-01 09:15:05 - Workflow 'daycare_enrollment' triggered
2026-02-01 09:15:10 - Email sent to parent1@example.com
2026-02-01 09:15:11 - Email sent to parent2@example.com
2026-02-01 09:15:15 - Digital Post queued (2 messages)
2026-02-01 09:30:22 - Parent 1 authenticated (MitID High, CPR: 010190-1234)
2026-02-01 09:30:25 - CPR lookup successful (SF1520)
2026-02-01 09:30:40 - Parent 1 approved
2026-02-01 11:20:15 - Parent 2 authenticated (MitID High, CPR: 020290-5678)
2026-02-01 11:20:18 - CPR lookup successful (SF1520)
2026-02-01 11:20:35 - Parent 2 approved
2026-02-01 11:20:40 - Task assigned to case worker (user_id: 45)
2026-02-02 09:15:00 - Case worker reviewed submission
2026-02-02 09:15:30 - Case worker approved
2026-02-02 09:15:35 - SBSYS case created (#98765)
2026-02-02 09:15:40 - Digital Post sent to parent1@example.com (delivered)
2026-02-02 09:15:41 - Digital Post sent to parent2@example.com (delivered)
2026-02-02 09:15:45 - Workflow completed
```

### Scenario 2: Rejection by Parent

**Timeline**: Stops immediately when parent rejects

```
T+0m:   Municipality submits request
T+0m:   Emails sent to both parents
T+15m:  Parent 1 approves
T+2h:   Parent 2 rejects (reason: "Date not suitable")
T+2h:   Workflow stops, notifications sent
T+2h:   Municipality notified
T+2h:   Parent 1 notified of rejection
```

**Outcomes**:
- ✗ No case worker review
- ✗ No SBSYS case created
- ✓ Municipality can resubmit with adjusted details
- ✓ All actions logged for audit

### Scenario 3: Timeout (No Response)

**Timeline**: Waits for deadline, then auto-rejects

```
T+0m:     Municipality submits request
T+0m:     Emails sent to both parents
T+15m:    Parent 1 approves
T+7d:     Reminder sent to Parent 2
T+14d:    Deadline reached, no response from Parent 2
T+14d:    Workflow auto-rejected
T+14d:    Municipality notified
T+14d:    Parent 1 notified of timeout
T+14d:    Parent 2 notified of missed deadline
```

**Outcomes**:
- ✗ Request not approved
- ✓ Can be resubmitted
- ✓ All parties notified of timeout

### Scenario 4: Case Worker Requests More Information

**Timeline**: Pauses for citizen response

```
T+0m:     Municipality submits request
T+2h:     Both parents approve
T+2h:     Task assigned to case worker
T+24h:    Case worker requests more information
          (Missing: Proof of address)
T+24h:    Citizen notified via email
T+48h:    Citizen uploads document
T+48h:    Task returns to case worker queue
T+72h:    Case worker reviews, approves
T+72h:    Workflow completes
```

**Outcomes**:
- ✓ Workflow pauses, doesn't timeout
- ✓ Citizen has 7 days to respond
- ✓ If citizen doesn't respond → auto-reject

### Scenario 5: Escalation to Supervisor

**Timeline**: Reassigned to higher authority

```
T+0m:     Municipality submits request
T+2h:     Both parents approve
T+24h:    Case worker escalates (reason: Policy exception needed)
T+24h:    Task assigned to supervisor
T+48h:    Supervisor reviews
T+48h:    Supervisor approves with note
T+48h:    Workflow completes
```

**Outcomes**:
- ✓ Proper approval authority
- ✓ Decision documented
- ✓ Exception noted in audit log

---

## GDPR Protections

### Data Visibility Rules

**Full Visibility** (Parents living together):
- Both parents see all information
- Child's full CPR visible
- Both parents' contact info visible
- Shared address shown

**Limited Visibility** (Parents living apart):
- Each parent sees only their own information
- Child's CPR masked (XXXXXX-1234)
- Other parent's contact info hidden
- Only relevant address shown

### Audit Trail Requirements

All these actions are automatically logged:
- MitID authentication attempts (success/failure)
- CPR/CVR lookups
- Data access (who viewed what)
- Approvals and rejections
- Case worker decisions
- System integrations (SBSYS, Digital Post)
- Data exports and deletions

### Citizen Rights

Citizens can exercise:
- **Right to Access**: Request copy of all data
- **Right to Rectification**: Request corrections
- **Right to Erasure**: Request deletion (after retention period)
- **Right to Restriction**: Request processing limitation
- **Right to Data Portability**: Export data in JSON/PDF

**Process requests at**: `/admin/config/aabenforms/gdpr/citizen-requests`

---

## Next Steps

- **Administrators**: See [Municipal Admin Guide](MUNICIPAL_ADMIN_GUIDE.md)
- **Developers**: See [CLAUDE.md](../CLAUDE.md) for technical details
- **Template Reference**: See [Workflow Templates](WORKFLOW_TEMPLATES.md)
- **Quick Help**: See [Quick Reference](QUICK_REFERENCE.md)

---

**Questions?** Contact your municipality's ÅbenForms administrator.
