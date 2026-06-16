# AabenForms Data Flow

Drupal 11 backend. How a webform submission drives a synchronous ECA workflow, where
PII (CPR, CVR, names, addresses, emails) enters, is logged, encrypted, sent, returned,
and where the trust boundaries and token chains break.

This is a tracing document. Every claim below is anchored to a file:line or a verified
runtime observation. It is not a design spec - it records what the code actually does as
shipped, which in several places diverges from intent.

Source roots referenced throughout:
- Flows: `config/sync/eca.eca.<name>.yml`
- Webforms: `config/sync/webform.webform.<id>.yml`
- Actions: `web/modules/custom/aabenforms_workflows/src/Plugin/Action/*.php`
- Digital Post action: `web/modules/custom/aabenforms_digital_post/modules/aabenforms_digital_post_eca/src/Plugin/Action/SendDigitalPostAction.php`
- Public entry: `web/modules/custom/aabenforms_core/src/Controller/WebformApiController.php`
- Audit: `web/modules/custom/aabenforms_core/src/Service/AuditLogger.php`

---

## 1. Overview - submission to synchronous flow to workflow.steps

1. A client POSTs to `/api/webform/{id}/submit` (`WebformApiController::submitWebform`).
   The route requires permission `access webform api`, which `config/sync/user.role.anonymous.yml`
   grants to anonymous (anonymous holds exactly `access content` + `access webform api`).
   The controller then calls `$webform->access('submission_create')` (WebformApiController.php:91),
   which is the only per-form gate. Most citizen forms allow `anonymous`, so the controller
   gate passes for unauthenticated callers; the staff/parent forms restrict create by role and
   return 403 for anonymous.
2. The controller writes the POST body verbatim into the `webform_submission` entity
   (the `webform_submission_data` table, plaintext) plus `remote_addr`/`uri`, then `save()`.
3. The save fires a content_entity `insert` (or `update`) event. ECA flows in `config/sync/eca.eca.*.yml`
   are matched by event + entity type + bundle (webform id). Matching flows run
   **synchronously, in-process**, before the HTTP response returns.
4. Each flow is an ordered chain of actions. Actions read config tokens
   (`cpr_token`, `recipient_token`, `workflow_id_token`, `session_data_token`, `result_token`, ...)
   and write result tokens into the ECA token environment (data bag).
   `AabenFormsActionBase::getTokenValue` resolves bracketed tokens
   (`[webform_submission:values:cpr:raw]`, `[parent1_session:cpr]`) via the token service;
   a **bare** name (`cpr`, `workflow_id`) is a data-bag key, NOT a resolved token.
5. Each action appends a step to a `WorkflowExecutionCollector`. After the flow finishes,
   the controller serializes `collector->toArray()` into the 201 JSON response as
   `workflow.steps` (label, description, status). The caller sees the step narrative.
6. **Critical property:** the flow runs to completion regardless of intermediate failure.
   ECA successor edges in these flows have empty conditions (`condition: ''`), so a FALSE
   MitID/CPR result does not stop later actions (Digital Post, audit) from running.

What is returned to the caller: only `workflow.steps` text (labels/descriptions/status).
This text was checked and is static/honest - no raw CPR/name is interpolated into step
descriptions. The one exception class is **error leakage** on the CVR/Digital Post paths
(see Section 6, external edges).

---

## 2. Per-flow data maps

Legend for sinks: SF1520 = CPR registry, SF1530 = CVR registry (both via
`aabenforms_core` ServiceplatformenClient); SF1601 = Digital Post (via the separate
`aabenforms_digital_post` module, `test_mode: fake_db` -> mock). Demo mode for CPR is
active whenever `aabenforms_core.settings serviceplatformen.certificates cert_path/key_path`
are empty (verified empty), via `CprLookupAction::demoModeAllowed()` (CprLookupAction.php:58-64).

### 2a. Citizen self-service (anonymous, unverified)

These forms all set `access.create = [anonymous, authenticated]`. There is no MitID action
and no gating condition. The raw form CPR drives both the registry lookup key and the
Digital Post recipient. They are the systemic exposure class (Section 6, trust_boundaries).

#### address_change_flow
- **Trigger:** insert on `address_change`. **Who:** anyone (anonymous).
- **Fields / PII:** `cpr` (free-text, regex only), `old_address`, `new_address`, `moving_date`, `consent`.
- **Token chain:** `[webform_submission:values:cpr:raw]` -> CprLookupAction (digits only,
  CprLookupAction.php:124) -> demo person `citizen_data = {cpr: 6digits+XXXX, full_name:'Demoborger (testdata)', demo:true}`
  (CprLookupAction.php:137-142); no SF1520 call. Same raw token -> SendDigitalPostAction
  `resolveRecipient()` reads the field straight off the submission entity (SendDigitalPostAction.php:271-276),
  NOT `citizen_data` -> `Recipient::cpr(raw)`.
- **External sinks:** SF1520 demo (no certs); SF1601 fake_db mock; Sender CVR 12345678 'Test Kommune'.
- **Logged:** watchdog `CPR lookup ran in demo mode`; one audit row with identifier `system`
  (the `cpr_token` defaults to bare `cpr`, never set on insert -> empty -> else-branch
  `system`, AuditLogAction.php:122-123,157); DP log row with `recipient_identifier_hash`
  = sha256('cpr:'+digits) (no cleartext CPR). The configured `[citizen_data:name]`
  additional-data token is dropped (read raw via getTokenData, not an array - AuditLogAction.php:137-139).
- **Returned:** full `workflow.steps` (demo step text), no raw PII.

#### parking_permit_flow
- **Trigger:** insert on `parking_permit`. **Who:** anyone.
- **Fields / PII:** `cpr`, `address`, `vehicle_registration`, `duration_months` (plaintext in submission + remote_addr).
- **Token chain:** identical shape to address_change. `[...:cpr:raw]` -> demo `citizen_data`;
  same raw token -> Digital Post recipient.
- **External sinks:** SF1520 demo, SF1601 fake_db. No real egress.
- **Logged:** audit row event `parking_permit_submitted`, identifier `system` (hashed),
  context `[]`. Plaintext CPR/address/plate persist in `webform_submission_data`.
- **Returned:** step labels/status only.

#### building_permit_flow
- **Trigger:** insert on `building_permit`. **Who:** anyone. Proven live: anonymous (uid=0)
  submission with CPR 0101901234 produced dp_log recipient_identifier_hash = sha256('cpr:0101901234').
- **Fields / PII:** `cpr`, `applicant_name`, `property_address`, `project_description` (raw plaintext in submission).
- **Token chain:** `[...:cpr:raw]` -> CprLookupAction demo (CprLookupAction.php:133-143),
  writes `citizen_data` which **no later action reads** (dead token). Audit `cpr_token`
  defaults to bare `cpr` (unset) -> identifier `system` -> sha256('system')=bbc5e661...
  (verified across all building_permit_submitted rows). Same raw token -> resolveRecipient
  Strategy 1 (SendDigitalPostAction.php:271-276) -> Recipient::cpr() (validates 10 digits only,
  no mod-11) -> FakeSendDatabaseLogger row.
- **External sinks:** SF1520 demo, SF1601 fake_db (recipient hashed, static subject/body, synthetic transaction_id).
- **Returned:** `workflow.steps` only.

#### citizen_service_application_flow
- **Trigger:** insert on `citizen_service_application`. **Who:** anyone. Controller checks only `submission_create` (WebformApiController.php:91).
- **Fields / PII:** `applicant_name`, `applicant_cpr`, `applicant_email`, `service_type`,
  `application_details`, `supporting_info`, `caseworker_email`.
- **Token chain (as actually executed on insert):** `mitid_validate` reads bare
  `workflow_id_citizen_service` -> '' -> recordNoSession -> `citizen_mitid_valid`=FALSE,
  `citizen_session` never set (MitIdValidateAction.php:135-140). The FALSE result is a
  **dead token** - no condition reads it. `cpr_lookup` reads `[citizen_session:cpr]` -> empty,
  but demo mode is TRUE so it emits demo `citizen_data` (key `full_name`, not `name`).
  `log_received` and `log_caseworker_assigned` read additional-data tokens as bare keys ->
  not arrays -> dropped (caseworker email never captured in audit context). `send_decision`
  resolveRecipient Strategy 1 matches `[webform_submission:values:applicant_cpr:raw]` -> the
  raw, unverified attacker CPR becomes the Digital Post recipient.
- **External sinks:** SF1520 bypassed (demo); SF1601 fake_db.
- **Logged:** audit row, identifier sha256-hashed, context empty (token mismatch). Raw
  `applicant_cpr` + `applicant_email` + remote_addr persist unredacted in submission storage.
- **Returned:** `workflow.steps` only.

#### company_verification_flow
- **Trigger:** insert on `company_verification`. **Who:** anyone (audit rows confirm uid=0).
- **Fields / PII:** `cvr` (8 digits), `director_cpr` (DDMMYY-XXXX), `verification_purpose`.
- **Token chain:** `cvr` -> CvrLookupAction `[webform_submission:values:cvr:raw]` ->
  `ServiceplatformenClient->request('SF1530','CompanyLookup',['cvr'=>cvr])` -> `company_data`.
  `director_cpr` is collected but **never read by any action** - dead PII at rest.
- **External sinks (REAL, NO demo guard):** SF1530 makes a real HTTPS POST to
  `https://exttest.serviceplatformen.dk/service/CVR/CVROnline/1` even with no client cert -
  the raw 8-digit CVR leaves the box in the SOAP envelope before the TLS handshake fails.
  SF1601 Digital Post -> fake_db (recipient hashed, verified row 31).
- **Logged:** audit identifier `system` (sha256('system')=bbc5e661... on rows 702/709/723),
  purpose 'Company verification request received; CVR resolved.', uid=0 - the actual CVR
  queried is NOT recorded in audit. BUT `CvrLookupAction->log()` writes the raw cleaned CVR
  in cleartext to the Drupal log channel: 'Performing CVR lookup via SF1530 for: {cvr}'
  (CvrLookupAction.php:109-111).
- **Returned:** `workflow.steps` incl. 'Digital Post sent'. (See Section 6 - CVR error leak.)

#### marriage_booking_flow
- **Trigger:** insert on `marriage_booking` (webform.webform.marriage_booking.yml:182-188 grants anonymous create). **Who:** anyone.
- **Fields / PII:** `partner1_cpr`, `partner1_name`, `partner2_cpr`, `partner2_name`,
  `ceremony_date`, `witness1/2_name`.
- **Token chain:** both partner CPRs -> getTokenValue (AabenFormsActionBase.php:151-164) ->
  CprLookupAction digits -> demo `partner1_data`/`partner2_data` (CprLookupAction.php:133-143);
  no SF1520. Audit `cpr_token` omitted -> default bare `cpr` (AuditLogAction.php:48) -> empty
  -> identifier `system`. send_confirmation reads `partner1_cpr` straight off the entity
  (SendDigitalPostAction.php:271-276) -> Recipient::cpr().
- **External sinks:** SF1520 demo, SF1601 fake_db (recipient hash, CVR 12345678).
- **Returned:** `workflow.steps` to anonymous caller.

#### foi_request_flow (anonymous, no external sink)
- **Trigger:** insert on `foi_request`. **Who:** anyone (observed sids 142/153 uid=0).
- **Fields / PII:** `requester_name`, `requester_email`, `request_text` - self-asserted, unverified.
- **Token chain:** the flow's only action is AuditLogAction; it reads **none** of the three
  PII fields. `cpr_token` default `cpr` is unset -> empty -> AuditLogger.log('foi_request_submitted',
  'system', static-message, 'success'). Audit row carries NO requester PII (sha256('system')=bbc5e661...,
  uid=0, verified on both existing rows).
- **External sinks:** NONE. No SF1520/1530, no SF1601, no email handler (getHandlers()=0), no handoff.
- **PII at rest:** `requester_name`/`email`/`request_text` land UNREDACTED and indefinitely in
  `webform_submission_data` (verified: 'Sofie Hansen' / sofie@test.dk in sid 153) + remote_addr (172.19.0.7).
- **Gap:** confirmation promises a statutory 7-working-day response, but **no actor is ever notified**.

### 2b. Citizen + MitID intended (elections - gate non-functional)

#### med_election_nomination_flow
- **Trigger:** insert on `med_election_nomination`. **Who:** anyone (anonymous holds `access webform api`; create=[anonymous,authenticated]).
- **Fields / PII:** `nominator_cpr`, `nominator_name`, `nominee_name`, `election_id`, `statement`, `consent`.
- **Intended chain:** MitID session -> `nominator_session` -> `[nominator_session:cpr]` -> CprLookupAction.
- **Observed runtime chain (live PT_ submission, since deleted):** logger emitted exactly two
  warnings - 'MitID validation failed: no workflow id in context (demo mode off)' and 'CPR lookup
  skipped: no CPR available to look up'. So (a) `workflow_id_med_nomination` unset on insert ->
  recordNoSession sets `nominator_mitid_valid`=FALSE (MitIdValidateAction.php:137-140), fails
  closed but flow continues; (b) `nominator_session` never set -> `[nominator_session:cpr]` empty
  -> CprLookupAction skips entirely (CprLookupAction.php:127-129). The submitted CPR is NEVER
  looked up and never reaches Serviceplatformen.
- **External sinks:** ONLY the `aabenforms_audit_log` table. Observed row: action=med_nomination_recorded,
  identifier_hash=sha256('system')=bbc5e661..., status=success, context={"action_id":"aabenforms_audit_log"}.
  `[webform_submission:values:nominee_name:raw]` did not populate context (bare-key drop).
- **Returned:** `workflow.steps`. No CPR/CVR, no Digital Post, no external network call.

#### med_election_voting_flow (inert as shipped)
- **Path A (ballot insert):** the target webform `med_election_ballot` **does not exist**, so the
  insert event can never fire. Even if it did, `validate_voter_mitid` reads `workflow_id_med_voting`
  (never set on insert -> fails closed), and `record_ballot` follows it with an EMPTY successor
  condition - so recording is not actually gated on `voter_mitid_valid`.
- **Path B (cron):** `tabulate_due` is system-triggered, no external trust boundary; writes JSON
  tallies into `aabenforms_election.results`.
- **Token chain (intended, correct in design):** voter CPR comes from `[voter_session:cpr]`
  (MitID session, NOT form input) -> RecordBallotAction -> `ElectionService::voterHashFor` ->
  sha256(election_id+':'+cpr) -> `aabenforms_election_ballot.voter_hash`. Raw CPR is NOT
  persisted and NOT logged.
- **External sinks:** local DB tables `aabenforms_election` / `aabenforms_election_ballot` only;
  no Serviceplatformen, no Digital Post, no email.
- **Net:** citizen voting path is inert (no webform); the MitID trust boundary is structurally
  not enforced by any condition.

### 2c. Internal / HR (staff-gated, no external sink, captures little)

These forms restrict `access.create` to a staff role, so anonymous hits 403 at WebformApiController.php:91.
Inputs are authenticated-but-unverified free text (no MitID, no binding to session identity).
None reach an external sink; most PII the flows are configured to capture is silently dropped by
the same getTokenData/is_array bare-key bug.

#### hr_onboarding_flow
- **Trigger:** insert on `hr_onboarding`, role `aabenforms_employee` only (webform yml:197-202).
- **Fields / PII:** `new_hire_name`, `new_hire_email`, `start_date`, `department`, `job_title`,
  `manager_email`, `it_distribution_email`, `equipment_needs`.
- **Token chain:** `lookup_manager` reads `employee_id_token=[...:hr_submitter_email:raw]` - field
  does NOT exist on the webform -> empty -> StubOrgChartService.findManagerEmail('', manager_email)
  returns the form manager_email into `hr_onboarding_manager_email` (step 'employee_id=unknown').
  All 3 audit actions write context exactly `{"action_id":"aabenforms_audit_log"}` - no new-hire
  name/email/manager/IT email reaches the row (bracketed-scalar additional-data token returns NULL
  from getTokenData; verified NULL).
- **External sinks:** none. **Net:** leaks no PII; also captures none of the PII it is configured to capture.

#### mileage_expense_flow
- **Trigger:** insert on `mileage_expense`, role `aabenforms_employee` only (webform yml:204-207;
  verified anonymous submission_create = NO). Fields are self-asserted and not tied to the
  authenticated identity - any employee can claim any `employee_id` / route to any `manager_email`.
- **Fields / PII:** `employee_name`, `employee_id`, `manager_email`, `amount`, `claim_type`.
- **Token chain:** log_received identifier `system` (sha256('system')=bbc5e661..., audit row 882);
  `[...:employee_name:raw]` dropped (getTokenData returns a DTO not array). lookup_manager ->
  StubOrgChartService fallback -> `mileage_expense_manager_email`. forward_to_payroll
  (PayrollPostAction -> PayrollService::forward): `employee_id` SHA-256 hashed before insert
  (employee_id_hash=94417c1e...=sha256('EMP-PT-9999')); amount '1234.56' DKK -> 123456 oere;
  payload JSON only `{"submission_id":194}`; mode=fake_db, response 'fake_db:synthetic-receipt',
  always STATUS_SUCCESS. Writes local `aabenforms_payroll_log`. result token unused downstream.
- **External sinks:** none (fake_db payroll). **Returned:** step descriptions expose only a
  4-char-truncated employee_id (ManagerLookupAction substr 0,4 + '****'), no full PII.

#### phone_declaration_flow
- **Trigger:** insert on `phone_declaration`, role `aabenforms_employee` only (webform yml ~330;
  verified anon denied, employee allowed). No re-verification that submitted employee/manager
  identity belongs to the session user; no MitID anywhere.
- **Fields / PII:** `employee_name`, `employee_id`, `tax_year`, `phone_number`, `private_use_declared`,
  `manager_email`, `declaration_date`.
- **Token chain:** all four audit/lookup steps hit the same getTokenData/is_array drop -
  `employee_name`, manager email, tax_year never logged; identifier falls back to literal `system`
  (AuditLogAction.php:157). lookup_manager -> StubOrgChartService fallback ->
  `phone_declaration_manager_email`; step embeds substr(employee_id,0,4)+'****'.
- **External sinks:** NONE (no SF1520/1530/1601, no email, no MitID). Only `aabenforms_audit_log`,
  all rows sha256('system'), context `{"action_id":"aabenforms_audit_log"}`. Zero PII persists in
  the audit trail; zero PII leaves the system.

#### association_booking_flow (mixed - anonymous + real CVR + mock payment)
- **Trigger:** insert on `association_booking`, create=[anonymous,authenticated] (webform yml:213-218). **Who:** anyone.
- **Fields / PII:** `association_name`, `cvr`, `contact_name`, `contact_email`, `contact_phone`,
  `reviewer_email`, `amount`, free-text notes/purpose - all unverified.
- **Token chain:** `mitid_validate` fails closed on insert (no workflow_id token) but its FALSE
  result is never checked (decorative boundary). `cvr` -> CvrLookupAction -> ServiceplatformenClient
  SF1530 SOAP POST to exttest.serviceplatformen.dk (raw 8-digit CVR leaves the box even though the
  handshake fails); `cvr` also written cleartext to watchdog (CvrLookupAction.php:109-111).
  `amount` -> PaymentService mock -> payment_id/transaction_id written back via setElementData+save().
- **External sinks:** SF1530 attempted (real network, no cert); payment is a mock; **Digital Post
  is NOT invoked at all** (no recipient notification despite log_reviewer_assigned claiming an email stub).
- **Logged:** audit identifier literal `system` sha256-hashed; configured additional-data tokens
  (`association_name`, `reviewer_email`) SILENTLY DROPPED (bare-key/array gate). Nothing encrypted.
- **Live test (submission #189, deleted):** MitID step status=failed, CVR step status=failed
  (SSL/cert), yet payment_status=completed and all 3 audit rows status=success.

### 2d. Parent approval (case_worker-gated; the real gate is in the controller)

`parent_request_form` restricts `access.create` to role `case_worker`
(webform.webform.parent_request_form.yml:244-249); `parent1_cpr`/`parent2_cpr`/`caseworker_notes`
are further restricted to administrator/case_worker via `#access_*_roles`. Anonymous hits 403.
So unlike the citizen forms, the originating submission is **not** attacker-controllable.

The real identity gate (MitID-asserted CPR == the captured parent CPR) is enforced in the
**controller**, `ParentApprovalController::mitidComplete()` (returns 403 on mismatch), not in
these ECA flows. Every parent ECA flow reads MitID/CPR tokens that are never populated on the
insert/update event and produces result tokens that **no condition consumes** - so the flows
provide no enforcement of their own. Multiple flows fire on the same parent_request_form events.

#### parent_submission_simple
- **Trigger:** case_worker creates a parent_request_form submission (insert).
- **Fields / PII:** `child_cpr`, `parent1_cpr`, `parent2_cpr` (plaintext), emails, names.
- **Token chain:** validate_mitid reads bare `workflow_id` -> '' (nothing ever sets it) ->
  recordNoSession; demo OFF -> mitid_result=FALSE, mitid_session never set
  (MitIdValidateAction.php:135-140). lookup_cpr reads `[mitid_session:cpr]` -> '' (verified
  replaceClear returns '') -> CprLookupAction skips, `cpr_person_data`=NULL (CprLookupAction.php:127-131).
  audit_log: `[webform_submission:sid]` resolves; `[cpr_person_data:name]` read as literal data-bag
  key (AuditLogAction.php:138) -> dropped; `cpr_token` default bare `cpr` -> identifier `system`.
- **External sinks:** none (SF1520 never called, no Digital Post, no MitID call). PII stays in
  submission storage.

#### parent1_approval_flow / parent2_approval_flow
- **Trigger:** ParentApprovalForm::submitForm sets `parent{N}_status` = 'complete'/'rejected'
  THEN save() (ParentApprovalForm.php:319-330), firing content_entity:update.
- **Real-path note:** because the status is flipped BEFORE save, the
  `check_parent{N}_pending == 'pending'` condition is FALSE - the entire chain is **skipped in the
  real approval path**.
- **Token chain (if it ever ran):** parent{N}_mitid reads bare `workflow_id_parent{N}` (never
  produced; confirmed by grep across web/modules + config/sync) -> '' -> recordNoSession -> demo
  off -> `parent{N}_mitid_valid`=FALSE (never gated). `parent{N}_cpr_verify` derives a different
  workflow id `parent_approval_<sid>_p{N}` and calls ParentCprVerifier::verify, which reads the
  parent CPR off the submission and the asserted CPR from the (usually absent) session, writes a
  **hashed-only** audit row (ParentCprVerifier hashes both CPRs, never raw) and a
  `parent{N}_cpr_consent` token that nothing consumes. `[parent{N}_session:cpr]` resolves empty ->
  CprLookupAction skips; with certs empty it would run demo (CprLookupAction.php:133-143).
  parent{N}_audit -> identifier `system`, `[parent{N}_data:name]` dropped.
- **External sinks:** AuditLogger only (hashed / no-PII here). SF1520 NOT called (demo/skip).
  MitID session manager read only via the controller-derived id in the verify step.
- **parent2 extra:** mark_parent2_complete sets only an ECA token; it is **not** persisted to the
  entity (no setElementData/save).

#### parent_dual_approval_working
- **Trigger:** case_worker creates parent_request_form (insert). Parents are NOT the actors; their
  identity is NOT verified in this flow.
- **Token chain:** `workflow_id_parent1/2` unset on insert -> recordNoSession -> demo off ->
  `parent{N}_mitid_valid`=FALSE; `parent{N}_session` never written. `[parent1_session:cpr]` resolves
  '' -> CprLookupAction empty-CPR guard (CprLookupAction.php:127-131) -> `parent1_data`=NULL, step
  'skipped' (demo branch is below the empty-CPR return, so NOT reached). `[parent1_data:name]` read
  as literal bare key -> dropped. No audit cpr_token -> identifier `system`.
- **Sinks:** `aabenforms_audit_log` only (all rows sha256('system')=bbc5e661...,
  context={"action_id":"aabenforms_audit_log"}). No SF1520, no Digital Post. Parent/child CPR,
  emails, names remain only in submission storage; not propagated, encrypted, or transmitted.
- **Net:** the in-flow "dual parent MitID approval" is theatre - it never verifies either parent,
  never blocks on failure, yet the terminal step asserts "Both parents approved".

#### caseworker_review_flow
- **Trigger:** content_entity:update on parent_request_form (case_worker-gated). Conditions read
  `parent1_status`/`parent2_status`/`caseworker_status` (hidden elements flipped by the parent
  approval flows) via `[webform_submission:data:*]`.
- **Token chain:** caseworker_audit `cpr_token` default `cpr` (AuditLogAction.php:48) not in stored
  config -> NULL -> identifier `system` (else-branch, line 157). Verified: every caseworker_review
  row identifier_hash = sha256('system')=bbc5e661.... additional_data_token is a BARE string
  'Both parents approved' passed to getTokenData (line 138) -> non-array -> dropped; stored context
  only `{"action_id":"aabenforms_audit_log"}`.
- **Sinks:** AuditLogger only (uid/action/hash/purpose/status/ip/context + watchdog info line). No
  Serviceplatformen/Digital Post/MitID/email. mark_caseworker_assigned writes
  `caseworker_status=assigned` to the ECA token environment only - never persisted to the entity.
- **Status-scalar fragility:** the caseworker condition concatenates two statuses into
  'completecomplete' (fragile/ambiguous; see token_integrity in Section 6).

---

## 3. Trust boundaries

| Boundary | Where enforced | Status |
|---|---|---|
| Anonymous -> can submit form | WebformApiController.php:91 `$webform->access('submission_create')` + per-form `access.create.roles` | Enforced. Citizen forms allow anonymous; staff/parent forms 403 anonymous. |
| Anonymous webform input -> MitID-verified identity | Intended via MitIdValidateAction in citizen/election flows | NOT enforced. `workflow_id_*` token never set on insert -> fails closed -> result token is dead (no condition reads it) -> flow continues. |
| Submitter owns the CPR/CVR they typed | (none in citizen flows) | NOT enforced. Raw `[webform_submission:values:*:raw]` is the registry lookup key AND the Digital Post recipient. `require_parent_cpr_match:true` exists but is consumed ONLY by the parent path. |
| Parent identity == captured parent CPR | `ParentApprovalController::mitidComplete()` (403 on mismatch); ParentCprVerifier | Enforced in the controller, NOT in the ECA flows. ECA result tokens are informational only. |
| Staff-form input bound to session user | (none) | NOT enforced. HR/mileage/phone fields are self-asserted; any employee can claim any id - but these never reach an external sink. |

Net systemic finding: in every insert-triggered flow the anonymous-to-verified boundary is
decorative. MitID validate runs with an unset `workflow_id` token (fails/empties), and every ECA
successor is unconditional (`condition: ''`), so even a FALSE MitID result does not stop the send.
Proven live: an anonymous citizen_service submission gave MitID=failed, CPR=skipped, yet Digital
Post=completed to the raw form CPR.

---

## 4. PII sink table

How each PII class is treated at each sink. "Hashed" = sha256, irreversible. "Plaintext" = stored/sent as-is.

| PII | Sink | Treatment | Evidence |
|---|---|---|---|
| CPR (form) | webform_submission_data | **Plaintext, indefinite** | inherent to webform; verified raw values in sids |
| CPR (form) | watchdog (CprLookupAction) | Masked: `substr(cpr,0,6)+'XXXX'` | CprLookupAction.php:138,150,168 |
| CPR (form) | aabenforms_audit_log identifier | Hashed; in practice the literal 'system' is hashed (cpr_token unwired) | AuditLogger.php:163; identifier 'system' AuditLogAction.php:157 |
| CPR (form) | aabenforms_digital_post_log | Hashed: `sha256('cpr:'+digits)` | Recipient.php:55; verified building_permit row |
| CPR (form) | SF1520 | NOT sent - demo mode (no cert) short-circuits | CprLookupAction.php:58-64 |
| CPR (verified session) | election ballot | Hashed: `sha256(election_id+':'+cpr)` | ElectionService::voterHashFor (path inert) |
| CPR (parent) | ParentCprVerifier audit | Hashed only, never raw | ParentCprVerifier (both CPRs hashed) |
| CVR (form) | SF1530 | **Plaintext SOAP egress** to exttest.serviceplatformen.dk (no demo guard) | CvrLookupAction.php:119; company_verification, association_booking |
| CVR (form) | watchdog (CvrLookupAction) | **Plaintext** 'Performing CVR lookup via SF1530 for: {cvr}' | CvrLookupAction.php:109-111 |
| CVR (form) | aabenforms_audit_log | NOT recorded (identifier 'system'; actual CVR not stored) | rows 702/709/723 |
| names/emails/addresses | webform_submission_data | **Plaintext, indefinite** | foi_request sid 153 verified |
| names/emails (configured additional-data) | aabenforms_audit_log context | DROPPED (getTokenData bare-key returns non-array; AuditLogAction.php:137-139) | context column = {"action_id":...} only |
| DP subject + body | aabenforms_digital_post_log payload + subject column | **Plaintext template** (can interpolate PII tokens); NO retention/purge | FakeSendDatabaseLogger |
| employee_id | aabenforms_payroll_log | Hashed: `sha256(employee_id)` | mileage_expense row 882 (94417c1e...) |
| director_cpr (company_verification) | (nowhere) | Collected, never read - dead PII at rest in submission | no token references it |

EncryptionService note: `EncryptionService::encryptCpr` (AES-256) is fully wired as a service and
the encrypt/key/real_aes modules are installed, but it has **zero production callers** - raw 10-digit
CPRs are stored in plaintext in `webform_submission_data`.

---

## 5. Template vs flow map

Two independent, divergent representations of each workflow:

- **BPMN templates** (13 files under `web/modules/custom/aabenforms_workflows/workflows/*.bpmn`):
  consumed ONLY by the wizard path (BpmnTemplateManager + WorkflowTemplateInstantiator). Encode the
  rich intent: MitID auth, CPR/CVR verification, Adressevælger, caseworker review, SBSYS/journaling, SF1601.
- **ECA YAML flows** (18 hand-authored `config/sync/eca.eca.*.yml`): what actually runs. Confirmed
  the active DB config set is exactly those 18; NO wizard-generated `eca.eca.<template>_<timestamp>`
  entities exist. Implements a much thinner (sometimes entirely different) action chain than the BPMN.
- The wizard, if ever used, would generate a THIRD, algorithmically-derived flow (graph-walk +
  keyword heuristic + `aabenforms_log` placeholders) resembling neither reliably.

| Active YAML flow | Matching BPMN template? | Notes |
|---|---|---|
| address_change / parking_permit / building_permit / citizen_service / company_verification / marriage_booking / association_booking / foi_request / med_election_nomination | thinner / divergent | BPMN encodes MitID+verify+journaling; YAML omits |
| contact_form | orphaned BPMN, no flow, no wizard instance | dead |
| caseworker_review_flow | none | YAML-only |
| med_election_voting_flow | none | YAML-only (and inert) |
| parent1_approval / parent2_approval | none | YAML-only |
| parent_dual_approval_working | none | YAML-only |
| parent_submission_simple | none | YAML-only |

The security-critical parent-approval and caseworker flows exist ONLY as YAML and would never be
reproduced or maintained through the BPMN/wizard surface the product UI presents as the source of truth.

---

## 6. Known edges and gaps

### Trust boundaries (systemic, broader than #73)
Anonymous users can trigger real SF1601 Digital Post and SF1520/SF1530 lookups to an arbitrary,
attacker-supplied CPR/CVR across 6 citizen flows. Recipient is read straight from
`[webform_submission:values:FIELD:raw]` with no MitID verification and no ownership check. Proven
live (building_permit, dp_log #32 = sha256('cpr:0101901234')). Related: #73 (scopes its fix to DP
recipient-from-verified-session + idempotency on the parent flows only), #54, #79.

### Token integrity (structural)
The MitID-validate -> CPR-lookup -> Digital-Post chain is broken in every citizen flow.
MitIdValidateAction reads a bare `workflow_id_*` token that nothing writes on insert/update; with
`allow_mitid_demo_mode` unset it fails closed (result_token=FALSE), but the result_token is a DEAD
token (no gateway reads it) and every successor edge is unconditional. The CPR lookup then gets an
empty `[*_session:cpr]` (skipped/demo) and Digital Post sends to the raw form value. If the demo
flag were ON, MitIdValidateAction would fall through to demo and record an unverified identity as
"verified" - the fall-through the parent path was hardened against (ParentCprVerifyAction derives a
session id; MitIdValidateAction does not). Parent `mark_parent*_complete` sets only an in-memory ECA
token, not the persisted field caseworker_review reads, and that scalar concatenates to
'completecomplete'. Related: #79, #54, #88.

### PII lifecycle / at-rest
Identifiers are hashed at the audit and DP sinks (good), but: (1) EncryptionService is dead code -
raw CPRs sit plaintext in webform_submission_data; (2) AuditLogAction is intended to write
token-resolved additional data into the unhashed `context` longtext and renders the message_template
into `purpose` (the bare-key bug currently drops most of it, but the leak path exists); (3)
FakeSendDatabaseLogger persists full subject+body (PII-interpolating templates) into
digital_post_log with NO retention/purge, while audit rows keep 1825 days. Related: #74 (ECA replay
PII redaction), #91 (GDPR retention/erasure) - but the encryption-dead-code and audit-context-leak
specifics are not captured by those.

### External edges (asymmetric protection)
CPR/SF1520 has the cert-absent demo auto-detect (CprLookupAction.php:58-64) and a sanitized catch
(generic Danish message). CVR/SF1530 has NEITHER: with empty certs CvrLookupAction makes a real SOAP
call to exttest.serviceplatformen.dk and its catch routes raw exception text through
AabenFormsActionBase::handleError into the workflow step description/error fields, which
WorkflowExecutionCollector::toArray() serializes into the anonymous-accessible JSON response.
Reproduced live against anonymous company_verification: response leaked
`cURL error 56: OpenSSL... decryption failed or bad record mac ... https://exttest.serviceplatformen.dk/service/CVR/CVROnline/1`.
SendDigitalPostAction surfaces `$e->getMessage()` into the citizen-facing step on validation errors
(same leak class, smaller surface). Also: CvrLookupAction/CprLookupAction never call AuditLogger
(only ParentCprVerifier and the DP emitter do), and a generic AuditLogAction step records "CVR
resolved" even when the lookup failed - a misleading audit trail. The unused SF1601 envelope in
ServiceplatformenClient is dead code (DP uses the separate module). Related: #72 (circuit breaker),
#76 (certs/enrollment), #77 (real SF1601).

### Template divergence
See Section 5. BPMN templates, active YAML flows, and the wizard generator are three non-agreeing
representations; the product UI presents BPMN/wizard as the source of truth while the YAML flows are
what run, and the parent/caseworker flows have no BPMN at all. Related: #90 (persistent execution
history + Recipe templates).
