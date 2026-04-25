# ÅbenForms Backend

**Headless Drupal 11 backend for Danish municipal workflow automation**

[![Drupal](https://img.shields.io/badge/Drupal-11.3.2-blue)](https://www.drupal.org)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-green)](LICENSE)

[![CI](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml/badge.svg)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)
[![Coding Standards](https://github.com/madsnorgaard/aabenforms/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/madsnorgaard/aabenforms/actions/workflows/coding-standards.yml)
[![Coverage](https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/madsnorgaard/aabenforms/main/.github/badges/coverage.json)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)
[![Tests](https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/madsnorgaard/aabenforms/main/.github/badges/tests.json)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)

## Overview

ÅbenForms is a modular platform for Danish municipalities to automate citizen-facing workflows and integrate with government services (MitID, Serviceplatformen, case management systems).

**Status: POC / demo**. Live at https://api.aabenforms.dk with a Nuxt
frontend at https://aabenforms.dk. Real MitID test-gateway
registration with Digitaliseringsstyrelsen is a future phase; today
the backend uses a Keycloak mock for local development and the
frontend's MitID button is hidden in prod.

This repository contains the **Drupal 11 backend** that provides:
- ECA workflow engine (event-driven automation)
- Visual BPMN 2.0 workflow builder with Danish municipal task palette
- 13 production-ready BPMN templates (parking permit, marriage booking, building permit, citizen service application, FOI, HR onboarding, mileage expense, MED election, address change, phone declaration, association booking, contact form, company verification)
- ~15 ECA action plugins (Digital Post, payment, SMS, PDF, calendar, GIS, MitID, CPR/CVR lookup, audit log, log, parent approval emails, etc.)
- Modular Danish government integrations: `aabenforms_digital_post` SF1601 (shipped in `fake_db` mode, real SOAP transport coming in Session 2B), MitID OIDC, planned NemLogin OIDC core
- WireMock + Keycloak mock services bundled in DDEV for zero-config local development
- Dynamic webforms with JSON:API exposure
- Multi-tenant architecture via Domain module
- GDPR-compliant CPR encryption and audit logging
- Shared admin design tokens (`aabenforms_core/admin` library) so feature modules style consistently from one source of CSS variables

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                  ÅbenForms Platform                     │
└─────────────────────────────────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
┌───────▼────────┐  ┌──────▼──────┐  ┌───────▼────────┐
│  Nuxt 3 UI     │  │  Drupal 11  │  │   VPS2 prod    │
│  (Frontend)    │◄─┤  (Backend)  │  │  (Deployment)  │
│                │  │             │  │                │
│  - Multi-tenant│  │  - JSON:API │  │  - Docker      │
│  - Form render │  │  - ECA      │  │  - Traefik TLS │
│  - Workflows   │  │  - Webform  │  │  - MariaDB 11  │
└────────────────┘  └─────────────┘  └────────────────┘
                           │
                           │ Serviceplatformen
                           ▼
               ┌───────────────────────┐
               │  Danish Gov Services  │
               │  - MitID (auth)       │
               │  - CPR (person data)  │
               │  - CVR (company data) │
               │  - Digital Post       │
               └───────────────────────┘
```

## Quick Start

### Prerequisites
- DDEV installed
- Docker running
- Git configured

### Installation

```bash
# Clone repository
git clone https://github.com/madsnorgaard/aabenforms.git backend
cd backend

# Start DDEV
ddev start

# Install Drupal (already done if cloning)
# ddev drush site:install aabenforms --account-pass=admin -y

# Access admin UI
ddev launch
# Login: admin / admin
```

### Local URLs
- **Frontend**: https://aabenforms.ddev.site
- **JSON:API**: https://aabenforms.ddev.site/jsonapi
- **Mailpit**: https://aabenforms.ddev.site:8026

### Local authentication (Keycloak mock realm)

`ddev start` also boots a Keycloak container that imports a mock realm from
`.ddev/mocks/keycloak/realms/danish-gov-test.json`. This stands in for MitID
during local development.

- Realm: `danish-gov-test` at http://localhost:8080
- OIDC client: `aabenforms-backend` (redirect URIs cover localhost:3000 and
  the DDEV hostnames)
- 10 test users (password `test1234`): freja.nielsen, mikkel.jensen,
  sofie.hansen, lars.andersen, emma.pedersen, karen.christensen,
  protected.person, morten.rasmussen, ida.mortensen, peter.larsen
- Client scopes: `ssn` (with a CPR user-attribute mapper emitting the `ssn`
  claim, matching the real MitID contract) plus the re-declared built-ins
  `profile`, `email`, `roles`, `web-origins`, `acr`, `role_list`.

**Gotcha worth knowing about.** When a realm JSON declares top-level
`clientScopes`, Keycloak's import replaces the auto-created built-in scope
set. If the JSON only declares `ssn`, `profile`/`email`/`roles`/etc. vanish
from the realm and OIDC discovery shrinks to three scopes. Every built-in
that clients depend on must be redeclared alongside the custom scope. This
cost a day of debugging on Apr 23, 2026.

`MitIdOidcClient::getAuthorizationUrl()` defaults scope to `'openid ssn'`
(`web/modules/custom/aabenforms_mitid/src/Service/MitIdOidcClient.php:114`).
Before the realm was updated, every login attempt in local dev got
`invalid_scope` back from Keycloak.

**Re-importing the realm after edits.** Keycloak runs with
`start-dev --import-realm`, which imports a realm once per H2 data store.
A plain `ddev restart` keeps the existing realm in place and silently
ignores your changes. To force re-import, remove the container and let
DDEV recreate it:

```bash
docker rm -f ddev-aabenforms-keycloak
ddev start
```

Then verify the scopes are live:

```bash
curl -s http://localhost:8080/realms/danish-gov-test/.well-known/openid-configuration \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['scopes_supported'])"
```

## Custom Modules

### Phase 1: Core Platform  Complete
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_core` |  Active | Base services, utilities, Serviceplatformen client, encryption |
| `aabenforms_tenant` |  Active | Multi-tenancy via Domain module |
| `aabenforms_webform` |  Active | Custom form elements (CPR, CVR, DAWA address fields) |

### Phase 2: Security & Authentication  Complete
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_mitid` |  Active | MitID OIDC integration, session management, CPR extraction |
| `aabenforms_gdpr` |  Partial | Field encryption (), audit logs (), retention policies (planned) |

### Phase 3: Complete Workflow System  Complete
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_workflows` |  Complete | **Full workflow automation platform:** |
| | | • 13 BPMN 2.0 templates (see "BPMN Workflow Templates" below) |
| | | • Visual workflow wizard (6-step template instantiation, modernized step bar) |
| | | • Approval system with secure token-based access |
| | | • 3 separate workflows for parallel parent approvals |
| | | • Email notifications (SendApprovalEmailAction) |
| | | • Custom ECA action plugins (MitID validate, CPR/CVR lookup, audit log, payment, SMS, PDF, calendar, GIS, zoning, reminders, `aabenforms_log` shim) |
| | | • `hook_storage_transform_import` preserves wizard-created configs across `drush cim` |
| | | • Municipal admin documentation under `docs/` |

### Phase 4: Modular Danish Service Integrations (Current)
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_digital_post` |  Session 1 shipped | SF1601 Digital Post core. Plug-and-play on bare Drupal 11 with `drupal:key` + `aabenforms_core` only. Test modes `fake_db`/`wiremock`/`live_test`/`live`. Real SOAP transport in Session 2B. |
| `aabenforms_digital_post_eca` |  Session 2A shipped | Submodule. ECA action plugin `aabenforms_digital_post_send`, ready for any BPMN template via `<aabenforms:ecaAction>`. |
| `aabenforms_digital_post_webform` |  Session 3 | Submodule. Webform handler, keeps the core module webform-free. |
| `aabenforms_digital_post_beskedfordeler` |  Session 3 | Submodule. Optional delivery-status receipts. |
| `aabenforms_digital_post_os2web_key` |  Session 3 | Submodule. Bridge for sites already using `os2web_key` certificates. |
| `aabenforms_nemlogin` |  Session 2C | Plug-and-play OIDC core (PKCE S256, ItkOidcClient, ClaimStore). |
| `aabenforms_nemlogin_keycloak` |  Session 2C | Submodule. Keycloak preset for local dev. |
| `aabenforms_mitid` |  deprecation track | Shim over `aabenforms_nemlogin` planned in Session 2C; full removal in Session 3. |
| `aabenforms_cpr` |  Partial | SF1520 person lookup (action plugin, production service planned) |
| `aabenforms_cvr` |  Partial | SF1530 company lookup (action plugin, production service planned) |
| `aabenforms_dawa` |  Partial | DAWA address autocomplete (webform element, full API integration planned) |
| `aabenforms_sbsys` |  Planned | SBSYS case management integration |
| `aabenforms_get_organized` |  Planned | GetOrganized ESDH document archiving |

**Legend**:  Shipped |  In Progress |  Planned

The Digital Post + NemLogin rewrite plan is the explicit modular alternative to the OS2/Bellcom dependency maze. Goal: each integration installs cleanly on any modern Drupal 11 with at most one mainstream contrib, no `os2web_*` chain.

### Development Progress

**Shipped:**
-  Dual parent approval system with parallel workflows
-  Secure token-based approval pages (HMAC-SHA256, 7-day expiry)
-  Visual workflow template wizard (no YAML required, modernized 6-step indicator)
-  13 BPMN templates (see "BPMN Workflow Templates" below)
-  ~15 ECA action plugins including `aabenforms_digital_post_send` and `aabenforms_log` shim
-  `aabenforms_digital_post` core + ECA bridge (Session 1 + 2A): SF1601 in `fake_db` on prod, write-through to `{aabenforms_digital_post_log}`
-  `aabenforms_core/admin` design tokens: one CSS-variable file feeding all admin styles
-  `hook_storage_transform_import` preserves wizard-created configs across `drush cim`
-  Keycloak mock realm with `ssn` client scope for local MitID development
-  GDPR-oriented data masking for separated parents
-  Automated test coverage via CI (see badges above for current numbers)

**Phase 4 Next** (In Progress):
-  BpmnTemplateManagerTest.php (5 tests)
-  WorkflowsModuleTest.php replacement
-  End-to-end browser tests (FunctionalJavascript)
-  Performance and security test suites
-  Achieve 60%+ test coverage
-  Production Serviceplatformen integration (replace mocks)

## Workflow System

ÅbenForms provides a powerful visual workflow automation system for Danish municipal processes:

### Key Features
- **Pre-built Templates**: 7 BPMN templates for common use cases (building permits, parking permits, marriage booking, address changes, FOI requests, company verification, contact form)
- **Visual Editor**: Create and modify workflows without programming
- **Danish Integrations**: MitID authentication, CPR/CVR lookup, Digital Post notifications
- **GDPR Compliant**: Automatic audit logging, encrypted CPR numbers, data retention policies

### Quick Start
1. Access workflow admin: `/admin/config/workflow/eca`
2. Choose a template (Building Permit, Contact Form, etc.)
3. Configure with the wizard (8 simple steps)
4. Test with sample data
5. Activate for production

### Documentation
- **[Municipal Admin Guide](docs/MUNICIPAL_ADMIN_GUIDE.md)** - Complete guide for non-technical administrators
- **[Workflow Creation Tutorial](docs/tutorials/CREATE_APPROVAL_WORKFLOW.md)** - Step-by-step tutorial with examples
- **[Approval Process Guide](docs/APPROVAL_PROCESS_GUIDE.md)** - End-to-end approval flow documentation
- **[Workflow Templates Reference](docs/WORKFLOW_TEMPLATES.md)** - Detailed template specifications
- **[Quick Reference Card](docs/QUICK_REFERENCE.md)** - One-page cheat sheet
- **[Video Tutorial Script](docs/VIDEO_SCRIPT.md)** - 5-minute video guide

### Testing Locally
```bash
# Create test workflow
ddev drush aabenforms:create-test-workflow

# Validate BPMN template
ddev drush aabenforms:validate-template building_permit

# Test approval flow
ddev drush aabenforms:test-approval --workflow=daycare_enrollment
```

## Development

### Common Commands
```bash
# Clear cache
ddev drush cr

# Export configuration
ddev drush config:export -y

# Import configuration
ddev drush config:import -y

# Update database
ddev drush updatedb -y

# Generate one-time login
ddev drush user:login
```

### Adding Modules
```bash
# Install via Composer
ddev composer require drupal/<module_name>

# Enable module
ddev drush pm:enable <module_name>

# Export config
ddev drush config:export -y
```

## BPMN Workflow Templates

ÅbenForms ships 13 BPMN 2.0 workflow templates under
`web/modules/custom/aabenforms_workflows/workflows/`:

| Template | Use Case | Notes |
|----------|----------|-------|
| `building_permit` | Building permit applications | MitID, CPR lookup, audit logging |
| `parking_permit` | Parking permit applications | MitID, payment, PDF, SMS |
| `marriage_booking` | Marriage ceremony booking | Dual MitID, calendar, payment, reminders |
| `address_change` | Address change notifications | DAWA, parallel system updates |
| `company_verification` | Business registration verification | CVR + CPR cross-reference |
| `foi_request` | Freedom of Information | 7-day deadline tracking |
| `contact_form` | Generic citizen inquiry | Email + auto-response |
| `citizen_service_application` | Service benefit application | MitID, CPR, caseworker review, **real Digital Post via `aabenforms_digital_post_send`** on both Approved and Rejected branches |
| `hr_onboarding` | HR-initiated employee onboarding | Three-party cascade (HR → manager → IT) |
| `mileage_expense` | Employee reimbursement | Manager approval, payroll forwarding |
| `phone_declaration` | Private phone use declaration | Manager approval, archive |
| `med_election_nomination` | MED committee election nomination | MitID, audit log (voting phase deferred) |
| `association_booking` | Association facility booking / grant | MitID Erhverv, CVR lookup, payment |

### Template Browser

Browse, preview, and instantiate templates via admin UI:
- Navigate to: **Workflow Templates** (`/admin/aabenforms/workflow-templates`)
- Active Workflows section appears on top once you have instances
- Click "Use This Template" to launch the 6-step wizard
- Preview thumbnails render the BPMN diagram inline (BPMN.iO viewer)
- Legacy import/export form: `/admin/config/workflow/aabenforms/templates`

For detailed workflow development guide, see [docs/WORKFLOW_GUIDE.md](docs/WORKFLOW_GUIDE.md).

## Documentation

For detailed information, see:
- **[docs/WORKFLOW_GUIDE.md](docs/WORKFLOW_GUIDE.md)** - BPMN workflow development guide
- **[docs/TESTING_GUIDE.md](docs/TESTING_GUIDE.md)** - Testing guide
- **Deployment**: orchestrated by
  [contabo-infrastructure](https://github.com/madsnorgaard/contabo-infrastructure)
  `.github/workflows/deploy.yml`. Push to `main` triggers a rebuild of the
  Docker image on VPS2. See that repo's `docs/STATUS-REPORT.md` for the
  current prod state.

## Technology Stack

| Component | Version | Purpose |
|-----------|---------|---------|
| Drupal Core | 11.3.2 | CMS |
| PHP | 8.4 | Runtime |
| MariaDB | 10.11 | Database |
| ECA | 3.0.10 | Workflow engine |
| BPMN.iO | 3.0.4 | Visual workflow modeller |
| Webform | 6.3.0-beta7 | Forms |
| Domain | 2.0.0-rc1 | Multi-tenancy |
| Gin | 3.0.0 | Admin theme |

## Security

**GDPR Compliance**: This platform handles sensitive data (CPR numbers). Always:
1. Enable field-level encryption (`aabenforms_gdpr`)
2. Log all CPR access (automatic via `aabenforms_cpr`)
3. Obtain explicit consent before collection
4. Implement data retention policies
5. Support right to erasure

## Related Projects

- **Frontend**: [aabenforms-frontend](https://github.com/madsnorgaard/aabenforms-frontend) - Nuxt 3 SSR at https://aabenforms.dk
- **Deployment orchestrator**: [contabo-infrastructure](https://github.com/madsnorgaard/contabo-infrastructure) - shared deploy workflow for this project and others on VPS2

## License

GPL-2.0 - See [LICENSE](LICENSE)

## Contributing

Issues and pull requests welcome at:
https://github.com/madsnorgaard/aabenforms/issues

