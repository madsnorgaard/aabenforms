# ÅbenForms ESDH

Journalises AabenForms cases into a municipality's **ESDH** (electronic case and
document management system - the *system of record*) via a pluggable connector.

## SF1470 index vs ESDH record - the distinction that matters

- **SF1470 Sags- og Dokumentindeks** (`aabenforms_case_journal`) is the
  *fælleskommunale metadata index*. You push case/document metadata so SAPA and
  Borgerblikket/Mit Overblik can show the citizen a cross-system overview. It is
  **not** the ESDH and it does not store the sag.
- **The ESDH** (SBSYS, KMD WorkZone, Formpipe Acadre, GetOrganized, KMD Nova,
  cBrain F2) is the kommune's actual record of the case and its documents.

A complete flow does **both**: journalise into the ESDH with
`aabenforms_case_esdh_journal` (this module), and register in SF1470 with
`aabenforms_case_journal`.

## Connectors

Selected in `/admin/config/aabenforms/esdh` (`aabenforms_esdh.settings.active_connector`).

| id | ESDH | Transport (verified surface) | Auth |
|----|------|------------------------------|------|
| `demo` | none | synthesises `ESDH-DEMO-…`, no external effect | none (default) |
| `getorganized` | GetOrganized (KBH, Aarhus, ATP) | REST `/_goapi/` (`Cases/`, `Cases/FindByCaseProperties`, `Documents/AddToDocumentLibrary`, `Documents/Finalize/ByDocumentId`) | username/password |
| `sbsys` | SBSYS (~45 kommuner) | SBSIP REST (`api/token`, `api/v10/sag/template`, `api/sag/{id}/part`, `api/sag/{id}/dokumenter`, `api/journalarknote/create`) | OAuth2 |
| `workzone` | KMD WorkZone | OData (`/odata/File` = case, `/odata/Record` = document, `/odata/Contacts`) | Azure AD OAuth2 |
| `acadre` | Formpipe Acadre | PWS (SOAP) + PWI (REST); Acadre ≥ v23 SP1 CU9 | client cert (.pfx) + service user |

Only `demo` runs without configuration. Every live connector **fails hard**
(throws `EsdhException`) until its transport is configured - it never silently
degrades to demo. `getorganized` and `sbsys` are the recommended first live
targets (best-documented / widest reach).

Add a new ESDH by dropping a class in `Plugin/AabenformsEsdh/` with the
`#[EsdhConnector(id: …)]` attribute implementing `EsdhConnectorInterface`.

## Prerequisites for going live (per kommune)

- **OCES3 FOCES3 systemcertifikat** for this IT-system, ordered in the kommune's
  MitID Erhverv (per environment; ~3-year validity - keep a renewal calendar).
- A **serviceaftale / tilslutning** approved by the kommune (data-controller
  consent), and for KOMBIT index writes a passed **compliancetest**.
- The KLE classification (`kle_emne`) referenced by UUID (SF1510) - the case
  carries a `kle_emne` field for this.
- Per-connector transport config (base URLs, OAuth2/cert credentials) sourced
  from **env vars**, never committed.

## Design contracts (lessons baked in from the pressure-test)

- **No synchronous receipt where the vendor is async.** `EsdhResult` supports a
  `pending` status; map queued acknowledgements to it and reconcile later rather
  than blocking a request on a receipt (the SF2900 Fordelingskomponent mistake).
- **Never close a case on a transient failure.** `EsdhResult::$transient`
  distinguishes retry-able (timeout/5xx) from permanent (validation/auth); the
  action records a `failed` step so a flow can gate its close.
- **Idempotent.** The queryable `esdh_ref` base field (added to `aabenforms_case`
  via `hook_entity_base_field_info`) means a re-fired flow does not re-journalise.
- **No raw CPR in the payload from the case.** The case holds only
  `submission_ref`; a live connector resolves the ESDH party server-side from the
  verified session, not from the case or a self-reported form field.

## Not yet built (tracked)

Document assembly (rendering the submission/decision to a PDF `EsdhDocument`),
per-connector queue workers (OS2Forms-style advancedqueue per connector), and
the live transports themselves.
