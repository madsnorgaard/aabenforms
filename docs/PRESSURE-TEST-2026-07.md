# AabenForms pressure-test - July 2026

An adversarial correctness/security/GDPR pressure-test of the casework engine
and its Danish gov-tech integrations, followed by Tier-1 remediation. Four
independent audits covered `aabenforms_case`, `aabenforms_kombit`,
`aabenforms_mitid`, `aabenforms_digital_post`, `aabenforms_core`,
`aabenforms_tenant`, plus the shipped ECA flows. Findings below were traced to
the exact code path (CONFIRMED) or need a runtime check (PLAUSIBLE); the two
case-module audits independently corroborated the FristClock and fail-open-flow
findings.

## What the audits established up front

- **The MitID JWT-signature hole is fixed.** `MitIdTokenVerifier::verify()` does
  real RS256/JWKS verification (rejects `alg != RS256`, validates iss/aud/exp),
  called before any claim is trusted. PKCE S256, `state`, and `nonce` replay
  protection are present.
- **The lawful lifecycle is real** (`allowedTransitions()`, FVL §19/§25) - but it
  lived **only inside the ECA actions**, so any other save path was unguarded,
  and several borger-facing flows were fail-open.

## Findings and disposition

| # | Sev | Module | Finding | Status |
|---|-----|--------|---------|--------|
| 1 | HIGH | case | Lifecycle enforced only in ECA actions; admin edit form / JSON:API / VBO could jump status to any state and a default form-save silently overwrote the audited revision | **FIXED** |
| 2 | HIGH | case | FVL §19: an adverse decision passed when partshøring was never opened (default `ikke_paakraevet`) - silent skip | **FIXED** |
| 3 | HIGH | case | FristClock counted Danish public holidays as working days and computed weekday in UTC not Europe/Copenhagen | **FIXED** |
| 4 | HIGH-GDPR | core | `CprAccess::protect()` failed open - returned plaintext CPR for storage on encryption failure | **FIXED** |
| 5 | HIGH | case+dp | `friplads_flow` auto-issued a favourable decision + Digital Post on a self-reported, unverified CPR (no MitID gate) | **FIXED** |
| 6 | HIGH | dp | Empty/wiped `test_mode` aliased to `fake_db` - live letters became fake successes | **FIXED** |
| 7 | MED-HIGH | workflows | 3 of 5 `config/install` flows shipped the dead `eca_scalar` contract; fresh installs regressed to dead gates | **FIXED** (3/5; 2 orphans flagged) |
| 8 | MED | case | `OpenCaseAction` had no idempotency -> a double-fired insert created duplicate cases; founding revision unattributed | **FIXED** |
| 9 | MED | case | `SetPartshoeringAction` had no lifecycle guard - could mutate a decided/closed case | **FIXED** |
| C1 | **CRIT** | dp+case | A failed/skipped Digital Post send never blocked SF2900 close; klagefrist started at decide-time not confirmed dispatch | **TRACKED** |
| C2 | **CRIT** | kombit | SF2900 returns a synchronous business receipt the real Fordelingskomponent delivers asynchronously | **TRACKED** |
| K1 | **CRIT** | case | `klage_flow` let anyone appeal any case by id - no identity/ownership check (enumeration, audit poisoning, denial-of-decision) | **TRACKED** |
| S1 | HIGH | mitid | Session fixation: `/mitid/login` accepts a caller-chosen `workflow_id`; phishing -> victim PII at `_access:TRUE` endpoints; capability also leaked in redirect URL | **TRACKED** |
| S2 | HIGH | mitid | eIDAS assurance level (`acr`) requested but never enforced; `getAssuranceLevel()` fail-open default `substantial` | **TRACKED** |
| H2 | HIGH | dp | Afslag letter body promises an attached klagevejledning that is never attached (FVL §25 in the received artifact) | **TRACKED** |
| H3 | HIGH | dp | No fritaget/fysisk-post path; DTO cannot carry a postal address; `Automatisk Valg` unused | **TRACKED** |
| M1 | MED | core/dp | CPR audit identifier is bare unsalted SHA-256 (reversible over a ~10⁹ keyspace) | **TRACKED** |
| M2 | MED | dp/kombit | No idempotency keys - a re-fired flow sends duplicate letters / re-distributes; tx id only in a revision-log string | **TRACKED** |
| M3 | MED | dp | Log table stores letter body plaintext, no retention/purge; DB-exception leaks payload; `wiremock` ships raw CPR over plain HTTP to a config-controlled URL | **TRACKED** |

## Fixed this session (Tier-1 + friplads)

- **Case entity lifecycle backstop** (`AabenformsCase::preSave()`): rejects illegal
  transitions and any change to a `lukket` case, forces a new audited revision on
  every update; `status` is no longer form-configurable. Kernel-tested.
- **FristClock**: Europe/Copenhagen, skips weekends + Danish public holidays
  (Easter via Anonymous-Gregorian; store bededag correctly excluded - abolished
  2024), due normalised to end-of-day; `ekstra_lukkedage` config for kommune
  closing days. Date logic validated (8 cases).
- **§19 exemption gate** (`MakeDecisionAction`): an adverse decision requires
  partshøring `afsluttet` OR an explicit, recorded §19 stk. 2 exemption.
- **OpenCaseAction**: idempotent by `submission_ref`; founding revision attributed.
- **SetPartshoeringAction**: rejected unless status is `oplyst`/`partshoering`.
- **CprAccess::protect()**: fails hard (throws) - never stores plaintext CPR.
- **Sf1601ClientFactory**: empty/unknown `test_mode` throws (no fake fallback).
- **friplads_flow**: MitID gate (`mitid_validate` + dual gate + `deny_identity`)
  inserted before `open_case`, in both `config/sync` and the module `config/install`.
- **Dead install configs**: `caseworker_review_flow`, `parent1/2_approval_flow`
  restored from the verified `config/sync` versions.

## Deferred criticals - fix recipes

- **C1 (letter->close gating).** Gate `sf2900_distribute` (and any close) on
  `[digital_post_result:success]`; on failure route to a terminal that leaves the
  case in `afgoerelse` with a flagged step + audit `failure`. Add an outbox table
  + retry worker (advancedqueue). Compute klagefrist from confirmed afsendelse.
- **C2 (SF2900 async receipt).** Split `distribute()` into
  `submitDistribution(case, idempotencyKey): pendingTxId` + a receipt
  callback/poll that performs the close; add a `distribution_tx` base field and a
  `distribueret/afventer-kvittering` case state.
- **K1 (klage ownership binding).** Add an ownership verifier: compare the MitID
  session CPR against the case's applicant CPR (decrypted from the case's
  submission) and fail closed on mismatch; gate `klage_flow` on a `mitid_validate`
  first. Crosses module boundaries (case <- mitid session).
- **S1 (session fixation).** Generate `workflow_id` server-side, bind it to the
  authenticating browser session (HTTPOnly cookie / one-time code exchange), and
  keep it out of the redirect URL. Needs frontend coordination (the SPA currently
  supplies the id).
- **S2 (assurance).** After `verify()`, compare the token `acr` to
  `required_assurance_level` (low<substantial<high) and reject a shortfall; make
  `getAssuranceLevel()`'s unknown-acr default fail closed.
- **H2 (klagevejledning attachment).** Render the klagevejledning + afgørelse to a
  PDF `MainDocument` and attach it, or template the klagevejledning + klagefrist
  into the letter body from the case fields.
- **H3 (fritaget/fysisk post).** Default flows to `Automatisk Valg`; add postal
  address to the `Recipient`/DTO (or a CPR-lookup-derived address provider); route
  `RECIPIENT_NOT_REACHABLE` to a manual fysisk-post work item.
- **M1 (CPR hash).** Replace bare `hash('sha256', $cpr)` with
  `hash_hmac('sha256', $cpr, $siteSecret)` (env-backed secret, not stored beside
  the table) in `AuditLogger` and `Recipient::identifierHash()`.
- **M2 (idempotency).** Persist send/distribution state keyed on (case id, kind)
  before transport; treat a unique-key violation as idempotent success; store
  `distribution_tx` as a queryable field.
- **M3 (log PII).** Cron purge with configurable retention for
  `aabenforms_digital_post_log`; catch DB exceptions without interpolating the
  driver message; restrict `wiremock` mode to non-prod and hash its identifier.
- **Orphan dead flows.** `parent_dual_approval` and `initial_request_flow`
  (module `config/install`, not in deployed `config/sync`) are structurally dead
  (dead `eca_scalar` + `successors` on conditions). Redesign against the current
  contract and test end-to-end, or remove.

## Verification

- FristClock date logic validated standalone (Easter, holiday-skip, store-bededag,
  extra closing days, TZ boundary - all pass). All changed/new PHP lints clean.
- Kernel tests added: case lifecycle enforcement, OpenCase idempotency, §19 gate,
  ESDH journal action. Run under DDEV (PHP 8.4): `ddev exec vendor/bin/phpunit
  web/modules/custom/aabenforms_case web/modules/custom/aabenforms_esdh`.
- Config: after `drush cr`, confirm friplads + restored install flows validate
  (`cim --simulate` / `cex` no-diff) with no dead gates.
