# ÅbenForms Backend

**Headless Drupal 11 backend for Danish municipal workflow automation**

[![Drupal](https://img.shields.io/badge/Drupal-11.3.2-blue)](https://www.drupal.org)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-green)](LICENSE)

[![CI](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml/badge.svg)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)
[![Coding Standards](https://github.com/madsnorgaard/aabenforms/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/madsnorgaard/aabenforms/actions/workflows/coding-standards.yml)
[![Coverage](https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/madsnorgaard/aabenforms/main/.github/badges/coverage.json)](https://github.com/madsnorgaard/aabenforms/actions/workflows/ci.yml)

## Overview

ÅbenForms is a modern, modular platform for Danish municipalities to automate citizen-facing workflows and integrate with government services (MitID, Serviceplatformen, case management systems).

This repository contains the **Drupal 11 backend** that provides:
-  ECA workflow engine (event-driven automation)
- Dynamic webforms with JSON:API exposure
-  Multi-tenant architecture via Domain module
-  GDPR-compliant CPR encryption
- 🇩🇰 Danish government service integrations (MitID, SF1520, SF1530, SF1601)

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

### Core Platform (Phase 1)
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_core` | ✅ Active | Base services, utilities, JSON:API config |
| `aabenforms_tenant` | ✅ Active | Multi-tenancy via Domain module |
| `aabenforms_workflows` | ✅ Complete | ECA integration, BPMN templates, workflow wizard |

### Security & Auth (Phase 2)
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_gdpr` |  Planned | Field encryption, audit logs, retention |
| `aabenforms_mitid` |  Planned | MitID OIDC authentication |

### Danish Integrations (Phase 3-4)
| Module | Status | Description |
|--------|--------|-------------|
| `aabenforms_cpr` |  Planned | SF1520 person lookup |
| `aabenforms_cvr` |  Planned | SF1530 company lookup |
| `aabenforms_dawa` |  Planned | Address autocomplete |
| `aabenforms_digital_post` |  Planned | SF1601 notifications |
| `aabenforms_sbsys` |  Planned | SBSYS case management |
| `aabenforms_get_organized` |  Planned | GetOrganized ESDH |

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

## Documentation

For detailed information, see:
- **[CLAUDE.md](CLAUDE.md)** - Complete development guide (commands, architecture, Danish integrations)
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
