# ÅbenForms Backend

**Headless Drupal 11 backend for Danish municipal workflow automation**

[![Drupal](https://img.shields.io/badge/Drupal-11.3.2-blue)](https://www.drupal.org)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-green)](LICENSE)

[![CI](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml/badge.svg)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)
[![Coding Standards](https://github.com/madsnorgaard/aabenforms/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/madsnorgaard/aabenforms/actions/workflows/coding-standards.yml)
[![Coverage](https://img.shields.io/badge/coverage-45%25-yellow)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)
[![Tests](https://img.shields.io/badge/tests-156%20passing-brightgreen)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)

## Overview

ÅbenForms is a modern, modular platform for Danish municipalities to automate citizen-facing workflows and integrate with government services (MitID, Serviceplatformen, case management systems).

This repository contains the **Drupal 11 backend** that provides:
- ECA workflow engine (event-driven automation)
- BPMN 2.0 workflow templates (5 ready-to-use templates)
- Dynamic webforms with JSON:API exposure
- Multi-tenant architecture via Domain module
- GDPR-compliant CPR encryption
- Danish government service integrations (MitID, SF1520, SF1530, SF1601)

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                  ÅbenForms Platform                     │
└─────────────────────────────────────────────────────────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
┌───────▼────────┐  ┌──────▼──────┐  ┌───────▼────────┐
│  Nuxt 3 UI     │  │  Drupal 11  │  │  Platform.sh   │
│  (Frontend)    │◄─┤  (Backend)  │  │  (Deployment)  │
│                │  │             │  │                │
│  - Multi-tenant│  │  - JSON:API │  │  - MariaDB     │
│  - Form render │  │  - ECA      │  │  - Redis       │
│  - Workflows   │  │  - Webform  │  │  - Solr        │
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

## Custom Modules

### Phase 1: Core Platform ✅ Complete
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_core` | ✅ Active | Base services, utilities, Serviceplatformen client, encryption |
| `aabenforms_tenant` | ✅ Active | Multi-tenancy via Domain module |
| `aabenforms_webform` | ✅ Active | Custom form elements (CPR, CVR, DAWA address fields) |

### Phase 2: Security & Authentication ✅ Complete
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_mitid` | ✅ Active | MitID OIDC integration, session management, CPR extraction |
| `aabenforms_gdpr` | 🔄 Partial | Field encryption (✓), audit logs (✓), retention policies (planned) |

### Phase 3: Complete Workflow System ✅ Complete
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_workflows` | ✅ Complete | **Full workflow automation platform:** |
| | | • 5 BPMN 2.0 templates (building permit, contact, company verification, address change, FOI) |
| | | • Visual workflow wizard (8-step template instantiation) |
| | | • Approval system with secure token-based access |
| | | • 3 separate workflows for parallel parent approvals |
| | | • Email notifications (SendApprovalEmailAction) |
| | | • 4 custom ECA actions (MitID validate, CPR lookup, CVR lookup, audit log) |
| | | • Complete documentation (4,166+ lines for municipalities) |

### Phase 4: Danish Service Integrations (Current)
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_cpr` | 🔄 Partial | SF1520 person lookup (action plugin ✓, production service planned) |
| `aabenforms_cvr` | 🔄 Partial | SF1530 company lookup (action plugin ✓, production service planned) |
| `aabenforms_dawa` | 🔄 Partial | DAWA address autocomplete (webform element ✓, full API integration planned) |
| `aabenforms_digital_post` | 📋 Planned | SF1601 Digital Post notifications |
| `aabenforms_sbsys` | 📋 Planned | SBSYS case management integration |
| `aabenforms_get_organized` | 📋 Planned | GetOrganized ESDH document archiving |

**Legend**: ✅ Complete | 🔄 In Progress | 📋 Planned

### Development Progress

**Phase 3 Completed** (Current Release):
- ✅ Complete dual parent approval system with parallel workflows
- ✅ Secure token-based approval pages (HMAC-SHA256, 7-day expiry)
- ✅ Visual workflow template wizard (no YAML required)
- ✅ 5 production-ready BPMN templates
- ✅ GDPR-compliant data masking for separated parents
- ✅ 156 passing tests (45% coverage)
- ✅ Comprehensive municipal documentation (4,166+ lines)

**Phase 4 Next** (In Progress):
- 🔄 BpmnTemplateManagerTest.php (5 tests)
- 🔄 WorkflowsModuleTest.php replacement
- 📋 End-to-end browser tests (FunctionalJavascript)
- 📋 Performance and security test suites
- 📋 Achieve 60%+ test coverage
- 📋 Production Serviceplatformen integration (replace mocks)

## Workflow System

ÅbenForms provides a powerful visual workflow automation system for Danish municipal processes:

### Key Features
- **Pre-built Templates**: 5 BPMN templates for common use cases (building permits, address changes, FOI requests, etc.)
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

ÅbenForms includes 5 production-ready BPMN 2.0 workflow templates:

| Template | Use Case | ECA Actions |
|----------|----------|-------------|
| `building_permit` | Building permit applications | MitID validation, CPR lookup, audit logging |
| `contact_form` | Generic citizen contact | Email notifications, case creation |
| `company_verification` | Business registration verification | CVR lookup, MitID Erhverv validation |
| `address_change` | Address change notifications | DAWA validation, Digital Post |
| `foi_request` | Freedom of Information requests | Document archiving, deadline tracking |

### Template Browser

Browse and import templates via admin UI:
- Navigate to: **Configuration > Workflows > BPMN Templates** (`/admin/config/workflow/bpmn-templates`)
- Preview templates visually (BPMN diagram)
- Import/export via XML
- Customize and save as new templates

For detailed workflow development guide, see [docs/WORKFLOW_GUIDE.md](docs/WORKFLOW_GUIDE.md).

## Documentation

For detailed information, see:
- **[CLAUDE.md](CLAUDE.md)** - Complete development guide (commands, architecture, Danish integrations)
- **[docs/WORKFLOW_GUIDE.md](docs/WORKFLOW_GUIDE.md)** - BPMN workflow development guide
- **[docs/TESTING_GUIDE.md](docs/TESTING_GUIDE.md)** - Testing guide (156 tests, 45% coverage)
- **[Platform Repository](https://github.com/madsnorgaard/aabenforms-platform)** - Deployment documentation

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

- **Frontend**: [aabenforms-frontend](https://github.com/madsnorgaard/aabenforms-frontend)
- **Platform**: [aabenforms-platform](https://github.com/madsnorgaard/aabenforms-platform)

## License

GPL-2.0 - See [LICENSE](LICENSE)

## Contributing

Issues and pull requests welcome at:
https://github.com/madsnorgaard/aabenforms/issues

---

**Developed with care for Danish municipalities**
