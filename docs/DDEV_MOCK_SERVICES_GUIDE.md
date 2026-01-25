# ÅbenForms Mock Services - DDEV Integration Guide

**Purpose**: Use Danish government mock services (MitID, Serviceplatformen, DAWA) in local DDEV development
**Target**: Both Drupal backend and Nuxt 3 frontend developers
**Approach**: Standards-based (Keycloak, WireMock, Prism) with realistic Danish test data

---

## Quick Start

### 1. Start Everything

```bash
# Navigate to backend
cd /home/mno/ddev-projects/aabenforms/backend

# Start DDEV (automatically starts mock services too!)
ddev start

# Check status
ddev mocks-status
```

**Expected Output**:
```
=== Danish Government Mock Services Status ===

🔑 Keycloak (MitID + UNI-Login):
   ✅ Running on http://localhost:8080
   📊 Admin UI: http://localhost:8080/admin (admin/admin)
   🔗 Realm: danish-gov-test

🌐 WireMock (Serviceplatformen):
   ✅ Running on http://localhost:8081
   📊 Admin UI: http://localhost:8081/__admin
   📝 Loaded mappings: 12

🏠 Prism (DAWA Address API):
   ✅ Running on http://localhost:8082
   📊 OpenAPI Docs: http://localhost:8082
```

### 2. Test Mock Services

```bash
# Test MitID OIDC discovery
curl http://localhost:8080/realms/danish-gov-test/.well-known/openid-configuration

# Test CPR lookup (Serviceplatformen SF1520)
curl -X POST http://localhost:8081/sf1520 \
  -H "Content-Type: text/xml" \
  --data '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://kombit.dk/xml/schemas/RequestPersonBaseDataExtended/1/"><soapenv:Body><ns:GetPersonBaseDataExtended><ns:CPRNumber>0101904521</ns:CPRNumber></ns:GetPersonBaseDataExtended></soapenv:Body></soapenv:Envelope>'

# Test DAWA address autocomplete
curl http://localhost:8082/adresser/autocomplete?q=frederiksberg
```

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                  DDEV Environment                    │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌──────────────────┐      ┌──────────────────┐   │
│  │  Drupal Backend  │      │  Nuxt 3 Frontend │   │
│  │  (port 443)      │◄────►│  (port 3000)     │   │
│  └────────┬─────────┘      └──────────┬───────┘   │
│           │                            │            │
│           │                            │            │
│           ▼                            ▼            │
│  ┌─────────────────────────────────────────────┐  │
│  │         Mock Services (Standards-Based)      │  │
│  ├─────────────────────────────────────────────┤  │
│  │                                              │  │
│  │  ┌──────────────┐    ┌──────────────┐      │  │
│  │  │  Keycloak    │    │  WireMock    │      │  │
│  │  │  (MitID)     │    │  (Service-   │      │  │
│  │  │  :8080       │    │  platformen) │      │  │
│  │  └──────────────┘    │  :8081       │      │  │
│  │                      └──────────────┘      │  │
│  │                                              │  │
│  │  ┌──────────────┐                          │  │
│  │  │  Prism       │                          │  │
│  │  │  (DAWA)      │                          │  │
│  │  │  :8082       │                          │  │
│  │  └──────────────┘                          │  │
│  └─────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

---

## Service Details

### Keycloak (MitID Mock)

**URL**: http://localhost:8080
**Admin UI**: http://localhost:8080/admin (admin/admin)
**Realm**: danish-gov-test

**Features**:
- ✅ Full OIDC + SAML 2.0 support
- ✅ Custom Danish claims (CPR, CVR, assurance level)
- ✅ 10 realistic test personas
- ✅ Multi-client support (backend + frontend)

**OIDC Endpoints**:
```
Discovery:      http://localhost:8080/realms/danish-gov-test/.well-known/openid-configuration
Authorization:  http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/auth
Token:          http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/token
Userinfo:       http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/userinfo
JWKs:           http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/certs
```

**Configured Clients**:
- `aabenforms-backend` (Drupal) - Confidential client
- `aabenforms-frontend` (Nuxt) - Public client

---

### WireMock (Serviceplatformen Mock)

**URL**: http://localhost:8081
**Admin UI**: http://localhost:8081/__admin

**Features**:
- ✅ SOAP 1.1/1.2 support
- ✅ OIO-compliant XML responses
- ✅ SF1520 (CPR lookup), SF1530 (CVR lookup), SF1601 (Digital Post)
- ✅ Request matching (XPath)
- ✅ No OCES certificate validation (bypassed for dev)

**Services**:
```
SF1520 (CPR):        POST http://localhost:8081/sf1520
SF1530 (CVR):        POST http://localhost:8081/sf1530
SF1601 (Digital Post): POST http://localhost:8081/sf1601
```

---

### Prism (DAWA Mock)

**URL**: http://localhost:8082
**OpenAPI Docs**: http://localhost:8082

**Features**:
- ✅ REST API (JSON)
- ✅ Address autocomplete
- ✅ Geolocation
- ✅ Realistic Danish addresses

**Endpoints**:
```
Autocomplete:  GET  http://localhost:8082/adresser/autocomplete?q={query}
Address by ID: GET  http://localhost:8082/adresser/{id}
Postal codes:  GET  http://localhost:8082/postnumre/{postal_code}
```

---

## Test Personas

### 10 Realistic Danish Citizens

| Username | Name | CPR | Role | Description |
|----------|------|-----|------|-------------|
| `freja.nielsen` | Freja Nielsen | 0101904521 | Citizen | Common Danish name, Copenhagen (Frederiksberg) |
| `mikkel.jensen` | Mikkel Jensen | 1502856234 | Citizen | Most common Danish surname |
| `sofie.hansen` | Sofie Hansen | 2506924015 | Citizen | Young parent with children |
| `lars.andersen` | Lars Andersen | 0803755210 | Citizen | Middle-aged, Aarhus resident |
| `emma.pedersen` | Emma Pedersen | 1010005206 | Citizen | Young adult, recent graduate |
| `karen.christensen` | Karen Christensen | 1205705432 | Business | Business owner, CVR: 12345678 (MitID Erhverv) |
| `protected.person` | [BESKYTTET] | 0101804321 | Citizen | Protected person (navne- og adressebeskyttelse) |
| `morten.rasmussen` | Morten Rasmussen | 2209674523 | Citizen | Senior citizen, retiree |
| `ida.mortensen` | Ida Mortensen | 0507985634 | Citizen | Young professional, Odense |
| `peter.larsen` | Peter Larsen | 1811826547 | Citizen | Typical male citizen |

**Password for all users**: `test1234`

### Login URLs

**Keycloak Admin**:
- URL: http://localhost:8080/admin
- Username: `admin`
- Password: `admin`

**Test User Account**:
- URL: http://localhost:8080/realms/danish-gov-test/account
- Login as any test user (e.g., `freja.nielsen` / `test1234`)

---

## Drupal Backend Configuration

### 1. Configure aabenforms_mitid Module

**File**: `web/sites/default/settings.local.php`

```php
<?php

/**
 * ÅbenForms Mock Services Configuration (Local Development)
 */

// MitID Mock (Keycloak)
$config['aabenforms_mitid.settings']['oidc']['issuer'] = 'http://localhost:8080/realms/danish-gov-test';
$config['aabenforms_mitid.settings']['oidc']['authorization_endpoint'] = 'http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/auth';
$config['aabenforms_mitid.settings']['oidc']['token_endpoint'] = 'http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/token';
$config['aabenforms_mitid.settings']['oidc']['userinfo_endpoint'] = 'http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/userinfo';

// OIDC Client Credentials
$config['aabenforms_mitid.settings']['oidc']['client_id'] = 'aabenforms-backend';
$config['aabenforms_mitid.settings']['oidc']['client_secret'] = 'aabenforms-backend-secret-change-in-production';

// Scopes
$config['aabenforms_mitid.settings']['oidc']['scopes'] = [
  'openid',
  'profile',
  'email',
];

// Redirect URI (auto-generated by module)
$config['aabenforms_mitid.settings']['oidc']['redirect_uri'] = 'https://aabenforms.ddev.site/mitid/callback';

// Mock mode (disables production validations)
$config['aabenforms_mitid.settings']['mock_mode'] = TRUE;
$config['aabenforms_mitid.settings']['production'] = FALSE;

// Serviceplatformen Mock (WireMock)
$config['aabenforms_core.settings']['serviceplatformen']['endpoint'] = 'http://localhost:8081';
$config['aabenforms_core.settings']['serviceplatformen']['mock_mode'] = TRUE;
$config['aabenforms_core.settings']['serviceplatformen']['validate_certificates'] = FALSE; // No OCES in mock

// SF1520 (CPR Lookup)
$config['aabenforms_cpr.settings']['serviceplatformen_url'] = 'http://localhost:8081/sf1520';
$config['aabenforms_cpr.settings']['mock_mode'] = TRUE;

// SF1530 (CVR Lookup)
$config['aabenforms_cvr.settings']['serviceplatformen_url'] = 'http://localhost:8081/sf1530';
$config['aabenforms_cvr.settings']['mock_mode'] = TRUE;

// SF1601 (Digital Post)
$config['aabenforms_digital_post.settings']['serviceplatformen_url'] = 'http://localhost:8081/sf1601';
$config['aabenforms_digital_post.settings']['mock_mode'] = TRUE;

// DAWA Mock (Prism)
$config['aabenforms_dawa.settings']['api_url'] = 'http://localhost:8082';
$config['aabenforms_dawa.settings']['mock_mode'] = TRUE;
```

### 2. Test MitID Login Flow

```bash
# Create test workflow
ddev drush ev "
\$workflowId = 'test-workflow-' . time();
echo 'Workflow ID: ' . \$workflowId . PHP_EOL;

// Generate MitID authorization URL
\$client = \Drupal::service('aabenforms_mitid.oidc_client');
\$authUrl = \$client->getAuthorizationUrl(\$workflowId);
echo 'Visit this URL to login: ' . \$authUrl . PHP_EOL;
"
```

**Expected Flow**:
1. User visits authorization URL
2. Redirected to Keycloak login page
3. Login as `freja.nielsen` / `test1234`
4. Redirected back to Drupal with authorization code
5. Drupal exchanges code for ID token
6. CPR extracted from token (`0101904521`)
7. Session stored in workflow

### 3. Test CPR Lookup

```bash
# Test SF1520 CPR lookup via mock
ddev drush ev "
\$client = \Drupal::service('aabenforms_core.serviceplatformen_client');

try {
  \$person = \$client->execute('sf1520', 'GetPersonBaseDataExtended', [
    'CPRNumber' => '0101904521',
  ]);

  echo 'Name: ' . \$person['PersonGivenName'] . ' ' . \$person['PersonSurnameName'] . PHP_EOL;
  echo 'CPR: ' . \$person['PersonCprNumber'] . PHP_EOL;
  echo 'Address: ' . \$person['PersonAddressStructured']['StreetName'] . ' ' .
       \$person['PersonAddressStructured']['StreetBuildingIdentifier'] . PHP_EOL;
  echo 'Protected: ' . (\$person['PersonProtection'] ? 'Yes' : 'No') . PHP_EOL;
} catch (\Exception \$e) {
  echo 'Error: ' . \$e->getMessage() . PHP_EOL;
}
"
```

**Expected Output**:
```
Name: Freja Nielsen
CPR: 0101904521
Address: Frederiksberg Allé 42
Protected: No
```

---

## Nuxt 3 Frontend Configuration

### 1. Install Dependencies

```bash
cd /path/to/aabenforms-frontend

# Install OIDC client
npm install @nuxtjs/auth-next
npm install @nuxt-alt/auth
```

### 2. Configure nuxt.config.ts

**File**: `nuxt.config.ts`

```typescript
export default defineNuxtConfig({
  modules: [
    '@nuxt-alt/auth',
  ],

  auth: {
    strategies: {
      mitid: {
        scheme: 'oauth2',
        endpoints: {
          authorization: 'http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/auth',
          token: 'http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/token',
          userInfo: 'http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/userinfo',
          logout: 'http://localhost:8080/realms/danish-gov-test/protocol/openid-connect/logout',
        },
        clientId: 'aabenforms-frontend',
        scope: ['openid', 'profile', 'email'],
        responseType: 'code',
        grantType: 'authorization_code',
        redirectUri: 'http://localhost:3000/callback',
        codeChallengeMethod: 'S256', // PKCE
        token: {
          property: 'access_token',
          type: 'Bearer',
          maxAge: 3600,
        },
        refreshToken: {
          property: 'refresh_token',
          maxAge: 3600,
        },
        user: {
          property: false,
        },
        autoLogout: false,
      },
    },

    redirect: {
      login: '/login',
      logout: '/',
      callback: '/callback',
      home: '/dashboard',
    },
  },

  // Runtime config (environment variables)
  runtimeConfig: {
    public: {
      mitidIssuer: 'http://localhost:8080/realms/danish-gov-test',
      apiUrl: 'https://aabenforms.ddev.site',
    },
  },
})
```

### 3. Create Login Component

**File**: `pages/login.vue`

```vue
<template>
  <div class="login-page">
    <h1>Log ind med MitID</h1>
    <button @click="loginWithMitID" class="mitid-button">
      🔑 Log ind med MitID
    </button>
    <p class="test-info">
      Test brugere: freja.nielsen, mikkel.jensen, etc.<br>
      Kodeord: test1234
    </p>
  </div>
</template>

<script setup>
const { $auth } = useNuxtApp()

const loginWithMitID = async () => {
  try {
    await $auth.loginWith('mitid')
  } catch (error) {
    console.error('Login failed:', error)
  }
}
</script>

<style scoped>
.login-page {
  max-width: 400px;
  margin: 100px auto;
  text-align: center;
}

.mitid-button {
  background-color: #003d73;
  color: white;
  padding: 15px 30px;
  font-size: 18px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.mitid-button:hover {
  background-color: #005a9c;
}

.test-info {
  margin-top: 20px;
  color: #666;
  font-size: 12px;
}
</style>
```

### 4. Display User Info

**File**: `pages/dashboard.vue`

```vue
<template>
  <div class="dashboard">
    <h1>Velkommen, {{ user.name }}</h1>

    <div class="user-info">
      <h2>Dine oplysninger fra MitID</h2>
      <dl>
        <dt>Navn:</dt>
        <dd>{{ user.name }}</dd>

        <dt>CPR:</dt>
        <dd>{{ formatCPR(user.cpr) }}</dd>

        <dt>Fødselsdato:</dt>
        <dd>{{ user.birthdate }}</dd>

        <dt>E-mail:</dt>
        <dd>{{ user.email }}</dd>

        <dt>Sikkerhedsniveau:</dt>
        <dd>
          <span :class="assuranceLevelClass">
            {{ assuranceLevelText }}
          </span>
        </dd>
      </dl>
    </div>

    <button @click="logout" class="logout-button">
      Log ud
    </button>
  </div>
</template>

<script setup>
const { $auth } = useNuxtApp()

// Get user info from ID token
const user = computed(() => {
  const idToken = $auth.strategy.token.get()
  if (!idToken) return {}

  // Decode JWT (simplified - use library in production)
  const payload = JSON.parse(atob(idToken.split('.')[1]))
  return payload
})

const formatCPR = (cpr) => {
  if (!cpr) return 'N/A'
  return `${cpr.slice(0, 6)}-${cpr.slice(6)}`
}

const assuranceLevelText = computed(() => {
  const acr = user.value.acr || ''
  if (acr.includes('high')) return 'Høj'
  if (acr.includes('substantial')) return 'Betydelig'
  return 'Lav'
})

const assuranceLevelClass = computed(() => {
  const acr = user.value.acr || ''
  if (acr.includes('high')) return 'level-high'
  if (acr.includes('substantial')) return 'level-substantial'
  return 'level-low'
})

const logout = async () => {
  await $auth.logout()
}
</script>

<style scoped>
.dashboard {
  max-width: 600px;
  margin: 50px auto;
  padding: 20px;
}

.user-info {
  background: #f5f5f5;
  padding: 20px;
  border-radius: 8px;
  margin: 20px 0;
}

dl {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 10px;
}

dt {
  font-weight: bold;
}

dd {
  margin: 0;
}

.level-high {
  color: #c30;
  font-weight: bold;
}

.level-substantial {
  color: #060;
  font-weight: bold;
}

.level-low {
  color: #666;
}

.logout-button {
  background-color: #c30;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
</style>
```

### 5. Test Frontend Login

```bash
# Start Nuxt dev server
cd /path/to/aabenforms-frontend
npm run dev

# Visit http://localhost:3000/login
# Click "Log ind med MitID"
# Login as freja.nielsen / test1234
# View dashboard with CPR and other data
```

---

## DDEV Commands Reference

### Mock Service Management

```bash
# Check status of all mock services
ddev mocks-status

# View logs (all services)
ddev mocks-logs

# View logs for specific service
ddev mocks-logs keycloak
ddev mocks-logs wiremock
ddev mocks-logs prism

# Restart mock services
ddev restart
```

### Drupal Testing

```bash
# Test MitID CPR extractor
ddev drush ev "
\$extractor = \Drupal::service('aabenforms_mitid.cpr_extractor');
\$mockToken = 'eyJ...'; // Get from Keycloak
\$cpr = \$extractor->extractCpr(\$mockToken);
echo 'CPR: ' . \$cpr . PHP_EOL;
"

# Test SF1520 lookup
ddev drush ev "
\$client = \Drupal::service('aabenforms_core.serviceplatformen_client');
\$person = \$client->execute('sf1520', 'GetPersonBaseDataExtended', ['CPRNumber' => '0101904521']);
print_r(\$person);
"

# Test session manager
ddev drush ev "
\$sessionMgr = \Drupal::service('aabenforms_mitid.session_manager');
\$sessionMgr->storeSession('test-123', ['cpr' => '0101904521', 'name' => 'Freja Nielsen']);
\$cpr = \$sessionMgr->getCprFromSession('test-123');
echo 'Stored CPR: ' . \$cpr . PHP_EOL;
"
```

---

## Adding More Test Data

### Add New Test User to Keycloak

1. **Open Keycloak Admin**: http://localhost:8080/admin
2. **Select Realm**: danish-gov-test
3. **Go to Users** → Create new user
4. **Set Attributes**:
   - `cpr`: Valid CPR number (modulus-11)
   - `birthdate`: YYYY-MM-DD format
   - `assurance_level`: `http://eidas.europa.eu/LoA/substantial`
5. **Set Credentials**: Password = `test1234`

### Add New CPR Response to WireMock

1. **Create mapping file**: `.ddev/mocks/wiremock/mappings/sf1520-{name}.json`
   ```json
   {
     "request": {
       "method": "POST",
       "urlPathPattern": "/sf1520.*",
       "bodyPatterns": [
         {"matchesXPath": {"expression": "//ns:CPRNumber[text()='YOUR_CPR']"}}
       ]
     },
     "response": {
       "status": 200,
       "bodyFileName": "sf1520-response-{name}.xml"
     }
   }
   ```

2. **Create response file**: `.ddev/mocks/wiremock/__files/sf1520-response-{name}.xml`
   - Copy existing file and modify person data

3. **Restart WireMock**: `ddev restart`

---

## Troubleshooting

### Keycloak Not Starting

**Problem**: Keycloak container fails to start

**Check logs**:
```bash
ddev mocks-logs keycloak
```

**Common fixes**:
- Ensure port 8080 is not in use: `lsof -i :8080`
- Check realm JSON syntax: `.ddev/mocks/keycloak/realms/danish-gov-test.json`
- Remove Keycloak data and restart: `docker volume rm ddev-aabenforms-keycloak`

### WireMock Not Responding

**Problem**: SF1520 returns 404

**Check mappings**:
```bash
curl http://localhost:8081/__admin/mappings
```

**Verify**:
- Mappings directory exists: `.ddev/mocks/wiremock/mappings/`
- Response files exist: `.ddev/mocks/wiremock/__files/`
- XPath expression matches request

### MitID Login Redirect Fails

**Problem**: After login, redirect fails

**Check**:
1. Redirect URI configured in Keycloak client
2. Drupal `settings.local.php` has correct callback URL
3. DDEV site URL matches: `ddev describe`

**Fix**:
```bash
# Update Keycloak client redirect URIs
# http://localhost:8080/admin → Clients → aabenforms-backend → Valid redirect URIs
# Add: https://aabenforms.ddev.site/*
```

---

## Production Deployment

### Switch from Mock to Real Services

**File**: `web/sites/default/settings.php`

```php
<?php

// Detect environment
$is_local = (getenv('DDEV_PROJECT') !== FALSE);
$is_production = (getenv('PLATFORM_BRANCH') === 'main');

if ($is_local) {
  // Use mock services (already configured in settings.local.php)
}
elseif ($is_production) {
  // Use real MitID
  $config['aabenforms_mitid.settings']['oidc']['issuer'] = 'https://mitid.dk/oidc';
  $config['aabenforms_mitid.settings']['oidc']['client_id'] = getenv('MITID_CLIENT_ID');
  $config['aabenforms_mitid.settings']['oidc']['client_secret'] = getenv('MITID_CLIENT_SECRET');
  $config['aabenforms_mitid.settings']['production'] = TRUE;
  $config['aabenforms_mitid.settings']['mock_mode'] = FALSE;

  // Use real Serviceplatformen
  $config['aabenforms_core.settings']['serviceplatformen']['endpoint'] = 'https://prod.serviceplatformen.dk';
  $config['aabenforms_core.settings']['serviceplatformen']['mock_mode'] = FALSE;
  $config['aabenforms_core.settings']['serviceplatformen']['validate_certificates'] = TRUE;
}
```

---

## Benefits of This Approach

### For Developers

- ✅ **Start coding immediately** - No waiting for credentials
- ✅ **Work offline** - No network dependencies
- ✅ **Fast tests** - Mock responses in milliseconds
- ✅ **Deterministic** - Same test data every time
- ✅ **Realistic** - Danish names, addresses, CPR numbers

### For Teams

- ✅ **Onboarding** - New developers productive on day one
- ✅ **CI/CD** - Fast integration tests without external APIs
- ✅ **Parallel development** - Backend and frontend teams work independently
- ✅ **Cost savings** - No test environment costs

### For Quality

- ✅ **Edge cases** - Test protected persons, high assurance levels
- ✅ **Error scenarios** - Simulate Serviceplatformen failures
- ✅ **Security testing** - Test without risking real CPR data
- ✅ **Performance testing** - Load test without rate limits

---

## Next Steps

1. ✅ **Start DDEV**: `ddev start`
2. ✅ **Check status**: `ddev mocks-status`
3. ✅ **Configure Drupal**: Add to `settings.local.php`
4. ✅ **Configure Nuxt**: Update `nuxt.config.ts`
5. ✅ **Test login flow**: Login as `freja.nielsen`
6. ✅ **Build workflows**: Use mock services in development

---

## Additional Resources

- **Keycloak Docs**: https://www.keycloak.org/documentation
- **WireMock Docs**: https://wiremock.org/docs/
- **Prism Docs**: https://stoplight.io/open-source/prism
- **MitID Test Tool**: https://pp.mitid.dk/test-tool/
- **KOMBIT Docs**: https://docs.kombit.dk/

---

**Created By**: ÅbenForms Team
**Last Updated**: 2026-01-25
**Version**: 1.0.0

**Questions?** Open an issue: https://github.com/aabenforms/aabenforms/issues
