# Promises vs Verified reality

A deliberate, honest record of what AabenForms **claims** versus what is **verified working**, produced from a multi-agent local security pressure test (every finding reproduced by an independent adversarial pass). Kept in the repo so marketing copy and demos stay truthful in front of a municipal evaluator.

Status key: **WORKS** (verified) · **PARTIAL** · **BROKEN** (verified defect) · **MISSING** · **UNVERIFIED** · **FABRICATED**.

| Claim | Status | Notes / fix |
|---|---|---|
| Visual workflow modeler, ready-made flows | **WORKS** | 18 ECA flows render in the React-Flow Workflow Modeler with execution replay; zero console errors. (Marketing "7"/"13 BPMN templates" count and "BPMN 2.0 designer" wording are inaccurate - it is the Modeler API / `workflow_modeler`.) |
| Parent-CPR consent gate ("only the right parent can approve", #54) | **BROKEN → fixed** | Was inert (no parent CPR collected → always `missing_expected_cpr`) and the controller failed **open**; session flag not submission-scoped. Closed in the consent-gate-hardening PR (fail-closed, real CPR fields, scoped session, submit-time re-verify). |
| MitID authentication | **BROKEN → fixed** | `MitIdValidateAction` reported "verified" with no session ("demo mode"); the result token was never gated. Now fails **closed** by default behind `allow_mitid_demo_mode`. |
| CPR/CVR lookup via Serviceplatformen (SF1520/SF1530) + audit "resolved against registry" | **BROKEN → fixed** | `getTokenValue()` looked up the literal `[token]` string, so the lookups never called Serviceplatformen yet logged success. Token resolution fixed; lookup steps now record `skipped`/`failed` honestly. |
| Digital Post (SF1601) | **PARTIAL** | Sends, but recipient CPR was taken from the request body and there is no idempotency (duplicate letters). Recipient-from-verified-session + idempotency are a tracked follow-up. |
| Execution replay / "full auditability" | **WORKS, hardened** | Real and useful; but while armed it recorded citizen CPR/IP into a cross-user store. Least-privilege + cron self-heal landed; deep redaction needs an upstream `eca` patch. |
| Role-based access control | **PARTIAL → improving** | `parent_request_form` was anonymous-creatable (now case-worker only); `administer workflows` is referenced by routes but defined nowhere (tracked). |
| "Field-level CPR encryption" / "deletion after case closure" | **UNVERIFIED / MISSING** | No retention/erasure subsystem found; encryption usage unverified. Do not claim until built. |
| WCAG 2.1 AA, "88% / 156 tests" | **UNVERIFIED** | No accessibility audit run; coverage figure unmeasured. Run axe-core/Lighthouse + measure before claiming. |
| Resilience to a Serviceplatformen outage | **BROKEN** | An SF1520 outage blocks a request ~36s (retries × timeout) with no circuit breaker. Tracked follow-up. |
| "Production Ready 2.0.0" | **NOT YET** | Pre-pilot. Several critical security fixes were required first; treat as in active hardening. |
| Municipality deployments (Vejle/Randers/Aalborg), satisfaction %, "450,000 forms", ROI (867%, €127k …) | **FABRICATED** | No such deployments. Removed/marked illustrative. Never present as real to a prospect. |
| Licence | **GPL-2.0-or-later** | Single source of truth. (Earlier EUPL-1.2 references were inconsistent and have been corrected.) |

Security hardening is being delivered as a staged sweep of PRs; this table is updated as each lands.
