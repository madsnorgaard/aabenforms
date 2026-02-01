# ÅbenForms MitID Module

**Primary CPR Access Method for ÅbenForms**

## Overview

This module provides MitID authentication and CPR Claims extraction, replacing the need for SF1520 (CPR Replika) for citizen-facing workflows.

### Why MitID Claims?

**Background**: SF1520 (CPR Replika Lookup) was closed for new users in 2024.

**Solution**: Extract CPR number and person data directly from MitID authentication tokens.

**Benefits**:
- Works for ALL new ÅbenForms clients (no SF1520 service agreement needed)
- Real-time verified data (no replica delay)
- GDPR compliant (authenticated user = explicit consent)
- No external API calls needed (CPR in token)
- Higher security (authenticated identity)
- NSIS assurance level tracking

## Features

### 1. MitID Authentication

**OIDC-based authentication** supporting:
- **MitID Privat** (Personal) - For citizens
- **MitID Erhverv** (Business) - For business users

**NSIS Assurance Levels**:
- `substantial` - Most workflows (MitID app, code reader)
- `high` - Sensitive decisions (requires additional factors)

### 2. CPR Claims Extraction

**Extract from id_token** (JWT):
```json
{
  "cpr": "0101001234",
  "name": "Hans Hansen",
  "given_name": "Hans",
  "family_name": "Hansen",
  "birthdate": "1900-01-01",
  "acr": "substantial",
  "sub": "mitid-uuid-123..."
}
```

**No external lookup needed** - Data comes from authentication token.

### 3. Flow-Scoped Sessions

**Session characteristics**:
- Tied to workflow instances (not user accounts)
- 15-minute expiration (configurable)
- Auto-delete after workflow completion
- Stored in private tempstore (or Redis for production)

### 4. Person Data Extraction

**Available data from MitID**:
- CPR number (verified)
- Full name (given + family)
- Birthdate
- Email (if provided)
- MitID UUID (persistent identifier)
- Assurance level (NSIS compliance)
- CVR number (for business MitID)
- Organization name (for business MitID)

## Installation

```bash
# Enable dependencies
ddev drush pm:enable openid_connect aabenforms_core aabenforms_webform -y

# Enable module
ddev drush pm:enable aabenforms_mitid -y

# Clear cache
ddev drush cr
```

## Configuration

### Test Environment (MitID Test Tool)

**URL**: https://pp.mitid.dk/test-tool/

**Configuration**:
```yaml
oidc:
  issuer: 'https://pp.mitid.dk/oidc'
  authorization_endpoint: 'https://pp.mitid.dk/oidc/authorize'
  token_endpoint: 'https://pp.mitid.dk/oidc/token'
  client_id: 'YOUR_TEST_CLIENT_ID'
  client_secret: 'YOUR_TEST_CLIENT_SECRET'
  scopes:
    - 'openid'
    - 'mitid'
    - 'cpr'
```

**Get Test Credentials**:
1. Visit https://pp.mitid.dk/test-tool/
2. Register your application
3. Obtain client_id and client_secret
4. Configure in ÅbenForms

### Production Environment

**MitID Production**:
- Requires service agreement with MitID
- Production credentials from https://www.mitid.dk/
- NSIS certification may be required

**Configuration**:
```yaml
oidc:
  issuer: 'https://mitid.dk/oidc'
  client_id: 'YOUR_PRODUCTION_CLIENT_ID'
  client_secret: 'YOUR_PRODUCTION_CLIENT_SECRET'
  
production: true
```

### Tenant-Specific Configuration

**Multi-tenant setup**:
```yaml
# Per-tenant MitID credentials
aabenforms_mitid.tenant.aarhus:
  oidc:
    client_id: 'aarhus-mitid-client-id'
    client_secret: 'aarhus-mitid-secret'

aabenforms_mitid.tenant.odense:
  oidc:
    client_id: 'odense-mitid-client-id'
    client_secret: 'odense-mitid-secret'
```

## Usage

### Service Access

```php
// In custom code
$cprExtractor = \Drupal::service('aabenforms_mitid.cpr_extractor');
$sessionManager = \Drupal::service('aabenforms_mitid.session_manager');

// Extract CPR from MitID token
$idToken = '...'; // From OIDC flow
$cpr = $cprExtractor->extractCpr($idToken);

// Get full person data
$personData = $cprExtractor->extractPersonData($idToken);

// Store in workflow session
$sessionManager->storeSession($workflowId, $personData);

// Retrieve later
$cpr = $sessionManager->getCprFromSession($workflowId);
```

### In Webforms

**Auto-populate CPR field**:
```yaml
citizen_cpr:
  '#type': aabenforms_cpr_field
  '#title': 'Your CPR Number'
  '#auto_populate': true  # From MitID session
  '#display_cpr_field': false  # Hide for privacy
  '#access_method': 'mitid_claims'
```

### In ECA Workflows

**BPMN workflow example**:
```xml
<!-- Step 1: Authenticate with MitID -->
<bpmn:serviceTask id="auth_mitid" name="MitID Authentication">
  <eca:action plugin="aabenforms_mitid_authenticate">
    <workflow_id>{{ workflow.id }}</workflow_id>
  </eca:action>
</bpmn:serviceTask>

<!-- Step 2: Extract CPR -->
<bpmn:serviceTask id="extract_cpr" name="Get CPR from MitID">
  <eca:action plugin="aabenforms_mitid_extract_cpr">
    <workflow_id>{{ workflow.id }}</workflow_id>
    <store_as>citizen_cpr</store_as>
  </eca:action>
</bpmn:serviceTask>

<!-- Step 3: Use CPR in workflow -->
<bpmn:serviceTask id="create_case" name="Create Case">
  <eca:action plugin="aabenforms_sbsys_create_case">
    <citizen_cpr>{{ citizen_cpr }}</citizen_cpr>
  </eca:action>
</bpmn:serviceTask>
```

## Testing

### MitID Test Tool

**Test CPR Numbers** (from MitID Test Tool):
```
0101001234  # Jan 1, 1900, Male
0202001235  # Feb 2, 1900, Female
```

**Test Authentication Flow**:
1. Visit: https://pp.mitid.dk/test-tool/
2. Generate test user with CPR
3. Authenticate with test MitID
4. Receive id_token with CPR claim
5. Extract CPR in ÅbenForms

**Test Commands**:
```bash
# Test CPR extraction (with mock token)
ddev drush ev "
\$extractor = \Drupal::service('aabenforms_mitid.cpr_extractor');

// Mock id_token payload (for testing)
\$claims = [
  'cpr' => '0101001234',
  'name' => 'Hans Hansen',
  'sub' => 'test-uuid-123',
  'iss' => 'https://pp.mitid.dk',
  'aud' => 'test-client',
  'exp' => time() + 3600,
  'iat' => time(),
  'acr' => 'substantial',
];

// Create mock JWT
\$header = base64_encode(json_encode(['alg' => 'none']));
\$payload = base64_encode(json_encode(\$claims));
\$mockToken = \$header . '.' . \$payload . '.';

// Extract CPR
\$cpr = \$extractor->extractCpr(\$mockToken);
echo 'Extracted CPR: ' . \$cpr . PHP_EOL;
"
```

## Security

### GDPR Compliance

**Data minimization**:
- Only extract CPR when needed
- Session auto-expires (15 min)
- Delete session after workflow completion

**Audit logging**:
- All CPR extractions logged
- Assurance level tracked
- Workflow context included

**Encryption**:
- CPR encrypted at rest (via aabenforms_core)
- Session data in secure tempstore

### NSIS Compliance

**Assurance levels**:
- `low` - Username/password only
- `substantial` - MitID app, code reader (default)
- `high` - Additional factors (for sensitive decisions)

**Configuration**:
```yaml
security:
  required_assurance_level: 'substantial'  # or 'high'
```

**Enforcement**:
```php
$assuranceLevel = $cprExtractor->getAssuranceLevel($idToken);
if ($assuranceLevel !== 'substantial' && $assuranceLevel !== 'high') {
  throw new \Exception('Insufficient authentication level');
}
```

## Architecture

### Flow-Scoped Authentication

```
┌────────────────────────────────────────────────┐
│  Traditional (User-Scoped)  vs.  ÅbenForms     │
│  (Flow-Scoped)                                 │
└────────────────────────────────────────────────┘

TRADITIONAL:
User → Login → User Account → Permanent → GDPR Risk

ÅBENFORMS:
Citizen → MitID → Workflow Session → 15min Expiry → Auto-Delete
                         ↓
                   Extract CPR
                         ↓
                Use in Workflow
                         ↓
                 Delete Session
```

### Data Flow

```
1. Citizen clicks "Login with MitID"
   ↓
2. OIDC redirect to MitID
   ↓
3. MitID authentication (substantial/high)
   ↓
4. Redirect back with authorization code
   ↓
5. Exchange code for id_token (JWT)
   ↓
6. Extract CPR from id_token
   ↓
7. Store in workflow session (15min)
   ↓
8. Use CPR in workflow
   ↓
9. Workflow completes → Delete session
```

## Troubleshooting

### "CPR not found in token"

**Check**:
- MitID configuration includes `cpr` scope
- Test environment configured correctly
- Token is valid and not expired

### "Token validation failed"

**Check**:
- Token expiration (`exp` claim)
- Token issuer (`iss` claim matches configuration)
- Clock skew (server time synchronized)

### "Session expired"

**Normal behavior** - Sessions expire after 15 minutes
**Solution**: Re-authenticate with MitID

## Related Modules

- **aabenforms_core** - Foundation (audit, encryption)
- **aabenforms_webform** - CPR field element
- **aabenforms_cpr** - CPR orchestration (uses MitID as backend)
- **aabenforms_workflows** - Workflow instance management

## References

- **MitID Documentation**: https://www.mitid.dk/
- **MitID Test Tool**: https://pp.mitid.dk/test-tool/
- **NSIS Specification**: https://www.digst.dk/it-loesninger/nemlog-in/
- **OIDC Specification**: https://openid.net/specs/openid-connect-core-1_0.html

## License

GPL-2.0 (aligned with Drupal and OS2 ecosystem)

## Maintainer

Mads Nørgaard <mads@aabenforms.dk>
