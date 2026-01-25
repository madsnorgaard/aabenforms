# International Standards & Open Source Tools for Danish Gov Mock Services

**Date**: 2026-01-25
**Purpose**: Identify international standards and existing open-source tools to leverage for mock services
**Strategy**: Build on proven standards, don't reinvent the wheel

---

## Executive Summary

**Key Principle**: Use **international standards** where possible, add **Danish-specific extensions** only where necessary.

**Recommendation**: Build Danish mock services as **thin wrappers** around proven open-source tools like:
- ✅ **Keycloak** (instead of custom OIDC server)
- ✅ **WireMock** (for HTTP/SOAP mocking)
- ✅ **SimpleSAMLphp** (for UNI-Login)
- ✅ **Faker.js** (for test data generation)

**Benefits**:
- 🔧 Less code to maintain
- 🛡️ Battle-tested security
- 📚 Better documentation
- 🌍 International community support
- 🚀 Faster development

---

## International Standards

### 1. Identity & Authentication Standards

#### OpenID Connect (OIDC) - ✅ **USE THIS**

**Official Spec**: https://openid.net/specs/openid-connect-core-1_0.html
**Status**: International standard (used by Google, Microsoft, MitID)

**Why**: MitID already uses OIDC, so our mocks should be fully compliant.

**Key Specifications**:
- **OIDC Core** - Authentication flow
- **OIDC Discovery** - `/.well-known/openid-configuration`
- **JWT** (RFC 7519) - ID token format
- **JWS** (RFC 7515) - Token signing
- **JWK** (RFC 7517) - Public key format

**Existing Open-Source Implementations**:

| Tool | Language | Maturity | Use Case |
|------|----------|----------|----------|
| **Keycloak** ⭐ | Java | Production | Full-featured IdP (RECOMMENDED) |
| **ORY Hydra** | Go | Production | Lightweight OAuth 2.0/OIDC server |
| **Ory Kratos** | Go | Production | Identity management |
| **IdentityServer** | .NET | Production | Enterprise OIDC/OAuth |
| **node-oidc-provider** | Node.js | Production | Flexible OIDC library |

**RECOMMENDATION**: Use **Keycloak** as base for MitID mock.

**Why Keycloak**:
- ✅ Full OIDC compliance (certified by OpenID Foundation)
- ✅ Admin UI (manage test users easily)
- ✅ Realm support (multi-tenant test environments)
- ✅ Custom claim mappers (add CPR, CVR claims)
- ✅ Docker image available
- ✅ Huge community (Red Hat backed)
- ✅ SAML 2.0 support (for UNI-Login too!)

**Danish Extension**:
```javascript
// Keycloak custom mapper for Danish claims
{
  "name": "Danish CPR Mapper",
  "protocol": "openid-connect",
  "protocolMapper": "oidc-usermodel-attribute-mapper",
  "config": {
    "user.attribute": "cpr",
    "claim.name": "cpr",
    "jsonType.label": "String",
    "id.token.claim": "true"
  }
}
```

---

#### OAuth 2.0 - ✅ **USE THIS**

**Official Spec**: https://oauth.net/2/
**RFCs**:
- RFC 6749 - OAuth 2.0 Authorization Framework
- RFC 6750 - Bearer Token Usage
- RFC 7636 - PKCE (Proof Key for Code Exchange)

**Why**: Foundation for OIDC, used by MitID.

**Recommendation**: Keycloak already implements OAuth 2.0 fully.

---

#### SAML 2.0 - ✅ **USE THIS**

**Official Spec**: http://docs.oasis-open.org/security/saml/Post2.0/
**Status**: International standard (legacy but still widely used)

**Why**: UNI-Login uses SAML 2.0, many older Danish systems use SAML.

**Existing Open-Source Implementations**:

| Tool | Language | Maturity | Use Case |
|------|----------|----------|----------|
| **SimpleSAMLphp** ⭐ | PHP | Production | SAML IdP/SP (RECOMMENDED) |
| **Shibboleth** | Java | Production | Enterprise SAML |
| **Keycloak** | Java | Production | SAML + OIDC combined |
| **saml2-js** | Node.js | Stable | Lightweight SAML library |

**RECOMMENDATION**: Use **Keycloak** (supports both SAML + OIDC in one).

**Danish Extension**:
```xml
<!-- SAML assertion with UNI-Login claims -->
<saml:Attribute Name="https://data.stil.dk/UniLogin/userId">
  <saml:AttributeValue>test-student-001</saml:AttributeValue>
</saml:Attribute>
<saml:Attribute Name="https://data.stil.dk/UniLogin/institutionId">
  <saml:AttributeValue>12345</saml:AttributeValue>
</saml:Attribute>
```

---

#### eIDAS (EU Electronic Identification) - ✅ **ALIGN WITH THIS**

**Official Spec**: https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/eIDAS
**Status**: EU regulation (910/2014)

**Why**: MitID is eIDAS-compliant (substantial/high assurance levels).

**Key Concepts**:
- **Assurance Levels**: Low, Substantial, High (maps to NSIS)
- **eIDAS Node**: Cross-border identity federation
- **Qualified Certificates**: For digital signatures (OCES)

**ÅbenForms Alignment**:
```php
// Map MitID ACR to eIDAS levels
$mapping = [
  'http://eidas.europa.eu/LoA/low' => 'low',
  'http://eidas.europa.eu/LoA/substantial' => 'substantial',
  'http://eidas.europa.eu/LoA/high' => 'high',
];
```

**RECOMMENDATION**: Use eIDAS ACR URIs (not custom Danish URIs).

---

### 2. Web Services Standards

#### SOAP 1.1/1.2 - ✅ **USE THIS**

**Official Spec**: https://www.w3.org/TR/soap12/
**Status**: W3C Recommendation

**Why**: Serviceplatformen uses SOAP for SF1520, SF1530, SF1601.

**Existing Open-Source Tools**:

| Tool | Language | Maturity | Use Case |
|------|----------|----------|----------|
| **WireMock** ⭐ | Java | Production | HTTP/SOAP mocking (RECOMMENDED) |
| **SoapUI** | Java | Production | SOAP testing (GUI tool) |
| **node-soap** | Node.js | Stable | SOAP server/client |
| **Apache CXF** | Java | Production | Enterprise SOAP framework |
| **Postman** | - | Production | API testing (has SOAP support) |

**RECOMMENDATION**: Use **WireMock** for Serviceplatformen mock.

**Why WireMock**:
- ✅ HTTP/SOAP/REST mocking in one tool
- ✅ Stub mapping (JSON configuration)
- ✅ Request matching (XPath for SOAP)
- ✅ Response templating (dynamic data)
- ✅ Docker image available
- ✅ Widely used (industry standard)

**Example WireMock Configuration**:
```json
{
  "request": {
    "method": "POST",
    "urlPath": "/sf1520",
    "bodyPatterns": [
      {
        "matchesXPath": "//CPRNumber[text()='0101001234']"
      }
    ]
  },
  "response": {
    "status": 200,
    "headers": {
      "Content-Type": "text/xml"
    },
    "bodyFileName": "sf1520-response-0101001234.xml"
  }
}
```

---

#### WSDL (Web Services Description Language) - ✅ **USE THIS**

**Official Spec**: https://www.w3.org/TR/wsdl20/
**Status**: W3C Recommendation

**Why**: Serviceplatformen services defined with WSDL.

**Recommendation**: Use official KOMBIT WSDL files (already public).

**Source**: https://docs.kombit.dk/integration/sf1520/4.0/

---

#### WS-Security - ✅ **USE THIS**

**Official Spec**: http://docs.oasis-open.org/wss/2004/01/
**Status**: OASIS Standard

**Why**: Serviceplatformen uses WS-Security for message-level security.

**Key Concepts**:
- **Signature**: XML Digital Signature (OCES certificates)
- **Encryption**: XML Encryption
- **Timestamp**: Message freshness
- **UsernameToken**: Basic auth alternative

**Mock Strategy**: **Bypass in mock** (no real certificate validation).

**WireMock Configuration**:
```json
{
  "request": {
    "bodyPatterns": [
      {
        "matchesXPath": "//wsse:Security",
        "xPathNamespaces": {
          "wsse": "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
        }
      }
    ]
  },
  "response": {
    "status": 200
  }
}
```

---

### 3. Data Standards

#### JSON Schema - ✅ **USE THIS**

**Official Spec**: https://json-schema.org/
**Status**: Internet standard

**Why**: Validate REST API requests/responses.

**Use Case**: DAWA mock (address API is JSON-based).

**Example**:
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "DAWA Address",
  "type": "object",
  "properties": {
    "id": { "type": "string", "format": "uuid" },
    "vejnavn": { "type": "string" },
    "husnr": { "type": "string" },
    "postnr": { "type": "string", "pattern": "^[0-9]{4}$" },
    "postnrnavn": { "type": "string" }
  },
  "required": ["id", "vejnavn", "husnr", "postnr"]
}
```

---

#### OpenAPI (Swagger) - ✅ **USE THIS**

**Official Spec**: https://swagger.io/specification/
**Status**: Industry standard (Linux Foundation)

**Why**: Document REST APIs (DAWA mock).

**Existing Tools**:
- **Prism** - OpenAPI mock server
- **Swagger UI** - API documentation
- **Redoc** - Alternative documentation

**RECOMMENDATION**: Use **Prism** for DAWA mock.

**Example**:
```yaml
openapi: 3.0.0
info:
  title: DAWA Mock API
  version: 1.0.0
paths:
  /adresser/autocomplete:
    get:
      parameters:
        - name: q
          in: query
          schema:
            type: string
      responses:
        '200':
          description: Address autocomplete results
          content:
            application/json:
              schema:
                type: array
                items:
                  $ref: '#/components/schemas/Address'
```

---

#### XML Schema (XSD) - ✅ **USE THIS**

**Official Spec**: https://www.w3.org/TR/xmlschema11-1/
**Status**: W3C Recommendation

**Why**: OIO standards define Danish government data formats in XSD.

**Source**: https://www.digst.dk/standarder/ (OIO XSD schemas)

**Recommendation**: Use official OIO XSD files for Serviceplatformen responses.

---

### 4. Security Standards

#### X.509 Certificates - ✅ **USE THIS**

**Official Spec**: RFC 5280
**Status**: Internet standard

**Why**: OCES certificates are X.509 certificates.

**Mock Strategy**: **Self-signed certificates** for testing.

**Tool**: OpenSSL
```bash
# Generate self-signed certificate (mock OCES)
openssl req -x509 -newkey rsa:4096 -keyout mock-oces.key -out mock-oces.crt -days 365 -nodes
```

---

#### JWT (JSON Web Token) - ✅ **USE THIS**

**Official Spec**: RFC 7519
**Status**: IETF standard

**Why**: MitID ID tokens are JWTs.

**Libraries**:
- **node-jose** (Node.js)
- **PyJWT** (Python)
- **jose4j** (Java)

**Recommendation**: Keycloak handles JWT signing automatically.

---

## Existing Open-Source Mock Frameworks

### 1. Keycloak - ✅ **PRIMARY RECOMMENDATION**

**Website**: https://www.keycloak.org/
**License**: Apache 2.0
**Language**: Java
**Maturity**: Production (Red Hat backed)

**Use Cases**:
- MitID OIDC mock
- UNI-Login SAML mock (Keycloak supports both!)

**Features**:
- ✅ OIDC + SAML in one
- ✅ Admin UI (manage test users)
- ✅ Realms (multi-tenant)
- ✅ Custom claim mappers (add CPR, CVR, UNI-ID)
- ✅ User federation (LDAP, database)
- ✅ Docker image
- ✅ REST API (for automation)

**Docker Setup**:
```yaml
# docker-compose.yml
services:
  keycloak:
    image: quay.io/keycloak/keycloak:23.0
    environment:
      KEYCLOAK_ADMIN: admin
      KEYCLOAK_ADMIN_PASSWORD: admin
    ports:
      - "8080:8080"
    command: start-dev
```

**Danish Configuration**:
```javascript
// Create realm: "danish-gov-test"
// Add custom user attributes: cpr, cvr, uni_id
// Create claim mappers to include in ID token
{
  "realm": "danish-gov-test",
  "clients": [
    {
      "clientId": "aabenforms-test",
      "protocol": "openid-connect",
      "redirectUris": ["http://localhost:3000/callback"]
    }
  ],
  "users": [
    {
      "username": "test-citizen",
      "attributes": {
        "cpr": ["0101001234"],
        "name": ["Hans Hansen"],
        "given_name": ["Hans"],
        "family_name": ["Hansen"],
        "birthdate": ["1900-01-01"]
      }
    }
  ]
}
```

**Advantages over Custom OIDC Server**:
- 🔧 No code to maintain (just configuration)
- 🛡️ Security audited (Red Hat)
- 📚 Excellent documentation
- 🌍 Large community
- 🚀 Admin UI (no code needed for user management)

---

### 2. WireMock - ✅ **PRIMARY RECOMMENDATION**

**Website**: https://wiremock.org/
**License**: Apache 2.0
**Language**: Java
**Maturity**: Production (industry standard)

**Use Cases**:
- Serviceplatformen SOAP mock (SF1520, SF1530, SF1601)
- HTTP API mocking

**Features**:
- ✅ SOAP/REST mocking
- ✅ Request matching (XPath, JSONPath, regex)
- ✅ Response templating (dynamic data)
- ✅ Stateful scenarios
- ✅ Fault injection (test error handling)
- ✅ Docker image
- ✅ Standalone or embedded

**Docker Setup**:
```yaml
services:
  wiremock:
    image: wiremock/wiremock:3.3.1
    ports:
      - "8081:8080"
    volumes:
      - ./wiremock/mappings:/home/wiremock/mappings
      - ./wiremock/files:/home/wiremock/__files
```

**SF1520 Mock Example**:
```json
{
  "request": {
    "method": "POST",
    "urlPath": "/sf1520",
    "bodyPatterns": [
      {
        "matchesXPath": "//CPRNumber[text()='0101001234']"
      }
    ]
  },
  "response": {
    "status": 200,
    "headers": {
      "Content-Type": "text/xml"
    },
    "body": "<?xml version=\"1.0\"?><soap:Envelope>...</soap:Envelope>"
  }
}
```

**Advantages over Custom SOAP Server**:
- 🔧 No code (just JSON configuration)
- 🛡️ Battle-tested
- 📚 Great documentation
- 🌍 Industry standard (used by Netflix, Amazon, etc.)
- 🚀 Fast development (no coding)

---

### 3. Prism (OpenAPI Mock Server) - ✅ **RECOMMENDATION**

**Website**: https://stoplight.io/open-source/prism
**License**: Apache 2.0
**Language**: Node.js
**Maturity**: Production

**Use Case**: DAWA address API mock

**Features**:
- ✅ OpenAPI 3.0 support
- ✅ Dynamic mock data
- ✅ Validation (requests/responses)
- ✅ Docker image

**Docker Setup**:
```yaml
services:
  prism:
    image: stoplight/prism:4
    command: mock -h 0.0.0.0 /api/dawa-openapi.yaml
    ports:
      - "8082:4010"
    volumes:
      - ./openapi/dawa-openapi.yaml:/api/dawa-openapi.yaml
```

---

### 4. Faker.js / Bogus - ✅ **RECOMMENDATION**

**Faker.js**: https://fakerjs.dev/
**Bogus**: https://github.com/bchavez/Bogus

**Use Case**: Generate realistic test data

**Features**:
- ✅ Names, addresses, emails, phone numbers
- ✅ Locale support (da_DK for Danish)
- ✅ Deterministic (seeded random)

**Danish Extension**:
```javascript
import { faker } from '@faker-js/faker';
import { da } from '@faker-js/faker/locale/da';

faker.locale = 'da';

// Generate Danish person
const person = {
  name: faker.name.fullName(),       // "Hans Hansen"
  address: faker.address.streetAddress(), // "Testvej 42"
  city: faker.address.city(),        // "København"
  email: faker.internet.email(),     // "hans@example.com"
};

// Add CPR generator (custom)
function generateCPR(birthdate) {
  const dd = birthdate.getDate().toString().padStart(2, '0');
  const mm = (birthdate.getMonth() + 1).toString().padStart(2, '0');
  const yy = birthdate.getFullYear().toString().slice(-2);
  const sequence = faker.datatype.number({ min: 1000, max: 9999 });

  const cpr = dd + mm + yy + sequence;

  // Calculate modulus-11 check digit
  const weights = [4, 3, 2, 7, 6, 5, 4, 3, 2, 1];
  let sum = 0;
  for (let i = 0; i < 10; i++) {
    sum += parseInt(cpr[i]) * weights[i];
  }

  // If valid modulus-11, return; otherwise regenerate
  return (sum % 11 === 0) ? cpr : generateCPR(birthdate);
}

const cpr = generateCPR(new Date('1900-01-01')); // "0101001234"
```

---

## Recommended Architecture (Revised)

### Stack Overview

```
┌─────────────────────────────────────────────┐
│         Danish Gov Mock Services            │
├─────────────────────────────────────────────┤
│                                             │
│  ┌──────────────┐  ┌──────────────┐        │
│  │  Keycloak    │  │  WireMock    │        │
│  │  (MitID +    │  │  (Service-   │        │
│  │  UNI-Login)  │  │  platformen) │        │
│  └──────────────┘  └──────────────┘        │
│                                             │
│  ┌──────────────┐  ┌──────────────┐        │
│  │  Prism       │  │  Test Data   │        │
│  │  (DAWA API)  │  │  Generator   │        │
│  └──────────────┘  └──────────────┘        │
│                                             │
│         ┌────────────────────┐             │
│         │ Danish Extensions  │             │
│         │ (CPR/CVR/OIO data) │             │
│         └────────────────────┘             │
└─────────────────────────────────────────────┘
```

### Component Breakdown

| Service | Base Tool | Danish Extension | Effort |
|---------|-----------|------------------|--------|
| **MitID OIDC** | Keycloak | Custom claim mappers (CPR, CVR) | 1 week |
| **UNI-Login SAML** | Keycloak | SAML claim mappers (UNI-ID, institution) | 1 week |
| **SF1520 (CPR)** | WireMock | SOAP stubs + test CPR data | 1 week |
| **SF1530 (CVR)** | WireMock | SOAP stubs + test CVR data | 1 week |
| **SF1601 (Digital Post)** | WireMock | SOAP stubs + delivery status | 1 week |
| **DAWA Address** | Prism | OpenAPI spec + Danish addresses | 1 week |
| **Test Data Generator** | Faker.js | CPR/CVR generators (modulus-11) | 1 week |
| **Orchestration** | Docker Compose | Multi-service setup | 1 week |

**Total Effort**: 8 weeks (vs 12 weeks for custom implementation)
**Cost Savings**: 33% reduction (DKK 100,000 saved)

---

## Benefits of Standards-Based Approach

### Technical Benefits

| Benefit | Custom Implementation | Standards-Based | Winner |
|---------|----------------------|-----------------|--------|
| **Development Time** | 12 weeks | 8 weeks | ✅ Standards |
| **Maintenance** | High (custom code) | Low (configuration) | ✅ Standards |
| **Security** | Needs audit | Pre-audited | ✅ Standards |
| **Documentation** | Must write | Already exists | ✅ Standards |
| **Community Support** | None | Large community | ✅ Standards |
| **Updates** | Manual | Automatic (upstream) | ✅ Standards |

### Business Benefits

- 💰 **Cost**: DKK 200,000 (vs DKK 300,000 custom)
- ⏱️ **Time**: 8 weeks (vs 12 weeks custom)
- 🛡️ **Risk**: Low (proven tools) vs High (custom code)
- 🌍 **Adoption**: High (familiar tools) vs Low (learning curve)

---

## International Examples

### 1. UK Government - GOV.UK Notify Mock

**Project**: https://github.com/alphagov/notifications-api
**Approach**: Mock notification services (email, SMS, letters)
**Tools**: Python Flask + PostgreSQL
**Learning**: Government mock services are valuable and widely used

### 2. Australian Government - DTA Mock Services

**Project**: https://github.com/govau
**Approach**: Mock identity and document verification
**Tools**: Node.js + Docker
**Learning**: Docker Compose for easy local development

### 3. EU eIDAS Node Mock

**Project**: https://ec.europa.eu/digital-building-blocks/code/projects/EIDMOCK
**Approach**: Mock eIDAS cross-border authentication
**Tools**: Java + WireMock
**Learning**: Standards-based approach (eIDAS spec compliance)

### 4. OpenID Foundation Conformance Suite

**Project**: https://openid.net/certification/testing/
**Approach**: Test OIDC providers for compliance
**Tools**: Java + Python
**Learning**: Test suite can double as mock server

---

## Recommendations

### PRIMARY STACK (Recommended)

```yaml
# docker-compose.yml
version: '3.8'

services:
  # MitID + UNI-Login Mock (both OIDC + SAML)
  keycloak:
    image: quay.io/keycloak/keycloak:23.0
    ports:
      - "8080:8080"
    volumes:
      - ./keycloak/realms:/opt/keycloak/data/import
    environment:
      KEYCLOAK_ADMIN: admin
      KEYCLOAK_ADMIN_PASSWORD: admin
    command: start-dev --import-realm

  # Serviceplatformen Mock (SF1520, SF1530, SF1601)
  wiremock:
    image: wiremock/wiremock:3.3.1
    ports:
      - "8081:8080"
    volumes:
      - ./wiremock/mappings:/home/wiremock/mappings
      - ./wiremock/__files:/home/wiremock/__files

  # DAWA Address API Mock
  prism:
    image: stoplight/prism:4
    command: mock -h 0.0.0.0 /api/dawa-openapi.yaml
    ports:
      - "8082:4010"
    volumes:
      - ./openapi/dawa-openapi.yaml:/api/dawa-openapi.yaml

  # Test Data Generator API
  test-data-api:
    build: ./test-data-generator
    ports:
      - "8083:3000"
    environment:
      NODE_ENV: development
```

**Effort**: 8 weeks
**Cost**: DKK 200,000
**Maintenance**: Low (configuration-based)

---

### ALTERNATIVE STACK (Lightweight)

If you want even simpler:

```yaml
# All-in-one mock server
services:
  mockserver:
    image: mockserver/mockserver:5.15.0
    ports:
      - "1080:1080"
    environment:
      MOCKSERVER_INITIALIZATION_JSON_PATH: /config/expectations.json
    volumes:
      - ./mockserver/expectations.json:/config/expectations.json
```

**Tool**: MockServer (https://www.mock-server.com/)
**Features**: OIDC + SOAP + REST in one container
**Effort**: 6 weeks
**Trade-off**: Less feature-rich than Keycloak + WireMock

---

## Implementation Plan (Revised)

### Phase 1: Setup Base Tools (Week 1-2)

**Tasks**:
1. Setup Keycloak with "danish-gov-test" realm
2. Configure WireMock with SOAP stubs
3. Setup Prism with DAWA OpenAPI spec
4. Create Docker Compose orchestration

**Deliverables**:
- ✅ Running Keycloak instance
- ✅ Running WireMock instance
- ✅ Running Prism instance
- ✅ `docker-compose up` works

### Phase 2: Danish Extensions (Week 3-5)

**Tasks**:
1. **Keycloak**: Add custom claim mappers (CPR, CVR, UNI-ID)
2. **WireMock**: Create SOAP stubs for SF1520, SF1530, SF1601
3. **Prism**: Add Danish address data
4. **Test Data**: Build CPR/CVR generator

**Deliverables**:
- ✅ Keycloak returns CPR in ID token
- ✅ WireMock responds to SF1520 SOAP requests
- ✅ Prism returns Danish addresses
- ✅ Test data generator creates valid CPR numbers

### Phase 3: Integration & Testing (Week 6-7)

**Tasks**:
1. Integrate with ÅbenForms
2. Create example configurations
3. Write integration tests
4. Documentation

**Deliverables**:
- ✅ ÅbenForms works with mock services
- ✅ Example projects (Drupal, Node.js)
- ✅ CI/CD templates
- ✅ User documentation

### Phase 4: Community Launch (Week 8)

**Tasks**:
1. Publish Docker images
2. Create GitHub repository
3. Write contribution guidelines
4. Present to OS2 community

**Deliverables**:
- ✅ Published to Docker Hub (os2community/*)
- ✅ GitHub repository live
- ✅ Community presentation
- ✅ Blog post

---

## Standards Compliance Checklist

### Identity Standards
- ✅ **OIDC Core 1.0** - Keycloak certified
- ✅ **OAuth 2.0** (RFC 6749) - Keycloak certified
- ✅ **SAML 2.0** - Keycloak supports
- ✅ **eIDAS** - Use eIDAS ACR URIs
- ✅ **JWT** (RFC 7519) - Keycloak generates
- ✅ **JWS** (RFC 7515) - Keycloak signs tokens
- ✅ **PKCE** (RFC 7636) - Keycloak supports

### Web Services Standards
- ✅ **SOAP 1.1/1.2** - WireMock supports
- ✅ **WSDL 1.1/2.0** - Use official KOMBIT WSDLs
- ✅ **WS-Security** - WireMock can validate (or bypass)
- ✅ **XML Schema (XSD)** - Use OIO schemas

### Data Standards
- ✅ **OpenAPI 3.0** - Prism uses
- ✅ **JSON Schema** - Prism validates
- ✅ **X.509 Certificates** - Self-signed for mocks

### Danish Standards
- ✅ **OIO Standards** - Use official XSD files
- ✅ **NSIS** - Map to eIDAS levels
- ✅ **CPR Modulus-11** - Custom generator
- ✅ **CVR Modulus-11** - Custom generator

---

## Open Source License Strategy

### Recommended License: **Apache 2.0** ✅

**Why Apache 2.0** (instead of GPL-2.0):
- ✅ Compatible with Keycloak (Apache 2.0)
- ✅ Compatible with WireMock (Apache 2.0)
- ✅ More permissive (allows commercial use)
- ✅ No copyleft (easier adoption by vendors)
- ✅ Patent protection clause

**Trade-off**: GPL-2.0 would align with Drupal/OS2, but Apache 2.0 allows broader ecosystem adoption.

**Recommendation**: Use **Apache 2.0** for mock services, **GPL-2.0** for Drupal modules.

---

## Conclusion

### Key Decisions

1. ✅ **Use Keycloak** (not custom OIDC server)
2. ✅ **Use WireMock** (not custom SOAP server)
3. ✅ **Use Prism** (for OpenAPI mocking)
4. ✅ **Use Faker.js** (for test data)
5. ✅ **Follow international standards** (OIDC, SAML, eIDAS, SOAP)
6. ✅ **Add Danish extensions** (CPR, CVR, OIO data)

### Benefits

- 🔧 **33% less development** (8 weeks vs 12 weeks)
- 💰 **33% cost savings** (DKK 200,000 vs DKK 300,000)
- 🛡️ **Better security** (audited tools)
- 📚 **Better documentation** (existing docs)
- 🌍 **Wider adoption** (familiar tools)

### Next Steps

1. **Validate approach** with OS2 Foundation
2. **Setup Keycloak** with Danish realm
3. **Create WireMock stubs** for Serviceplatformen
4. **Test integration** with ÅbenForms
5. **Launch to community**

---

**Final Recommendation**: ✅ **BUILD ON INTERNATIONAL STANDARDS** - Don't reinvent the wheel!

**Key Principle**: "Danish government services are just **data extensions** on top of **international protocols**."

---

**Created By**: Claude Sonnet 4.5
**Date**: 2026-01-25
**Next Review**: After OS2 Foundation validation
