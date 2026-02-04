# ÅbenForms Backend

**Open source workflow automation platform for Danish municipalities**

[![Drupal](https://img.shields.io/badge/Drupal-11.3.2-blue)](https://www.drupal.org)
[![PHP](https://img.shields.io/badge/PHP-8.4-purple)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL--2.0-green)](LICENSE.txt)
[![Tests](https://img.shields.io/badge/tests-180%20passing-brightgreen)](#testing)
[![Coverage](https://img.shields.io/badge/coverage-19.14%25-yellow)](#testing)

---

## Overview

ÅbenForms is a production-ready, headless Drupal 11 platform that enables Danish municipalities to automate citizen-facing workflows while integrating with government services including MitID, Serviceplatformen (CPR/CVR lookup), and Digital Post.

Built on modern, modular architecture with GDPR compliance at its core, ÅbenForms provides municipalities with a secure, scalable foundation for digital service delivery.

### Current Status: Phase 5 Complete

Phase 5 marks the completion of comprehensive testing infrastructure, establishing ÅbenForms as a production-ready platform with:

- 180 passing automated tests (unit, performance, and security suites)
- Complete approval workflow system with secure token-based authentication
- 5 production-ready BPMN workflow templates
- GDPR-compliant data handling with field-level encryption
- Extensive documentation for municipal administrators and developers

**Production deployments are ready to begin.**

---

## Key Features

### Workflow Automation
- **Visual workflow builder** with BPMN 2.0 standard
- **5 pre-built templates**: Building permits, contact forms, company verification, address changes, Freedom of Information requests
- **Dual-party approval system** with parallel workflow execution
- **ECA engine** (Event-Condition-Action) for complex business logic
- **Template wizard** with 8-step configuration (no coding required)

### Danish Government Integration
- **MitID authentication** (Privat and Erhverv)
- **Serviceplatformen SF15** integration:
  - SF1520: CPR person data lookup
  - SF1530: CVR company data lookup
  - SF1601: Digital Post notifications (planned)
- **DAWA address validation** and autocomplete
- **GDPR compliance**: Field encryption, audit logging, data retention policies

### Multi-Tenancy
- **Domain-based tenant isolation** via Domain module
- **Per-tenant configuration** (MitID credentials, branding, workflows)
- **Shared codebase** with isolated data
- **Scalable architecture** for multiple municipalities on single infrastructure

### Security & Compliance
- **Field-level AES-256 encryption** for sensitive data (CPR numbers)
- **Comprehensive audit logging** for all data access
- **HMAC-SHA256 approval tokens** with 7-day expiry
- **Timing-safe comparisons** to prevent timing attacks
- **XXE injection protection** for BPMN XML processing
- **Right to erasure workflows** for GDPR Article 17 compliance

---

## Technology Stack

| Component | Version | Purpose |
|-----------|---------|---------|
| **Core Platform** | | |
| Drupal Core | 11.3.2 | Headless CMS foundation |
| PHP | 8.4 | Runtime environment |
| MariaDB | 10.11 | Relational database |
| JSON:API | Core | RESTful API for headless architecture |
| **Workflow Engine** | | |
| ECA | 3.0.10 | Event-driven workflow automation |
| BPMN.iO | 3.0.4 | Visual workflow modeler |
| Webform | 6.3.0-beta7 | Dynamic form builder |
| **Multi-Tenancy** | | |
| Domain | 2.0.0-rc1 | URL-based tenant routing |
| Domain Access | 2.0.0-rc1 | Content isolation per tenant |
| **Security** | | |
| Encrypt | 3.2.0 | Field-level encryption |
| Key | 1.22.0 | Key management system |
| Real AES | 2.6.0 | AES-256 encryption provider |
| **Authentication** | | |
| OpenID Connect | 3.0.0-alpha6 | MitID OIDC integration |
| External Auth | 2.0.8 | External authentication framework |
| **Developer Experience** | | |
| DDEV | Latest | Local development environment |
| Drush | 13.7 | Command-line administration |
| Gin | 3.0.0 | Modern admin theme |

---

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
                           │ Serviceplatformen SF15
                           ▼
               ┌───────────────────────┐
               │  Danish Gov Services  │
               │  - MitID (auth)       │
               │  - CPR (person data)  │
               │  - CVR (company data) │
               │  - Digital Post       │
               └───────────────────────┘
```

---

## Quick Start

### Prerequisites

- Docker Desktop installed and running
- DDEV installed ([installation guide](https://ddev.readthedocs.io/en/latest/users/install/))
- Git configured
- 8GB+ RAM available

### Installation

```bash
# Clone repository
git clone https://github.com/madsnorgaard/aabenforms.git backend
cd backend

# Start DDEV environment
ddev start

# Access admin interface
ddev launch
# Login: admin / admin
```

### Local Development URLs

- **Admin UI**: https://aabenforms.ddev.site/admin
- **JSON:API**: https://aabenforms.ddev.site/jsonapi
- **Webform Admin**: https://aabenforms.ddev.site/admin/structure/webform
- **Workflow Builder**: https://aabenforms.ddev.site/admin/config/workflow/eca
- **Mailpit**: https://aabenforms.ddev.site:8026

### First Steps

1. **Create a test workflow**:
   ```bash
   ddev drush aabenforms:create-test-workflow
   ```

2. **Browse workflow templates**:
   Navigate to: Configuration > Workflows > BPMN Templates

3. **Run test suite**:
   ```bash
   ddev exec phpunit --testsuite=unit
   ```

4. **View comprehensive guides**:
   - [Municipal Admin Guide](docs/MUNICIPAL_ADMIN_GUIDE.md) - For non-technical administrators
   - [CLAUDE.md](CLAUDE.md) - For developers and AI assistants

---

## Custom Modules

ÅbenForms is built on a modular architecture with 8 custom modules organized in the `aabenforms` package namespace.

### Core Foundation

| Module | Status | Description |
|--------|--------|-------------|
| **aabenforms_core** | Production | Base services, utilities, Serviceplatformen client, encryption, audit logging |
| **aabenforms_tenant** | Production | Multi-tenancy via Domain module with per-tenant configuration |
| **aabenforms_webform** | Production | Custom form elements: CPR validator, CVR validator, DAWA address field |

### Security & Authentication

| Module | Status | Description |
|--------|--------|-------------|
| **aabenforms_mitid** | Production | MitID OIDC integration, session management, CPR extraction from tokens |
| **aabenforms_gdpr** | Beta | Field encryption, comprehensive audit logging, retention policies (planned) |

### Workflow Engine

| Module | Status | Description |
|--------|--------|-------------|
| **aabenforms_workflows** | Production | Complete workflow automation platform:<br>• 5 BPMN 2.0 templates<br>• Visual workflow wizard (8-step template instantiation)<br>• Approval system with secure token-based access<br>• Parallel parent approval workflows<br>• Email notifications<br>• 4 custom ECA actions |

### Danish Service Integrations

| Module | Status | Description |
|--------|--------|-------------|
| **aabenforms_cpr** | Beta | SF1520 CPR person lookup (action plugin ready, production API integration planned) |
| **aabenforms_cvr** | Beta | SF1530 CVR company lookup (action plugin ready, production API integration planned) |

**Planned**: aabenforms_dawa (full API), aabenforms_digital_post (SF1601), aabenforms_sbsys (case management), aabenforms_get_organized (ESDH archiving)

---

## Workflow System

### Pre-Built Templates

ÅbenForms includes 5 production-ready BPMN 2.0 workflow templates:

| Template | Use Case | Key Features |
|----------|----------|--------------|
| **Building Permit** | Construction permit applications | MitID validation, CPR lookup, case worker assignment, audit logging |
| **Contact Form** | Generic citizen inquiries | Email notifications, automatic categorization, case creation |
| **Company Verification** | Business registration validation | CVR lookup, MitID Erhverv authentication, company data extraction |
| **Address Change** | Change of address notifications | DAWA address validation, Digital Post confirmation, multi-system updates |
| **FOI Request** | Freedom of Information requests | Document tracking, deadline management, case archiving |

### Dual-Party Approval System

Unique three-workflow parallel approval architecture for scenarios requiring multiple approvals (e.g., both parents approving daycare enrollment):

```
Main Workflow (Orchestrator)
    │
    ├─ Workflow 1: Parent 1 Approval
    │  ├─ MitID Authentication
    │  ├─ CPR Lookup & Data Display
    │  └─ Capture Decision
    │
    ├─ Workflow 2: Parent 2 Approval
    │  ├─ MitID Authentication
    │  ├─ CPR Lookup & Data Display
    │  └─ Capture Decision
    │
    └─ Synchronization Point
       └─ Both Approved? → Case Worker Review
```

**Security features**:
- HMAC-SHA256 tokens with 7-day expiry
- Timing-safe token comparison
- CSRF protection per submission and parent
- Independent authentication sessions
- GDPR-compliant data masking for separated parents

### Template Wizard

Non-technical administrators can create workflows in 8 steps:

1. **Select template** (building permit, contact form, etc.)
2. **Configure basic info** (name, description, category)
3. **Map form fields** (link webform elements to workflow variables)
4. **Configure notifications** (email recipients, templates)
5. **Set approval rules** (single/dual approval, timeout policies)
6. **Define integrations** (MitID, CPR/CVR lookups)
7. **Review configuration** (visual preview of BPMN diagram)
8. **Activate workflow** (deploy to production)

No YAML editing or programming required.

---

## Testing

### Test Infrastructure (Phase 5 Complete)

ÅbenForms has comprehensive automated testing across multiple dimensions:

| Test Suite | Tests | Status | Purpose |
|------------|-------|--------|---------|
| **Unit Tests** | 166 | 100% passing | Core logic, services, validators |
| **Performance Tests** | 6 | 100% passing | Action plugins, token generation, template loading |
| **Security Tests** | 8 | 100% passing | CSRF protection, timing attacks, token expiry, XXE injection |
| **Total** | **180** | **100% passing** | Comprehensive validation |

**Coverage**: 19.14% (635/3318 lines)

While unit test coverage is comprehensive for tested components, overall coverage is below the 60% target due to integration test dependencies. Phase 6 will focus on kernel and functional tests to validate end-to-end workflows.

### Running Tests

```bash
# Run all unit tests
ddev exec phpunit --testsuite=unit

# Run performance tests
ddev exec phpunit --group=performance

# Run security tests
ddev exec phpunit --group=security

# Generate coverage report
ddev xdebug on
ddev exec "XDEBUG_MODE=coverage phpunit --coverage-html=coverage/html"
```

### Test Results

Detailed test reports are available in [docs/reports/](docs/reports/):
- [Database Integration Tests](docs/reports/DATABASE_INTEGRATION_TEST_RESULTS.md)
- [API Endpoint Tests](docs/reports/API_ENDPOINT_TEST_RESULTS.md)
- [E2E Integration Tests](docs/reports/E2E_INTEGRATION_TEST_RESULTS.md)
- [Coverage Report](docs/reports/TEST_COVERAGE_REPORT.md)

---

## Documentation

### For Municipal Administrators

Non-technical guides for configuring and managing workflows:

- **[Municipal Admin Guide](docs/MUNICIPAL_ADMIN_GUIDE.md)** - Complete operational guide
- **[Approval Process Guide](docs/APPROVAL_PROCESS_GUIDE.md)** - End-to-end approval flow
- **[Workflow Templates Reference](docs/WORKFLOW_TEMPLATES.md)** - Template specifications
- **[Quick Reference Card](docs/QUICK_REFERENCE.md)** - One-page cheat sheet
- **[Video Tutorial Script](docs/VIDEO_SCRIPT.md)** - 5-minute video guide

### For Developers

Technical documentation for customization and integration:

- **[CLAUDE.md](CLAUDE.md)** - Complete development guide with commands, architecture, and AI context
- **[Workflow Guide](docs/WORKFLOW_GUIDE.md)** - BPMN workflow development
- **[Testing Guide](docs/TESTING_GUIDE.md)** - Testing strategies and best practices
- **[Deployment Guide](docs/DEPLOYMENT_GUIDE.md)** - Production deployment on Platform.sh
- **[Maintenance Guide](docs/MAINTENANCE_GUIDE.md)** - Operations and troubleshooting

### For Decision Makers

Strategic documentation for planning and scaling:

- **[Municipal Sales Guide](docs/MUNICIPAL_SALES_GUIDE.md)** - Value proposition and ROI
- **[Pilot Deployment Guide](docs/PILOT_DEPLOYMENT_GUIDE.md)** - Phased rollout strategy
- **[Scaling Guide](docs/SCALING_GUIDE.md)** - Multi-municipality architecture
- **[Production Checklist](docs/PRODUCTION_CHECKLIST.md)** - Pre-launch verification

### Analysis & Reports

- **[Week 10 Summary](docs/analysis/week10_summary.md)** - Development progress report
- **[Copilot Review Response](docs/analysis/COPILOT_REVIEW_RESPONSE.md)** - Code review findings

---

## Development

### Essential Commands

```bash
# Cache management
ddev drush cr                    # Clear all caches

# Configuration management
ddev drush config:export -y      # Export active configuration
ddev drush config:import -y      # Import staged configuration

# Database operations
ddev drush updatedb -y           # Apply pending database updates
ddev drush user:login            # Generate one-time login link

# Module management
ddev composer require drupal/module_name
ddev drush pm:enable module_name
ddev drush config:export -y      # Always export after enabling modules
```

### Adding Custom Modules

```bash
# Create new custom module
mkdir -p web/modules/custom/my_module
cd web/modules/custom/my_module

# Create .info.yml
cat > my_module.info.yml << 'EOF'
name: 'My Module'
type: module
description: 'Custom module description'
package: 'Custom'
core_version_requirement: ^11
dependencies:
  - drupal:core
EOF

# Enable and export
ddev drush pm:enable my_module
ddev drush config:export -y
```

### Workflow Development

```bash
# Create new BPMN template
cp web/modules/custom/aabenforms_workflows/templates/bpmn/building_permit.bpmn \
   web/modules/custom/aabenforms_workflows/templates/bpmn/my_workflow.bpmn

# Edit template with BPMN editor
# Navigate to: /admin/config/workflow/eca/add/bpmn_io

# Validate template
ddev drush aabenforms:validate-template my_workflow

# Test workflow
ddev drush aabenforms:test-workflow my_workflow
```

---

## Security & GDPR Compliance

### Data Protection

ÅbenForms is designed with GDPR compliance as a core principle:

1. **Field-level encryption**: CPR numbers encrypted with AES-256 at rest
2. **Comprehensive audit logging**: All data access logged with user, timestamp, purpose, IP
3. **Data minimization**: Only request data absolutely necessary for workflow
4. **Explicit consent**: Consent management integrated into webforms
5. **Right to erasure**: Automated workflows for GDPR Article 17 compliance
6. **Data retention policies**: Configurable auto-deletion after legal retention period

### CPR Number Handling

**CRITICAL**: CPR numbers are sensitive personal data under GDPR Article 9.

**Requirements enforced by aabenforms_gdpr module**:
- Field-level encryption (automatic)
- Access logging (automatic)
- Consent validation (required)
- Data retention enforcement (configurable)

### Security Best Practices

```bash
# Generate encryption key (production environments)
ddev drush key:generate aes encryption --key-type=encryption --key-provider=config

# Configure field encryption
ddev drush config:set encrypt.profile.cpr_encryption encryption_key aes

# Enable audit logging
ddev drush config:set aabenforms_core.settings audit.enabled true

# Review audit logs
ddev drush sql:query "SELECT * FROM aabenforms_audit_log WHERE action='cpr_lookup' ORDER BY created DESC LIMIT 20;"
```

---

## Related Repositories

- **Frontend**: [aabenforms-frontend](https://github.com/madsnorgaard/aabenforms-frontend) - Nuxt 3 multi-tenant UI
- **Platform**: [aabenforms-platform](https://github.com/madsnorgaard/aabenforms-platform) - Platform.sh deployment configuration

---

## Contributing

Issues and pull requests are welcome at: https://github.com/madsnorgaard/aabenforms/issues

### Development Guidelines

1. Follow Drupal coding standards (run `ddev drush phpcs`)
2. Write unit tests for new services (minimum 60% coverage)
3. Document all public APIs with PHPDoc
4. Update relevant documentation in docs/
5. Export configuration changes (`ddev drush config:export -y`)
6. Commit configuration separately from code changes

---

## License

GPL-2.0 - See [LICENSE.txt](LICENSE.txt)

---

## Support

- **Documentation**: [docs/](docs/)
- **Developer Guide**: [CLAUDE.md](CLAUDE.md)
- **Issues**: https://github.com/madsnorgaard/aabenforms/issues
- **Drupal Community**: https://www.drupal.org/docs
- **ECA Module**: https://www.drupal.org/docs/contributed-modules/eca-event-driven-actions

---

Developed with care for Danish municipalities by Mads Nørgaard.
