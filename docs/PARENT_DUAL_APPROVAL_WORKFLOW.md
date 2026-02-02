# Parent Dual Approval Workflow Guide

## Use Case
Municipality sends request to parents for approval. Workflow handles two scenarios:
- **Parents Together**: Full data visibility, joint approval
- **Parents Apart**: Limited data visibility per parent, separate approvals

## Workflow Architecture

```
┌─────────────────────────────────────────┐
│  Municipality Initiates Request         │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  Check: Parents Together or Apart?      │
└────┬─────────────────────────┬──────────┘
     │                         │
     │ Together                │ Apart
     ▼                         ▼
┌─────────────┐          ┌──────────────┐
│ Full Data   │          │ Limited Data │
│ Visibility  │          │ Visibility   │
└──────┬──────┘          └──────┬───────┘
       │                        │
       └────────┬───────────────┘
                ▼
     ┌──────────────────────┐
     │  Parent 1 MitID Auth │
     └──────────┬───────────┘
                ▼
     ┌──────────────────────┐
     │  Parent 1 CPR Lookup │
     └──────────┬───────────┘
                ▼
     ┌──────────────────────┐
     │  Parent 1 Approval   │
     └──────────┬───────────┘
                ▼
     ┌──────────────────────┐
     │  Parent 2 MitID Auth │
     └──────────┬───────────┘
                ▼
     ┌──────────────────────┐
     │  Parent 2 CPR Lookup │
     └──────────┬───────────┘
                ▼
     ┌──────────────────────┐
     │  Parent 2 Approval   │
     └──────────┬───────────┘
                ▼
     ┌──────────────────────┐
     │  Case Worker Review  │
     └──────────┬───────────┘
                ▼
     ┌──────────────────────┐
     │  Digital Post to     │
     │  Parent 1 & Parent 2 │
     └──────────────────────┘
```

## Building in ECA UI

### Step 1: Create the Workflow

1. Navigate to: `/admin/config/workflow/eca`
2. Click **"Add model"**
3. **ID**: `parent_dual_approval`
4. **Label**: `Parent Dual Approval Workflow`
5. **Modeler**: `Fallback`

### Step 2: Configure the Event

**Event Type**: `Insert content entity (webform_submission)`

**Configuration**:
```
Type: webform_submission
Bundle: parent_request_form
```

This triggers when municipality submits the parent request form.

### Step 3: Add Gateway - Check Parent Status

**Gateway Type**: `Scalar comparison`

**Configuration**:
```
Left value: [webform_submission:field_parents_together]
Operator: ==
Right value: together
```

**Successors**:
- `true` → Full data visibility flow
- `false` → Limited data visibility flow

### Step 4: Parent 1 Authentication Flow

**Action 1**: `Set Token Value`
```
Token name: data_visibility
Token value: [conditional based on gateway]
```

**Action 2**: `Validate MitID Session` (aabenforms_mitid_validate)
```
workflow_id_token: workflow_id
result_token: parent1_mitid_valid
session_data_token: parent1_session
```

**Action 3**: `CPR Person Lookup` (aabenforms_cpr_lookup)
```
cpr_token: [parent1_session:cpr]
result_token: parent1_data
use_cache: true
```

**Action 4**: `Audit Log` (aabenforms_audit_log)
```
event_type: parent_approval_request
message_template: Parent 1 authenticated: [parent1_data:name]
cpr_token: [parent1_session:cpr]
```

### Step 5: User Task - Parent 1 Approval

**Action**: `Set Token Value`
```
Token name: parent1_approval_status
Token value: pending
```

*Note: In production, this would be a user task where Parent 1 views data and approves/rejects.*

### Step 6: Parent 2 Authentication Flow

(Same as Parent 1, but with `parent2_*` tokens)

**Action 1**: `Validate MitID Session`
```
workflow_id_token: workflow_id_2
result_token: parent2_mitid_valid
session_data_token: parent2_session
```

**Action 2**: `CPR Person Lookup`
```
cpr_token: [parent2_session:cpr]
result_token: parent2_data
use_cache: true
```

**Action 3**: `Audit Log`
```
event_type: parent_approval_request
message_template: Parent 2 authenticated: [parent2_data:name]
cpr_token: [parent2_session:cpr]
```

### Step 7: Case Worker Review

**Action**: `Set Token Value`
```
Token name: caseworker_status
Token value: pending_review
```

**Audit Log**:
```
event_type: caseworker_review_assigned
message_template: Case assigned to case worker
```

*Note: In production, case worker sees task at `/admin/workflows/tasks`*

### Step 8: Gateway - Case Worker Decision

**Gateway Type**: `Scalar comparison`

**Configuration**:
```
Left value: [caseworker_decision]
Operator: ==
Right value: approved
```

**Successors**:
- `true` → Send Digital Post
- `false` → Reject workflow

### Step 9: Send Digital Post Notifications

**For Parents Together**:
```
Action: Send Digital Post (SF1601)
Recipient: [parent1_data:cpr], [parent2_data:cpr]
Message: Request approved - view details at borger.dk
```

**For Parents Apart**:
```
Action 1: Send Digital Post to Parent 1
Recipient: [parent1_data:cpr]
Message: Request approved - your portion available

Action 2: Send Digital Post to Parent 2
Recipient: [parent2_data:cpr]
Message: Request approved - your portion available
```

## Data Visibility Control

### Full Visibility (Parents Together)
```php
// Parent 1 and Parent 2 can see:
- Child's full name
- Child's CPR
- Both parents' names
- Shared address
- Full case details
```

### Limited Visibility (Parents Apart)
```php
// Parent 1 sees:
- Child's full name
- Child's CPR (masked: XXXXXX-1234)
- Parent 1's own name
- Parent 1's address
- Limited case details (only relevant to Parent 1)

// Parent 2 sees:
- Child's full name
- Child's CPR (masked: XXXXXX-1234)
- Parent 2's own name
- Parent 2's address
- Limited case details (only relevant to Parent 2)
```

## Webform Configuration

Create form at `/admin/structure/webform`:

**Form ID**: `parent_request_form`

**Fields**:
```
- child_name (textfield)
- child_cpr (cpr_field) - Custom ÅbenForms element
- parent1_email (email)
- parent2_email (email)
- parents_together (radios)
  - Options: together | apart
- request_details (textarea)
- caseworker_notes (textarea, admin only)
```

## Testing the Workflow

### Test Scenario 1: Parents Together

1. Municipality submits form with `parents_together: together`
2. Email sent to Parent 1 with MitID login link
3. Parent 1 authenticates → sees FULL data → approves
4. Email sent to Parent 2 with MitID login link
5. Parent 2 authenticates → sees FULL data → approves
6. Case worker reviews → approves
7. Digital Post sent to BOTH parents at same address

### Test Scenario 2: Parents Apart

1. Municipality submits form with `parents_together: apart`
2. Email sent to Parent 1 with MitID login link
3. Parent 1 authenticates → sees LIMITED data → approves
4. Email sent to Parent 2 with MitID login link
5. Parent 2 authenticates → sees LIMITED data → approves
6. Case worker reviews → approves
7. Digital Post sent SEPARATELY to each parent

## GDPR Compliance

All CPR lookups and data access are audited:

```
Audit Log Entries:
- Parent 1 MitID authentication
- Parent 1 CPR lookup (SF1520)
- Parent 1 data access (with visibility level)
- Parent 2 MitID authentication
- Parent 2 CPR lookup (SF1520)
- Parent 2 data access (with visibility level)
- Case worker access
- Digital Post delivery confirmation
```

## Production Enhancements

1. **Email Notifications**: Use ECA email actions to notify parents
2. **Task Management**: Create user tasks for parent approvals
3. **Deadline Tracking**: Add timer events (7 days to respond)
4. **Escalation**: Auto-escalate if parent doesn't respond
5. **Digital Post Integration**: Actually call SF1601 service
6. **SBSYS Integration**: Create case in SBSYS upon approval

## Next Steps

1. Build this workflow in ECA UI following steps above
2. Create the webform at `/admin/structure/webform`
3. Test with mock MitID data (see WORKFLOW-TESTING.md)
4. Review audit logs at `/admin/reports/dblog`
5. Iterate based on case worker feedback
