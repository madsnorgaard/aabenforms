# ÅbenForms Backend - Drupal 11

## Project Overview

**ÅbenForms** is a headless workflow automation platform for Danish municipalities, built on:
- **Backend**: Drupal 11.3.2 (this repository)
- **Frontend**: Nuxt 3 (separate repository)
- **Deployment**: Platform.sh orchestration (separate repository)

This backend provides:
- JSON:API endpoints for headless content delivery
- ECA (Event-Condition-Action) workflow engine (modern replacement for Maestro)
- Multi-tenancy via Domain module
- Danish government service integrations (MitID, Serviceplatformen)
- GDPR-compliant data handling with field-level encryption

## Technology Stack

| Component | Version | Purpose |
|-----------|---------|---------|
| Drupal Core | 11.3.2 | CMS foundation |
| PHP | 8.4 | Runtime |
| MariaDB | 10.11 | Database (consistent local→production) |
| DDEV | Latest | Local development |
| Composer | 2.x | Dependency management |
| Drush | 13.7 | CLI tool |

## Essential Commands

### DDEV Operations
```bash
# Start environment
cd /home/mno/ddev-projects/aabenforms/backend
ddev start

# Stop environment
ddev stop

# SSH into container
ddev ssh

# Database operations
ddev drush sql:dump > backup.sql
ddev import-db --file=backup.sql
```

### Drush Commands
```bash
# Clear cache
ddev drush cr

# Export/Import configuration
ddev drush config:export -y
ddev drush config:import -y

# Check database updates
ddev drush updatedb -y

# User operations
ddev drush user:login    # Generate one-time login link

# Module operations
ddev drush pm:enable aabenforms_tenant
ddev drush pm:uninstall <module_name>
```

### Composer Operations
```bash
# Add module
ddev composer require drupal/<module_name>

# Remove module
ddev composer remove drupal/<module_name>

# Update all dependencies
ddev composer update

# Security updates
ddev composer update drupal/core --with-dependencies
```

## Module Architecture

### Custom Modules (Package: ÅbenForms)

```
web/modules/custom/
├── aabenforms_core/              # Foundation (CRITICAL - Phase 1)
│   ├── aabenforms_core.info.yml
│   ├── aabenforms_core.module
│   └── src/
│       └── Services/             # Shared services, utilities
│
├── aabenforms_tenant/            # Multi-tenancy (Optional - Phase 1)
│   ├── aabenforms_tenant.info.yml
│   ├── aabenforms_tenant.module
│   └── src/
│       ├── Entity/TenantConfig.php
│       └── Services/TenantDetector.php
│
├── aabenforms_workflows/         # ECA integration (CRITICAL - Phase 1)
│   ├── aabenforms_workflows.info.yml
│   ├── aabenforms_workflows.module
│   └── config/install/           # Pre-built workflow templates
│
├── aabenforms_gdpr/              # Security & GDPR (HIGH - Phase 2)
│   └── [Field encryption, audit logs, retention policies]
│
├── aabenforms_mitid/             # MitID authentication (HIGH - Phase 2)
│   └── [OIDC integration for citizen/business login]
│
├── aabenforms_cpr/               # SF1520 CPR lookup (HIGH - Phase 3)
│   └── [Serviceplatformen person data]
│
├── aabenforms_cvr/               # SF1530 CVR lookup (HIGH - Phase 3)
│   └── [Serviceplatformen company data]
│
├── aabenforms_dawa/              # Address autocomplete (MEDIUM - Phase 3)
│   └── [Danish address validation]
│
├── aabenforms_digital_post/      # SF1601 Digital Post (HIGH - Phase 3)
│   └── [Official notifications]
│
├── aabenforms_sbsys/             # SBSYS integration (MEDIUM - Phase 4)
│   └── [Case management]
│
└── aabenforms_get_organized/     # GetOrganized ESDH (MEDIUM - Phase 4)
    └── [Document archiving]
```

### Module Dependencies

```
aabenforms_core (no dependencies)
  ↓
  ├─→ aabenforms_tenant (depends on: domain module)
  ├─→ aabenforms_workflows (depends on: eca, webform)
  ├─→ aabenforms_gdpr (depends on: encrypt, key, real_aes)
  ├─→ aabenforms_mitid (depends on: openid_connect, aabenforms_gdpr)
  ├─→ aabenforms_cpr (depends on: aabenforms_gdpr)
  ├─→ aabenforms_cvr (depends on: aabenforms_core)
  ├─→ aabenforms_dawa (depends on: aabenforms_core)
  ├─→ aabenforms_digital_post (depends on: aabenforms_gdpr)
  ├─→ aabenforms_sbsys (depends on: aabenforms_workflows)
  └─→ aabenforms_get_organized (depends on: aabenforms_workflows)
```

**Key Principle**: All Danish integration modules are STANDALONE - enable only what you need.

## Contrib Modules

| Module | Version | Purpose |
|--------|---------|---------|
| **Workflow Engine** | | |
| eca | 2.1.18 | Event-Condition-Action engine (replaces Maestro) |
| webform | 6.3.0-beta7 | Dynamic form builder |
| **Multi-Tenancy** | | |
| domain | 2.0.0-rc1 | URL-based tenant routing |
| domain_access | 2.0.0-rc1 | Per-tenant content isolation |
| **Security & GDPR** | | |
| encrypt | 3.2.0 | Field-level encryption |
| key | 1.22.0 | Key management |
| real_aes | 2.6.0 | AES encryption provider |
| **API** | | |
| jsonapi | Core | JSON:API implementation |
| jsonapi_extras | 3.28.0 | JSON:API enhancements |
| **Authentication** | | |
| openid_connect | 3.0.0-alpha6 | OpenID Connect (for MitID) |
| externalauth | 2.0.8 | External authentication framework |
| **Admin** | | |
| gin | 3.0.0 | Modern admin theme |
| gin_toolbar | 1.0.0 | Enhanced toolbar |

## Danish Government Integrations Roadmap

### Phase 2: Authentication & Security (Weeks 5-8)
- **aabenforms_mitid**: MitID OIDC integration
  - Personal login (MitID Privat)
  - Business login (MitID Erhverv)
  - NSIS compliance
- **aabenforms_gdpr**: GDPR compliance
  - CPR field encryption (AES-256)
  - Access audit logging
  - Data retention policies
  - Right to erasure workflows

### Phase 3: Serviceplatformen (Weeks 9-12)
All integrations via **Serviceplatformen SF15** (KOMBIT's API gateway):

- **aabenforms_cpr** (SF1520): CPR lookup
  - Person master data
  - Address history
  - Family relations

- **aabenforms_cvr** (SF1530): CVR lookup
  - Company data
  - P-numbers (production units)
  - Industry classifications

- **aabenforms_dawa**: DAWA autocomplete
  - Address validation
  - Geolocation
  - Direct integration (no auth required)

- **aabenforms_digital_post** (SF1601): Digital Post
  - Secure notifications
  - Fallback to physical mail
  - Delivery receipts

### Phase 4: Case Management (Weeks 13-16)
- **aabenforms_sbsys**: SBSYS integration
  - Case creation
  - Document archiving
  - Status synchronization

- **aabenforms_get_organized**: GetOrganized ESDH
  - Document filing
  - Metadata management

## Development Workflow

### 1. Creating New Features
```bash
# 1. Create feature branch
git checkout -b feature/add-digital-post

# 2. Enable development mode
ddev drush state:set system.maintenance_mode TRUE

# 3. Make changes, enable modules
ddev drush pm:enable aabenforms_digital_post

# 4. Export configuration
ddev drush config:export -y

# 5. Test
ddev drush updatedb -y && ddev drush cr

# 6. Disable maintenance mode
ddev drush state:set system.maintenance_mode FALSE

# 7. Commit
git add -A && git commit -m "Add Digital Post integration"
```

### 2. Updating Contrib Modules
```bash
# Check for security updates
ddev composer outdated "drupal/*"

# Update specific module
ddev composer update drupal/webform --with-dependencies
ddev drush updatedb -y
ddev drush config:export -y

# Test thoroughly, then commit
git add composer.json composer.lock config/
git commit -m "Update webform to 6.3.1"
```

### 3. Testing Multi-Tenancy
```bash
# Create test tenant domains
ddev drush domain:create aarhus.aabenforms.ddev.site "Aarhus Kommune"
ddev drush domain:create odense.aabenforms.ddev.site "Odense Kommune"

# Add to /etc/hosts (or DDEV auto-manages with use_dns_when_possible)
# 127.0.0.1 aarhus.aabenforms.ddev.site
# 127.0.0.1 odense.aabenforms.ddev.site
```

## Security & GDPR Notes

### CPR Number Handling
⚠️ **CRITICAL**: CPR numbers (Danish social security numbers) are **sensitive personal data** under GDPR Article 9.

**Requirements**:
1. **Field-level encryption** via `aabenforms_gdpr` module
2. **Access logging** - all CPR lookups must be audited
3. **Data minimization** - only request CPR when absolutely necessary
4. **Retention policies** - auto-delete after legal retention period
5. **Consent management** - explicit user consent required

### Encryption Setup
```bash
# Generate encryption key
ddev drush key:generate aes encryption --key-type=encryption --key-provider=config

# Configure field encryption
ddev drush config:set encrypt.profile.cpr_encryption encryption_key aes
```

### Audit Logging
All CPR lookups via `aabenforms_cpr` are automatically logged:
- User ID
- Timestamp
- CPR queried
- Purpose (from workflow context)
- IP address

## API Endpoints

### JSON:API Base
```
https://aabenforms.ddev.site/jsonapi
```

### Key Resources
```
# Webform schemas
GET /jsonapi/webform/webform/{form_id}

# Submissions
POST /jsonapi/webform_submission/{form_id}

# Tenant configuration
GET /jsonapi/node/tenant?filter[domain]={domain}

# Workflow tasks
GET /jsonapi/eca_workflow/task?filter[assigned_to]=current_user
```

### CORS Configuration
Configured in `.ddev/config.yaml`:
```yaml
web_environment:
  - CORS_ALLOW_ORIGIN=https://aabenforms-frontend.ddev.site
```

## Database

**Type**: MariaDB 10.11
**Why**: Consistent across all environments (local DDEV → Platform.sh production)

```bash
# Connect to database
ddev mysql

# Database snapshot
ddev snapshot

# Restore snapshot
ddev snapshot restore --latest
```

## Performance

### Caching Strategy
```bash
# Clear all caches
ddev drush cr

# Clear specific cache bin
ddev drush cache:clear render

# Check cache settings
ddev drush config:get system.performance
```

### Production Recommendations
- Enable Redis (Platform.sh service)
- Enable Drupal performance caching
- Use Varnish (Platform.sh CDN)
- Lazy-load Webform fields
- Index frequently queried fields

## Troubleshooting

### Common Issues

**Problem**: "Class not found" errors
**Solution**: Rebuild autoloader
```bash
ddev composer dump-autoload
ddev drush cr
```

**Problem**: Database connection errors
**Solution**: Restart DDEV
```bash
ddev restart
```

**Problem**: Permission denied on files
**Solution**: Fix file permissions
```bash
ddev exec chmod -R 777 web/sites/default/files
```

**Problem**: Config import fails
**Solution**: Check UUID mismatch
```bash
ddev drush config:status
ddev drush config:delete <config_name>
ddev drush config:import -y
```

## Git Workflow

```bash
# Clone repository
git clone https://github.com/madsnorgaard/aabenforms.git backend

# Create feature branch
git checkout -b feature/<name>

# Standard commit flow
git add <files>
git commit -m "Descriptive message"
git push origin feature/<name>

# Merge to main (after review)
git checkout main
git merge feature/<name>
git push origin main
```

## Related Repositories

- **Frontend**: [madsnorgaard/aabenforms-frontend](https://github.com/madsnorgaard/aabenforms-frontend)
- **Platform**: [madsnorgaard/aabenforms-platform](https://github.com/madsnorgaard/aabenforms-platform)

## Support

- **Issues**: https://github.com/madsnorgaard/aabenforms/issues
- **Drupal Docs**: https://www.drupal.org/docs
- **ECA Docs**: https://www.drupal.org/docs/contributed-modules/eca-event-driven-actions
- **Domain Docs**: https://www.drupal.org/docs/contributed-modules/domain-access

## License

GPL-2.0 - See LICENSE file
