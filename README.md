# AabenForms Backend

Headless Drupal 11 backend for Danish municipal workflow automation.

[![Drupal](https://img.shields.io/badge/Drupal-11-blue)](https://www.drupal.org)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-green)](LICENSE.txt)
[![CI](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml/badge.svg)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)
[![Coding Standards](https://github.com/madsnorgaard/aabenforms/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/madsnorgaard/aabenforms/actions/workflows/coding-standards.yml)

## Status

Pre-pilot POC. Live at https://api.aabenforms.dk with a Nuxt frontend at https://aabenforms.dk. MitID runs against a Keycloak mock; Serviceplatformen (CPR/CVR) and Digital Post run against test or mock endpoints, not live production integrations. For a claim-by-claim account of what works today versus what is demo or planned, see [docs/PROMISES-VS-VERIFIED.md](docs/PROMISES-VS-VERIFIED.md).

## What it is

A modular platform for Danish kommuner to automate citizen-facing workflows and connect to government services (MitID/NemLog-in, Serviceplatformen, Digital Post). Workflows are built in a visual editor and run as event-driven (ECA) flows.

Real today:

- ECA workflow engine with a visual Workflow Modeler (`drupal/modeler`) and execution replay with token inspection
- 18 municipal ECA flows (building and parking permits, marriage and association booking, citizen service, FOI, address and phone change, company verification, dual-parent approval, HR onboarding, mileage, MED election, caseworker review)
- MitID OIDC sign-in against a Keycloak mock IdP; CPR (SF1520) and CVR (SF1530) lookup clients
- Custom Danish webform elements with server-side validation: CPR (format and modulus-11), CVR, DAWA address autocomplete
- Field-level CPR encryption and audit logging (in `aabenforms_core`)
- Digital Post (SF1601) in `fake_db` and `wiremock` test modes

Demo or mock, not production:

- Payment, SMS, GIS/zoning, payroll and calendar actions are demo mocks
- Digital Post has no live MeMo/SOAP transport yet; MitID has no live registration; Serviceplatformen needs client certificates before CPR/CVR run live

## Architecture

```
Nuxt 3 frontend  ->  Drupal 11 backend (JSON:API, ECA, Webform)  ->  Danish gov services
(aabenforms.dk)      (api.aabenforms.dk)                              MitID, CPR/CVR (Serviceplatformen), Digital Post
```

Deployment is orchestrated by the `contabo-infrastructure` repo; a push to `main` rebuilds the Docker image on VPS2.

## Quick start

```bash
git clone https://github.com/madsnorgaard/aabenforms.git backend
cd backend
ddev start
ddev launch        # admin UI, login admin / admin
```

Local URLs: site at https://aabenforms.ddev.site, JSON:API at `/jsonapi`, mail at `:8026`. `ddev start` also boots Keycloak (mock MitID) and WireMock (mock Serviceplatformen) for zero-config local development. The mock realm, test users, and the Keycloak re-import gotcha are documented in [docs/DDEV_MOCK_SERVICES_GUIDE.md](docs/DDEV_MOCK_SERVICES_GUIDE.md).

## Modules

| Module | Status | What it does |
| --- | --- | --- |
| `aabenforms_core` | Active | Base services: Serviceplatformen client (SF1520/SF1530), AES-256 field encryption, audit logging, tenant resolver, workflow execution collector |
| `aabenforms_workflows` | Active | ECA action plugins, the approval system, and the template wizard. Some actions (payment, SMS, GIS, payroll, calendar) are demo mocks |
| `aabenforms_mitid` | Active | MitID OIDC sign-in, session management, CPR claim extraction |
| `aabenforms_webform` | Active | Custom webform elements: CPR, CVR, DAWA address |
| `aabenforms_tenant` | Active | Domain-based multi-tenancy (logical, single database) |
| `aabenforms_digital_post` (+ `_eca`) | Partial | SF1601 Digital Post in `fake_db`/`wiremock`; real MeMo and SOAP transport are planned (issue #77) |
| `aabenforms_nemlogin`, `aabenforms_sbsys`, `aabenforms_get_organized` | Planned | NemLog-in Erhverv and ESDH/case-system integrations (issues #79, #84-#86) |

Encryption and GDPR audit logging are built into `aabenforms_core`. CPR numbers are encrypted at rest (AES-256) on submission and decrypted only at the point of use (registry lookup, Digital Post recipient, audit hashing). The encryption key is read from the `AABENFORMS_CPR_KEY` environment variable (base64 of 32 random bytes, generated with `openssl rand -base64 32`); the key and encryption profile are provisioned automatically by a database update and never stored in git. If the variable is unset, submissions still succeed but CPR is stored unencrypted and a warning is logged. A retention and right-to-erasure subsystem does not exist yet and is tracked in issue #91.

## Recent progress

A security pass in June 2026 fixed issues found by a local pressure test, where some steps reported success while the underlying control did nothing:

- #68 parent-CPR consent gate: fail-closed, real caseworker CPR fields, submission-scoped session, re-verified at submit
- #69 audit integrity: token resolution so CPR/CVR lookups actually run; MitID validation fails closed; honest step statuses
- #70 execution-replay PII: least-privilege plus a cron self-heal so an armed replay cannot record citizen CPR to a shared store
- #71 [docs/PROMISES-VS-VERIFIED.md](docs/PROMISES-VS-VERIFIED.md): the claim-vs-verified table

## Roadmap

The backlog is in [GitHub issues](https://github.com/madsnorgaard/aabenforms/issues), grouped by label:

- Security ([`security`](https://github.com/madsnorgaard/aabenforms/labels/security)): circuit breaker #72, Digital Post idempotency #73, replay redaction #74, permission registration #75, consent-gate test #54
- Productionise integrations ([`integration`](https://github.com/madsnorgaard/aabenforms/labels/integration)): Serviceplatformen certs #76, live Digital Post #77, Beskedfordeler #78, MitID production #79, DAWA + Datafordeler #80
- Replace mocks ([`mocks`](https://github.com/madsnorgaard/aabenforms/labels/mocks)): payment #81, SMS #82, GIS/BBR #83
- Case-system / ESDH handoff ([`esdh`](https://github.com/madsnorgaard/aabenforms/labels/esdh)): handoff module #84, SF1470 journaling #85, ESDH adapters #86
- Advanced flows ([`flows`](https://github.com/madsnorgaard/aabenforms/labels/flows)): SLA and escalation #87, task inbox and gateways #88, appeals and letters #89, persistent history and templates #90
- Compliance ([`compliance`](https://github.com/madsnorgaard/aabenforms/labels/compliance)): GDPR retention/erasure #91, WCAG and NIS2/DPA #92

## Development

```bash
ddev drush cr                 # clear cache
ddev drush config:export -y   # export config
ddev drush config:import -y   # import config
ddev exec phpunit -c web/core web/modules/custom/aabenforms_workflows/tests/src/Unit
```

Workflow admin is at `/admin/config/workflow/eca`; the template wizard is at `/admin/aabenforms/workflow-templates`. See [docs/WORKFLOW_GUIDE.md](docs/WORKFLOW_GUIDE.md) for building flows, [docs/DATA-FLOW.md](docs/DATA-FLOW.md) for how data moves through every flow and where the PII sinks and trust boundaries are, and [docs/](docs/) for the template reference and admin guides.

## Related

- Frontend: [aabenforms-frontend](https://github.com/madsnorgaard/aabenforms-frontend) (Nuxt 3)
- Deployment: [contabo-infrastructure](https://github.com/madsnorgaard/contabo-infrastructure)

## License

GPL-2.0-or-later. See [LICENSE.txt](LICENSE.txt).
