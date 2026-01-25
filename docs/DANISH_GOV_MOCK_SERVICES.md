# Danish Government Mock Services - Project Plan

**Project Name**: `danish-gov-mock-services`
**Purpose**: Generic mock services for Danish government integrations (MitID, Serviceplatformen, etc.)
**Target Audience**: OS2 projects, municipalities, commercial vendors, developers
**License**: GPL-2.0 (aligned with OS2 ecosystem)

---

## Executive Summary

**Problem**: Developing Danish government integrations requires:
- Service agreements (takes months)
- Production credentials (security risk in dev)
- Network connectivity (can't work offline)
- Complex test data management

**Solution**: Open-source mock services that replicate Danish government APIs:
- MitID OIDC server
- Serviceplatformen SOAP gateway (SF1520, SF1530, SF1601)
- CPR/CVR test data generators
- Digital Post mock
- UNI-Login SAML server

**Benefits**:
- ✅ **No credentials needed** for development
- ✅ **Work offline** (local Docker containers)
- ✅ **Fast CI/CD** (no external dependencies)
- ✅ **Deterministic testing** (repeatable test data)
- ✅ **Community-driven** (OS2 ecosystem contribution)

---

## Use Cases

### 1. Local Development
```bash
# Developer cloning ÅbenForms for first time
git clone https://github.com/aabenforms/aabenforms.git
cd aabenforms/backend

# Start mock services
docker compose -f docker-compose.mocks.yml up -d

# Develop with full Danish integration stack (no credentials needed!)
ddev start
```

### 2. CI/CD Testing
```yaml
# GitHub Actions workflow
services:
  mitid-mock:
    image: os2community/mitid-mock:latest
  serviceplatformen-mock:
    image: os2community/serviceplatformen-mock:latest

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - name: Run integration tests
        run: npm test -- --integration
```

### 3. Training & Education
- Universities teaching Danish e-government
- Onboarding new developers to OS2 projects
- Prototyping before production access

### 4. Vendor Development
- Commercial products (XFlow, FLIS) can use for dev
- Reduces time-to-market (no waiting for test credentials)
- Standardized test data across ecosystem

---

## Architecture

### Repository Structure

```
danish-gov-mock-services/
│
├── README.md                      # Project overview
├── LICENSE                        # GPL-2.0
├── docker-compose.yml             # All services
│
├── mitid-mock/                    # MitID OIDC Server
│   ├── Dockerfile
│   ├── src/
│   │   ├── oidc-server.js         # Express OIDC implementation
│   │   ├── jwt-signer.js          # JWT token generation
│   │   └── claims-generator.js   # Person data generator
│   ├── test-data/
│   │   ├── test-users.json        # Predefined test users
│   │   └── client-configs.json   # OIDC client configurations
│   └── README.md
│
├── serviceplatformen-mock/        # Serviceplatformen SOAP Gateway
│   ├── Dockerfile
│   ├── src/
│   │   ├── soap-server.js         # Node-SOAP server
│   │   ├── services/
│   │   │   ├── sf1520-cpr.js      # CPR lookup
│   │   │   ├── sf1530-cvr.js      # CVR lookup
│   │   │   └── sf1601-digitalpost.js  # Digital Post
│   │   └── xml-generator.js       # OIO-compliant XML
│   ├── wsdl/
│   │   ├── CPRPersonBaseDataExtended.wsdl
│   │   ├── CVROnline.wsdl
│   │   └── DigitalPost.wsdl
│   ├── test-data/
│   │   ├── cpr-database.json      # Mock CPR data
│   │   ├── cvr-database.json      # Mock CVR data
│   │   └── addresses.json         # DAWA-compatible addresses
│   └── README.md
│
├── uni-login-mock/                # UNI-Login SAML Server
│   ├── Dockerfile
│   ├── src/
│   │   ├── saml-server.js         # SAML 2.0 implementation
│   │   └── institution-data.js   # School/student data
│   ├── test-data/
│   │   └── students.json          # Test students
│   └── README.md
│
├── dawa-mock/                     # DAWA Address API
│   ├── Dockerfile
│   ├── src/
│   │   └── address-api.js         # REST API
│   ├── test-data/
│   │   └── danish-addresses.json  # Address database
│   └── README.md
│
├── test-data-generator/           # CPR/CVR Data Generator
│   ├── generate-cpr.js            # Valid CPR with modulus-11
│   ├── generate-cvr.js            # Valid CVR numbers
│   └── generate-person.js         # Full person profiles
│
├── docs/
│   ├── INTEGRATION_GUIDE.md       # How to use in your project
│   ├── API_REFERENCE.md           # Mock API documentation
│   ├── TEST_DATA.md               # Available test users/companies
│   └── CONTRIBUTING.md            # Contribution guidelines
│
└── examples/                      # Integration examples
    ├── drupal/                    # Drupal module configuration
    ├── nodejs/                    # Node.js example
    ├── python/                    # Python example
    └── curl/                      # cURL commands for testing
```

---

## Component Specifications

### 1. MitID Mock Server

**Technology**: Node.js + Express + node-jose (JWT signing)

**Features**:
- OIDC Discovery endpoint (`/.well-known/openid-configuration`)
- Authorization endpoint (`/oidc/authorize`)
- Token endpoint (`/oidc/token`)
- Userinfo endpoint (`/oidc/userinfo`)
- JWT signing (RS256/ES256)
- Configurable assurance levels (low/substantial/high)
- Test user database

**Example Test User**:
```json
{
  "users": [
    {
      "username": "test-citizen-01",
      "password": "test",
      "cpr": "0101001234",
      "name": "Hans Hansen",
      "given_name": "Hans",
      "family_name": "Hansen",
      "birthdate": "1900-01-01",
      "email": "hans@example.com",
      "assurance_level": "substantial",
      "mitid_uuid": "mitid-uuid-test-123"
    },
    {
      "username": "test-business-01",
      "password": "test",
      "cpr": "0202002345",
      "name": "Karen Jensen",
      "cvr": "12345678",
      "organization_name": "Test ApS",
      "assurance_level": "high"
    }
  ]
}
```

**API Endpoints**:
```
GET  /.well-known/openid-configuration  # Discovery
GET  /oidc/authorize                    # Login page
POST /oidc/token                        # Token exchange
GET  /oidc/userinfo                     # User info
GET  /oidc/jwks                         # Public keys
```

**Docker Run**:
```bash
docker run -p 8080:8080 os2community/mitid-mock:latest
```

**Configuration**:
```yaml
# docker-compose.yml
mitid-mock:
  image: os2community/mitid-mock:latest
  ports:
    - "8080:8080"
  environment:
    - ISSUER=http://localhost:8080
    - JWT_ALGORITHM=RS256
    - DEFAULT_ASSURANCE_LEVEL=substantial
  volumes:
    - ./test-data/users.json:/app/test-data/users.json
```

---

### 2. Serviceplatformen Mock

**Technology**: Node.js + node-soap + xml2js

**Features**:
- SOAP 1.1/1.2 server
- WSDL generation
- OIO-compliant XML responses
- Mock certificate validation (bypass OCES)
- Deterministic test data
- Request logging

**Services**:

#### SF1520 - CPR Lookup
```xml
<!-- Request -->
<soapenv:Envelope>
  <soapenv:Body>
    <ns:GetPersonBaseDataExtended>
      <ns:CPRNumber>0101001234</ns:CPRNumber>
    </ns:GetPersonBaseDataExtended>
  </soapenv:Body>
</soapenv:Envelope>

<!-- Response (OIO-compliant) -->
<soapenv:Envelope>
  <soapenv:Body>
    <ns:GetPersonBaseDataExtendedResponse>
      <ns:PersonBaseDataExtended>
        <ns:PersonGivenName>Hans</ns:PersonGivenName>
        <ns:PersonSurnameName>Hansen</ns:PersonSurnameName>
        <ns:PersonCprNumber>0101001234</ns:PersonCprNumber>
        <ns:PersonBirthDate>1900-01-01</ns:PersonBirthDate>
        <ns:PersonAddressStructured>
          <ns:StreetName>Testvej</ns:StreetName>
          <ns:StreetBuildingIdentifier>42</ns:StreetBuildingIdentifier>
          <ns:PostCodeIdentifier>2100</ns:PostCodeIdentifier>
          <ns:DistrictName>København Ø</ns:DistrictName>
        </ns:PersonAddressStructured>
        <ns:PersonProtection>false</ns:PersonProtection>
      </ns:PersonBaseDataExtended>
    </ns:GetPersonBaseDataExtendedResponse>
  </soapenv:Body>
</soapenv:Envelope>
```

#### SF1530 - CVR Lookup
```xml
<!-- Request -->
<GetCompanyData>
  <CVRNumber>12345678</CVRNumber>
</GetCompanyData>

<!-- Response -->
<GetCompanyDataResponse>
  <CVROnline>
    <CompanyName>Test ApS</CompanyName>
    <CVRNumber>12345678</CVRNumber>
    <CompanyStatus>AKTIV</CompanyStatus>
    <IndustryCode>620100</IndustryCode>
  </CVROnline>
</GetCompanyDataResponse>
```

#### SF1601 - Digital Post
```xml
<!-- Request -->
<SendDigitalPost>
  <CPR>0101001234</CPR>
  <Subject>Test Notification</Subject>
  <Body>This is a test message</Body>
  <Documents>
    <Document>
      <FileName>document.pdf</FileName>
      <Content>base64-encoded-content</Content>
    </Document>
  </Documents>
</SendDigitalPost>

<!-- Response -->
<SendDigitalPostResponse>
  <MessageID>msg-uuid-12345</MessageID>
  <Status>SENT</Status>
  <DeliveryMethod>DIGITAL</DeliveryMethod>
</SendDigitalPostResponse>
```

**Docker Run**:
```bash
docker run -p 8081:8081 os2community/serviceplatformen-mock:latest
```

**Configuration**:
```yaml
serviceplatformen-mock:
  image: os2community/serviceplatformen-mock:latest
  ports:
    - "8081:8081"
  environment:
    - SF1520_ENABLED=true
    - SF1530_ENABLED=true
    - SF1601_ENABLED=true
    - LOG_REQUESTS=true
  volumes:
    - ./test-data/cpr-database.json:/app/test-data/cpr-database.json
    - ./test-data/cvr-database.json:/app/test-data/cvr-database.json
```

---

### 3. Test Data Generator

**Purpose**: Generate realistic Danish test data

**Features**:
- Valid CPR numbers (modulus-11 compliant)
- Valid CVR numbers (modulus-11 compliant)
- Realistic Danish names (from statistics)
- Real Danish addresses (from DAWA)
- Family relations (parents, children)
- Protected persons (name/address protection)

**CLI Tool**:
```bash
# Generate 100 test CPR numbers
npx danish-test-data generate cpr --count 100 --output cpr-test.json

# Generate test company data
npx danish-test-data generate cvr --count 50 --output cvr-test.json

# Generate family (2 parents + 2 children)
npx danish-test-data generate family --output family-test.json

# Generate protected person
npx danish-test-data generate protected-person --output protected.json
```

**Example Output**:
```json
{
  "cpr": "0101001234",
  "name": "Hans Hansen",
  "given_name": "Hans",
  "family_name": "Hansen",
  "birthdate": "1900-01-01",
  "gender": "male",
  "address": {
    "street": "Testvej 42",
    "postal_code": "2100",
    "city": "København Ø",
    "dawa_id": "0a3f5091-45b6-32b8-e044-0003ba298018"
  },
  "family": {
    "spouse": {
      "cpr": "0202001235",
      "name": "Karen Hansen"
    },
    "children": [
      {
        "cpr": "0303101234",
        "name": "Emma Hansen"
      }
    ]
  },
  "protected": false,
  "nationality": "dansk",
  "civil_status": "gift"
}
```

---

### 4. UNI-Login Mock (SAML Server)

**Technology**: Node.js + saml2-js

**Features**:
- SAML 2.0 IdP (Identity Provider)
- Student/teacher profiles
- Institution metadata
- Class memberships

**Test Users**:
```json
{
  "students": [
    {
      "uni_id": "test-student-001",
      "name": "Emma Petersen",
      "cpr": "0303101234",
      "institution": "Testskolen",
      "institution_id": "12345",
      "class": "5.A",
      "role": "student"
    }
  ],
  "teachers": [
    {
      "uni_id": "test-teacher-001",
      "name": "Lars Larsen",
      "cpr": "0404801234",
      "institution": "Testskolen",
      "institution_id": "12345",
      "role": "teacher"
    }
  ]
}
```

---

### 5. DAWA Mock (Address API)

**Technology**: Node.js + Express

**Features**:
- REST API (JSON responses)
- Address autocomplete
- Geolocation
- Address validation

**API Endpoints**:
```
GET /adresser/autocomplete?q=testvej 42
GET /adresser/{id}
GET /postnumre/{postal_code}
```

**Example Response**:
```json
{
  "adresser": [
    {
      "id": "0a3f5091-45b6-32b8-e044-0003ba298018",
      "vejnavn": "Testvej",
      "husnr": "42",
      "postnr": "2100",
      "postnrnavn": "København Ø",
      "koordinater": {
        "bredde": 55.7075,
        "længde": 12.5667
      }
    }
  ]
}
```

---

## Integration Guide

### For ÅbenForms

**1. Add to docker-compose.yml**:
```yaml
# docker-compose.mocks.yml
version: '3.8'

services:
  mitid-mock:
    image: os2community/mitid-mock:latest
    ports:
      - "8080:8080"
    environment:
      - ISSUER=http://localhost:8080
    volumes:
      - ./mocks/test-users.json:/app/test-data/users.json

  serviceplatformen-mock:
    image: os2community/serviceplatformen-mock:latest
    ports:
      - "8081:8081"
    environment:
      - SF1520_ENABLED=true
      - SF1530_ENABLED=true
      - SF1601_ENABLED=true
    volumes:
      - ./mocks/cpr-database.json:/app/test-data/cpr-database.json
      - ./mocks/cvr-database.json:/app/test-data/cvr-database.json

  dawa-mock:
    image: os2community/dawa-mock:latest
    ports:
      - "8082:8082"
    volumes:
      - ./mocks/addresses.json:/app/test-data/addresses.json
```

**2. Configure ÅbenForms modules**:
```php
// settings.local.php (for development)
$config['aabenforms_mitid.settings']['oidc']['issuer'] = 'http://localhost:8080';
$config['aabenforms_mitid.settings']['oidc']['authorization_endpoint'] = 'http://localhost:8080/oidc/authorize';
$config['aabenforms_mitid.settings']['oidc']['token_endpoint'] = 'http://localhost:8080/oidc/token';

$config['aabenforms_core.settings']['serviceplatformen']['endpoint'] = 'http://localhost:8081';
$config['aabenforms_core.settings']['serviceplatformen']['mock_mode'] = TRUE;

$config['aabenforms_dawa.settings']['api_url'] = 'http://localhost:8082';
```

**3. Start mocks**:
```bash
docker compose -f docker-compose.mocks.yml up -d
```

**4. Test**:
```bash
# Test MitID login
ddev drush ev "
\$client = \Drupal::service('aabenforms_mitid.oidc_client');
\$authUrl = \$client->getAuthorizationUrl('test-workflow-123');
echo \$authUrl;
"

# Test CPR lookup
ddev drush ev "
\$client = \Drupal::service('aabenforms_core.serviceplatformen_client');
\$person = \$client->execute('sf1520', 'GetPersonBaseDataExtended', ['cpr' => '0101001234']);
print_r(\$person);
"
```

---

### For Other OS2 Projects

**OS2Forms Example**:
```yaml
# docker-compose.override.yml
services:
  mitid-mock:
    image: os2community/mitid-mock:latest
    ports:
      - "8080:8080"

  serviceplatformen-mock:
    image: os2community/serviceplatformen-mock:latest
    ports:
      - "8081:8081"
```

**Configuration**:
```php
// settings.local.php
$config['os2forms_mitid.settings']['issuer'] = 'http://localhost:8080';
$config['os2forms_cpr.settings']['serviceplatformen_url'] = 'http://localhost:8081';
$config['os2forms_cpr.settings']['mock_mode'] = TRUE;
```

---

### For CI/CD

**GitHub Actions Example**:
```yaml
name: Integration Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mitid-mock:
        image: os2community/mitid-mock:latest
        ports:
          - 8080:8080

      serviceplatformen-mock:
        image: os2community/serviceplatformen-mock:latest
        ports:
          - 8081:8081

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3

      - name: Install dependencies
        run: composer install

      - name: Run integration tests
        run: vendor/bin/phpunit --group integration
        env:
          MITID_MOCK_URL: http://localhost:8080
          SERVICEPLATFORMEN_MOCK_URL: http://localhost:8081
```

---

## Test Data Management

### Predefined Test Personas

**Citizen - Normal**:
```json
{
  "username": "test-citizen-normal",
  "cpr": "0101001234",
  "name": "Hans Hansen",
  "address": "Testvej 42, 2100 København Ø",
  "protected": false,
  "spouse": "0202001235",
  "children": ["0303101234", "0404101235"]
}
```

**Citizen - Protected**:
```json
{
  "username": "test-citizen-protected",
  "cpr": "0505001234",
  "name": "[BESKYTTET]",
  "address": "[BESKYTTET]",
  "protected": true
}
```

**Business User**:
```json
{
  "username": "test-business-owner",
  "cpr": "0606001234",
  "name": "Karen Jensen",
  "cvr": "12345678",
  "organization": "Test ApS",
  "role": "direktør"
}
```

**Caseworker**:
```json
{
  "username": "test-caseworker",
  "employee_id": "emp-12345",
  "name": "Lars Andersen",
  "department": "Borgerservice",
  "municipality": "Aarhus Kommune"
}
```

---

## Community & Governance

### Target Organizations

1. **OS2 Community**
   - OS2Forms (form workflows)
   - OS2KITOS (IT contracts)
   - OS2mo (organization hierarchy)
   - OS2MO (master data)

2. **KOMBIT**
   - Serviceplatformen documentation
   - Reference implementation

3. **Commercial Vendors**
   - XFlow
   - FLIS
   - KMD
   - Fujitsu

4. **Municipalities**
   - Aarhus Kommune
   - København Kommune
   - Odense Kommune
   - 95 other municipalities

### Contribution Model

**Repository**: `github.com/os2community/danish-gov-mock-services`

**Governance**:
- OS2 Foundation as primary maintainer
- Community contributions welcome (pull requests)
- Biannual releases (aligned with Danish gov API updates)

**Contributors**:
- ÅbenForms team (initial development)
- OS2 community members
- Municipality developers
- Vendors (bug fixes, enhancements)

---

## Development Roadmap

### Phase 1: Core Services (Weeks 1-4)

**Deliverables**:
- ✅ MitID Mock (OIDC server)
- ✅ Serviceplatformen Mock (SF1520, SF1530, SF1601)
- ✅ Test data generator (CPR, CVR)
- ✅ Docker images published
- ✅ Basic documentation

**Timeline**: 4 weeks
**Resources**: 1-2 developers

### Phase 2: Advanced Features (Weeks 5-8)

**Deliverables**:
- ✅ UNI-Login Mock (SAML server)
- ✅ DAWA Mock (address API)
- ✅ Enhanced test data (families, protected persons)
- ✅ Integration examples (Drupal, Node.js, Python)
- ✅ CI/CD templates

**Timeline**: 4 weeks
**Resources**: 1 developer

### Phase 3: Community & Refinement (Weeks 9-12)

**Deliverables**:
- ✅ OS2 community presentation
- ✅ Contribution guidelines
- ✅ API reference documentation
- ✅ Example projects (OS2Forms, custom apps)
- ✅ Performance optimization

**Timeline**: 4 weeks
**Resources**: 1 developer + community feedback

### Phase 4: Ecosystem Integration (Weeks 13+)

**Deliverables**:
- Integration with OS2Forms
- Integration with OS2KITOS
- Municipal pilot programs
- Vendor adoption
- Conference presentations (KOMBIT Forum, OS2 Day)

**Timeline**: Ongoing
**Resources**: Community-driven

---

## Technical Specifications

### MitID Mock - Detailed

**Technology Stack**:
- Node.js 20 LTS
- Express 4.x
- node-jose (JWT/JWK handling)
- bcrypt (password hashing)
- Pug (login page templates)

**Dependencies**:
```json
{
  "name": "mitid-mock",
  "version": "1.0.0",
  "dependencies": {
    "express": "^4.18.0",
    "node-jose": "^2.2.0",
    "bcrypt": "^5.1.0",
    "pug": "^3.0.0",
    "body-parser": "^1.20.0",
    "uuid": "^9.0.0"
  }
}
```

**Key Files**:
```javascript
// src/oidc-server.js
const express = require('express');
const jose = require('node-jose');

class OidcServer {
  constructor() {
    this.app = express();
    this.keyStore = null;
    this.clients = new Map();
    this.users = new Map();
  }

  async initialize() {
    // Generate RSA key pair for JWT signing
    this.keyStore = jose.JWK.createKeyStore();
    await this.keyStore.generate('RSA', 2048, { alg: 'RS256', use: 'sig' });

    // Load test users
    this.loadTestUsers();

    // Setup routes
    this.setupRoutes();
  }

  setupRoutes() {
    // Discovery endpoint
    this.app.get('/.well-known/openid-configuration', (req, res) => {
      res.json({
        issuer: process.env.ISSUER,
        authorization_endpoint: `${process.env.ISSUER}/oidc/authorize`,
        token_endpoint: `${process.env.ISSUER}/oidc/token`,
        userinfo_endpoint: `${process.env.ISSUER}/oidc/userinfo`,
        jwks_uri: `${process.env.ISSUER}/oidc/jwks`,
        scopes_supported: ['openid', 'mitid', 'cpr'],
        response_types_supported: ['code'],
        grant_types_supported: ['authorization_code'],
        subject_types_supported: ['public'],
        id_token_signing_alg_values_supported: ['RS256'],
      });
    });

    // Authorization endpoint (login page)
    this.app.get('/oidc/authorize', (req, res) => {
      res.render('login', {
        client_id: req.query.client_id,
        redirect_uri: req.query.redirect_uri,
        state: req.query.state,
      });
    });

    // Token endpoint
    this.app.post('/oidc/token', async (req, res) => {
      const { code, client_id, client_secret } = req.body;

      // Validate client
      if (!this.validateClient(client_id, client_secret)) {
        return res.status(401).json({ error: 'invalid_client' });
      }

      // Exchange code for tokens
      const user = this.getUserFromCode(code);
      const idToken = await this.generateIdToken(user, client_id);

      res.json({
        access_token: 'mock-access-token',
        token_type: 'Bearer',
        expires_in: 3600,
        id_token: idToken,
      });
    });

    // Public keys endpoint
    this.app.get('/oidc/jwks', (req, res) => {
      res.json(this.keyStore.toJSON());
    });
  }

  async generateIdToken(user, clientId) {
    const payload = {
      iss: process.env.ISSUER,
      sub: user.mitid_uuid,
      aud: clientId,
      exp: Math.floor(Date.now() / 1000) + 3600,
      iat: Math.floor(Date.now() / 1000),
      cpr: user.cpr,
      name: user.name,
      given_name: user.given_name,
      family_name: user.family_name,
      birthdate: user.birthdate,
      email: user.email,
      acr: user.assurance_level,
    };

    // Sign with RS256
    const key = this.keyStore.all()[0];
    const token = await jose.JWS.createSign({ format: 'compact' }, key)
      .update(JSON.stringify(payload))
      .final();

    return token;
  }
}

module.exports = OidcServer;
```

---

### Serviceplatformen Mock - Detailed

**Technology Stack**:
- Node.js 20 LTS
- node-soap (SOAP server)
- xml2js (XML parsing)
- uuid (message IDs)

**Key Files**:
```javascript
// src/soap-server.js
const soap = require('soap');
const express = require('express');
const fs = require('fs');

class ServiceplatformenServer {
  constructor() {
    this.app = express();
    this.cprDatabase = new Map();
    this.cvrDatabase = new Map();
    this.loadTestData();
  }

  loadTestData() {
    // Load CPR test data
    const cprData = JSON.parse(fs.readFileSync('./test-data/cpr-database.json'));
    cprData.forEach(person => {
      this.cprDatabase.set(person.cpr, person);
    });

    // Load CVR test data
    const cvrData = JSON.parse(fs.readFileSync('./test-data/cvr-database.json'));
    cvrData.forEach(company => {
      this.cvrDatabase.set(company.cvr, company);
    });
  }

  setupSF1520() {
    const wsdl = fs.readFileSync('./wsdl/CPRPersonBaseDataExtended.wsdl', 'utf8');

    const service = {
      CPRService: {
        CPRServicePort: {
          GetPersonBaseDataExtended: (args, callback) => {
            const cpr = args.CPRNumber;
            const person = this.cprDatabase.get(cpr);

            if (!person) {
              return callback({
                Fault: {
                  Code: 'PersonNotFound',
                  String: `CPR ${cpr} not found in test database`,
                },
              });
            }

            // Generate OIO-compliant XML response
            const response = {
              PersonBaseDataExtended: {
                PersonGivenName: person.given_name,
                PersonSurnameName: person.family_name,
                PersonCprNumber: person.cpr,
                PersonBirthDate: person.birthdate,
                PersonAddressStructured: {
                  StreetName: person.address.street,
                  StreetBuildingIdentifier: person.address.house_number,
                  PostCodeIdentifier: person.address.postal_code,
                  DistrictName: person.address.city,
                },
                PersonProtection: person.protected,
              },
            };

            callback(null, response);
          },
        },
      },
    };

    soap.listen(this.app, '/sf1520', service, wsdl);
  }

  setupSF1530() {
    // Similar implementation for CVR lookup
  }

  setupSF1601() {
    // Similar implementation for Digital Post
  }

  start(port = 8081) {
    this.setupSF1520();
    this.setupSF1530();
    this.setupSF1601();

    this.app.listen(port, () => {
      console.log(`Serviceplatformen Mock running on port ${port}`);
    });
  }
}

module.exports = ServiceplatformenServer;
```

---

## Distribution Strategy

### Docker Hub

**Images**:
```
os2community/mitid-mock:latest
os2community/serviceplatformen-mock:latest
os2community/uni-login-mock:latest
os2community/dawa-mock:latest
os2community/danish-gov-mocks:all-in-one
```

**Tags**:
- `latest` - Latest stable release
- `v1.0.0` - Semantic versioning
- `nightly` - Development builds

### NPM Packages

**Packages**:
```
@os2community/mitid-mock
@os2community/serviceplatformen-mock
@os2community/danish-test-data
```

**Usage**:
```bash
npm install --save-dev @os2community/mitid-mock
npx mitid-mock start --port 8080
```

### Drupal Module

**Module**: `danish_gov_mocks`
**Purpose**: Automatically configure Drupal to use mock services in dev

**Features**:
- Detects `settings.local.php` (dev mode)
- Auto-configures mock endpoints
- Drush commands: `drush mocks:start`, `drush mocks:stop`

---

## Success Metrics

### Adoption Goals (Year 1)

- ✅ **10+ OS2 projects** using mocks in development
- ✅ **5+ municipalities** using in CI/CD
- ✅ **1,000+ Docker pulls** per month
- ✅ **50+ GitHub stars**
- ✅ **10+ community contributors**

### Impact Metrics

- **Time Saved**: 4-8 weeks (no waiting for credentials)
- **Cost Saved**: DKK 50,000-100,000 per project (support contracts)
- **Quality Improved**: 80%+ test coverage (deterministic tests)

---

## Budget & Resources

### Development Cost

| Phase | Effort | Timeline | Cost (DKK) |
|-------|--------|----------|------------|
| Phase 1: Core Services | 160h | 4 weeks | 120,000 |
| Phase 2: Advanced Features | 160h | 4 weeks | 120,000 |
| Phase 3: Community | 80h | 4 weeks | 60,000 |
| **TOTAL** | **400h** | **12 weeks** | **300,000** |

**Hourly Rate**: DKK 750 (developer)

### Ongoing Maintenance

- **Annual**: 40 hours (updates, bug fixes)
- **Cost**: DKK 30,000/year
- **Funded by**: OS2 Foundation membership fees

---

## Licensing & Legal

**License**: GPL-2.0 (aligned with OS2 ecosystem)

**Rationale**:
- Compatible with Drupal (GPL-2.0)
- Encourages open collaboration
- Prevents commercial lock-in
- OS2 Foundation governance

**Copyright**: © 2026 OS2 Foundation + Contributors

---

## Next Steps

### Immediate Actions

1. **Validate Concept** (Week 1)
   - Present to OS2 community
   - Gather feedback from municipalities
   - Validate with KOMBIT

2. **Create Repository** (Week 1)
   - `github.com/os2community/danish-gov-mock-services`
   - Setup CI/CD (GitHub Actions)
   - Initial documentation

3. **Develop MitID Mock** (Weeks 1-2)
   - OIDC server implementation
   - Test user database
   - Docker image

4. **Develop Serviceplatformen Mock** (Weeks 3-4)
   - SF1520, SF1530, SF1601
   - WSDL/SOAP server
   - Test data

5. **Integration with ÅbenForms** (Week 5)
   - Docker Compose configuration
   - Module configuration
   - End-to-end tests

6. **Community Launch** (Week 8)
   - OS2 Day presentation
   - Blog post
   - Documentation site

---

## Conclusion

**Value Proposition**: Open-source mock services that **accelerate development**, **reduce costs**, and **improve quality** for Danish government integrations.

**Target Audience**: OS2 projects, municipalities, vendors, developers

**Business Model**: Open-source (GPL-2.0), funded by OS2 Foundation

**Timeline**: 12 weeks to MVP, ongoing community maintenance

**Impact**: Democratizes access to Danish government integration testing

---

**Recommendation**: ✅ **PROCEED** - High value, low risk, strong community demand.

**Next Step**: Present to OS2 Foundation and KOMBIT for validation.
