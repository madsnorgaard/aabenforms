# CI/CD with Danish Government Mock Services

**Status**: Working - mock services integrated into GitHub Actions CI

This is a pre-pilot POC. The same mock services described here run locally in DDEV
and in CI; none of them talk to live Danish government endpoints.

---

## What We Built

The GitHub Actions CI pipeline runs with the same Danish government mock services
used locally in DDEV:

1. Keycloak (MitID mock) - 10 Danish test users in the `danish-gov-test` realm
2. WireMock (Serviceplatformen mock) - SF1520 CPR lookup stubs
3. Test data - valid CPR numbers, Danish names, Copenhagen addresses

MitID is a Keycloak mock realm only (no live MitID registration). CPR (SF1520) and
CVR (SF1530) lookups run against WireMock; live use would require client
certificates. Digital Post (SF1601) is exercised in test/mock modes only - there is
no live MeMo/SOAP path yet.

---

## CI Pipeline Flow

```
┌─────────────────────────────────────────────────────────┐
│  Pull Request / Push to main                            │
└────────────────┬────────────────────────────────────────┘
                 │
     ┌───────────┴──────────────┐
     │                           │
     ▼                           ▼
┌─────────────────┐    ┌──────────────────────┐
│ Validate        │    │ PHPUnit Tests        │
│ Composer        │    │ with Mock Services   │
│                 │    │                      │
│ - Validate      │    │ Services:            │
│ - Security      │    │ MariaDB 10.11     │
│   Audit         │    │ Keycloak:8080     │
└─────────────────┘    │ WireMock:8081     │
                       │                      │
                       │ Steps:               │
                       │ 1. Import realm      │
                       │ 2. Load mappings     │
                       │ 3. Run tests         │
                       │ 4. Generate coverage │
                       └──────────────────────┘
                                │
                                ▼
                       ┌──────────────────────┐
                       │ CI Summary           │
                       │                      │
                       │ Tests result         │
                       │ Coverage report      │
                       │ 10 test users        │
                       └──────────────────────┘
```

---

## Key Features

### 1. Automatic Mock Service Setup

The CI pipeline automatically:
- Starts Keycloak and WireMock containers
- Imports the `danish-gov-test` realm (10 users)
- Loads WireMock mappings for SF1520 CPR lookups
- Verifies services are healthy before running tests

### 2. Same Data Everywhere

| Environment | MitID | Serviceplatformen | Test Users |
|-------------|-------|-------------------|------------|
| **Local (DDEV)** | localhost:8080 | localhost:8081 | Same 10 users |
| **CI (GitHub)** | localhost:8080 | localhost:8081 | Same 10 users |
| **Staging** | localhost:8080 | localhost:8081 | Same 10 users |
| **Production** | mitid.dk | serviceplatformen.dk | Real citizens |

**Benefit**: Tests pass locally = tests pass in CI = predictable deployments

### 3. Zero Credentials Needed

- No MitID test credentials
- No Serviceplatformen certificates
- No external API dependencies
- **100% offline capable**

### 4. Fast and free

- **Pipeline duration**: a few minutes
- **Cost**: GitHub free tier
- **Parallel jobs**: Composer validation + PHPUnit tests
- **Caching**: Composer dependencies cached

---

## Test Users Available in CI

All 10 Danish personas are available during CI test runs:

| Username | CPR | Name | Description |
|----------|-----|------|-------------|
| freja.nielsen | 0101904521 | Freja Nielsen | Copenhagen, Frederiksberg Allé 42 |
| mikkel.jensen | 1502856234 | Mikkel Jensen | Most common surname |
| sofie.hansen | 2506924015 | Sofie Hansen | Young parent |
| lars.andersen | 0803755210 | Lars Andersen | Aarhus resident |
| emma.pedersen | 1010005206 | Emma Pedersen | Young adult |
| karen.christensen | 1205705432 | Karen Christensen | **Business user** (CVR: 12345678) |
| protected.person | 0101804321 | [BESKYTTET] | **Protected person** (hidden data) |
| morten.rasmussen | 2209674523 | Morten Rasmussen | Senior citizen |
| ida.mortensen | 0507985634 | Ida Mortensen | Odense resident |
| peter.larsen | 1811826547 | Peter Larsen | Typical male citizen |

**Password for all**: `test1234`

---

## Environment Variables in CI

The following environment variables are automatically set during PHPUnit tests:

```bash
SIMPLETEST_DB=mysql://root:root@127.0.0.1/drupal_test
SIMPLETEST_BASE_URL=http://localhost
KEYCLOAK_URL=http://localhost:8080
KEYCLOAK_REALM=danish-gov-test
SERVICEPLATFORMEN_URL=http://localhost:8081
MOCK_MODE=true
```

Your modules can use these to detect CI environment:

```php
// In your module
$isMockMode = getenv('MOCK_MODE') === 'true';
$keycloakUrl = getenv('KEYCLOAK_URL') ?: 'http://localhost:8080';
```

---

## Writing Integration Tests

### Example: Testing MitID Login Flow

```php
<?php

namespace Drupal\Tests\aabenforms_mitid\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests MitID authentication with mock Keycloak.
 *
 * @group aabenforms_mitid
 * @group integration
 */
class MitIdAuthenticationTest extends KernelTestBase {

  protected static $modules = ['aabenforms_core', 'aabenforms_mitid'];

  public function testMockMitIdLogin() {
    // Mock Keycloak is automatically available at http://localhost:8080
    $keycloakUrl = getenv('KEYCLOAK_URL');
    $this->assertEquals('http://localhost:8080', $keycloakUrl);

    // Test OIDC discovery endpoint
    $discoveryUrl = "$keycloakUrl/realms/danish-gov-test/.well-known/openid-configuration";
    $response = file_get_contents($discoveryUrl);
    $config = json_decode($response, TRUE);

    $this->assertNotEmpty($config['authorization_endpoint']);
    $this->assertNotEmpty($config['token_endpoint']);

    // Test that we can authenticate as Freja Nielsen (CPR: 0101904521)
    // (In real test, you'd use Guzzle to simulate OAuth flow)
  }
}
```

### Example: Testing CPR Lookup

```php
<?php

namespace Drupal\Tests\aabenforms_workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;

/**
 * Tests SF1520 CPR lookup with mock Serviceplatformen.
 *
 * CPR (SF1520) and CVR (SF1530) lookups are action plugins in
 * aabenforms_workflows backed by the Serviceplatformen client in
 * aabenforms_core. They are not standalone modules.
 *
 * @group aabenforms_workflows
 * @group integration
 */
class CprLookupTest extends KernelTestBase {

  protected static $modules = ['aabenforms_core', 'aabenforms_workflows'];

  public function testMockCprLookup() {
    $serviceplatformenUrl = getenv('SERVICEPLATFORMEN_URL');
    $this->assertEquals('http://localhost:8081', $serviceplatformenUrl);

    // Mock WireMock has SF1520 stubs loaded
    $client = new Client();
    $response = $client->post("$serviceplatformenUrl/sf1520", [
      'headers' => ['Content-Type' => 'text/xml'],
      'body' => $this->buildCprSoapRequest('0101904521'),
    ]);

    $this->assertEquals(200, $response->getStatusCode());

    // Parse response
    $xml = simplexml_load_string($response->getBody());
    $name = (string) $xml->xpath('//ns:PersonGivenName')[0];

    $this->assertEquals('Freja', $name);
  }

  private function buildCprSoapRequest(string $cpr): string {
    return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://kombit.dk/xml/schemas/RequestPersonBaseDataExtended/1/">
  <soapenv:Body>
    <ns:GetPersonBaseDataExtended>
      <ns:CPRNumber>$cpr</ns:CPRNumber>
    </ns:GetPersonBaseDataExtended>
  </soapenv:Body>
</soapenv:Envelope>
XML;
  }
}
```

---

## CI Workflow File

**Location**: `.github/workflows/ci.yml`

**Key Sections**:

### Services Definition
```yaml
services:
  mysql:
    image: mariadb:10.11
  keycloak:
    image: quay.io/keycloak/keycloak:23.0
    ports:
      - 8080:8080
  wiremock:
    image: wiremock/wiremock:3.3.1
    ports:
      - 8081:8080
```

### Mock Service Initialization
```yaml
- name: Wait for Mock Services
  run: |
    timeout 120 bash -c 'until curl -sf http://localhost:8080; do sleep 2; done'
    timeout 60 bash -c 'until curl -sf http://localhost:8081/__admin/health; do sleep 2; done'

- name: Import Keycloak Realm (Danish Test Users)
  run: |
    TOKEN=$(curl -sf -X POST http://localhost:8080/realms/master/protocol/openid-connect/token ...)
    curl -X POST http://localhost:8080/admin/realms \
      -H "Authorization: Bearer $TOKEN" \
      -d @.ddev/mocks/keycloak/realms/danish-gov-test.json

- name: Configure WireMock Mappings (Serviceplatformen)
  run: |
    for mapping in .ddev/mocks/wiremock/mappings/*.json; do
      curl -X POST http://localhost:8081/__admin/mappings -d @"$mapping"
    done
```

---

## Viewing CI Results

### 1. Pull Request Checks

When you create a PR, GitHub will automatically:
1. Run the CI pipeline
2. Show status checks on the PR page
3. Display a summary with coverage and mock service info

### 2. GitHub Actions Tab

Navigate to: `https://github.com/madsnorgaard/aabenforms/actions`

You'll see:
- All jobs (Composer validation, PHPUnit tests)
- Code coverage percentage
- Mock service information
-  Duration (~5-8 minutes)

### 3. Coverage Report Artifact

After each CI run:
1. Click on the workflow run
2. Scroll to "Artifacts" section
3. Download `coverage-report` (HTML)
4. Open `index.html` in browser

---

## Troubleshooting CI

### Issue: Keycloak import fails

**Error**: `Realm import failed`

**Solution**: Check if realm JSON is valid
```bash
# Locally validate JSON
jq . .ddev/mocks/keycloak/realms/danish-gov-test.json
```

### Issue: WireMock mappings not loading

**Error**: `Failed to load mapping`

**Solution**: Check XPath syntax in mapping files
```bash
# Locally test WireMock
ddev restart
curl http://localhost:8081/__admin/mappings
```

### Issue: Tests fail in CI but pass locally

**Debugging**:
1. Check environment variables match
2. Verify services started successfully (check logs)
3. Run tests locally with same env vars:
```bash
export KEYCLOAK_URL=http://localhost:8080
export SERVICEPLATFORMEN_URL=http://localhost:8081
export MOCK_MODE=true
ddev test
```

---

## Benefits Summary

Running against mock services instead of live Danish government endpoints means:

- No service agreements or production credentials needed to develop or run CI
- No external network dependency, so CI is deterministic and can run offline
- Faster, more reliable CI runs (no flaky third-party APIs)

---

## Next Steps

### 1. Write More Integration Tests

Focus on the areas that integrate with Danish services:
- `aabenforms_mitid`: authentication flows (Keycloak mock)
- `aabenforms_workflows`: CPR (SF1520) and CVR (SF1530) lookup action plugins
- `aabenforms_core`: Serviceplatformen client
- `aabenforms_digital_post`: Digital Post (SF1601) delivery in fake_db / wiremock modes

### 2. Add More WireMock Stubs

Currently only Freja Nielsen has a CPR lookup stub. Add stubs for all 10 users:

```bash
# Create stub for each user
cd .ddev/mocks/wiremock/mappings/
cp sf1520-freja-nielsen.json sf1520-mikkel-jensen.json
# Edit CPR number and response file reference
```

### 3. Enable Branch Protection

Configure GitHub to require CI passing before merge:

1. Go to: Settings → Branches → Add rule
2. Branch name pattern: `main`
3. Enable: "Require status checks to pass before merging"
4. Select: `CI Summary`

### 4. Add Coverage Badge to README

```markdown
![Coverage](https://img.shields.io/endpoint?url=https://raw.githubusercontent.com/madsnorgaard/aabenforms/main/.github/badges/coverage.json)
```

---

## Files Modified

### New Files Created
1. `.github/workflows/ci.yml` - CI pipeline with mock services
2. `docs/CI_WITH_MOCK_SERVICES.md` - This documentation

### Configuration Used
- `.ddev/mocks/keycloak/realms/danish-gov-test.json` - 10 test users
- `.ddev/mocks/wiremock/mappings/sf1520-freja-nielsen.json` - CPR lookup stub
- `.ddev/mocks/wiremock/__files/sf1520-response-freja-nielsen.xml` - OIO response

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  GitHub Actions Runner                                      │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│  │  MariaDB     │  │  Keycloak    │  │  WireMock       │  │
│  │  :3306       │  │  :8080       │  │  :8081          │  │
│  └──────────────┘  └──────────────┘  └─────────────────┘  │
│         │                 │                    │            │
│         └─────────────────┼────────────────────┘            │
│                           │                                 │
│                    ┌──────▼───────┐                         │
│                    │  PHPUnit     │                         │
│                    │  Tests       │                         │
│                    │              │                         │
│                    │ - Unit       │                         │
│                    │ - Kernel     │                         │
│                    │ - Functional │                         │
│                    └──────────────┘                         │
│                           │                                 │
│                    ┌──────▼───────┐                         │
│                    │  Coverage    │                         │
│                    │  Report      │                         │
│                    └──────────────┘                         │
└─────────────────────────────────────────────────────────────┘
```

---

## Comparison: Local vs CI

| Aspect | Local DDEV | GitHub Actions CI |
|--------|------------|-------------------|
| **Database** | MariaDB 10.11 | Same (MariaDB 10.11) |
| **MitID Mock** | Keycloak :8080 | Same (Keycloak :8080) |
| **Serviceplatformen Mock** | WireMock :8081 | Same (WireMock :8081) |
| **Test Users** | 10 personas | Same (10 personas) |
| **Test Data** | Danish CPR/names | Same data |
| **PHP Version** | 8.4 | Same (8.4) |

**Result**: If tests pass locally, they will pass in CI (and vice versa).

---

## Coverage Targets

Targets to aim for as tests are added:
- **aabenforms_core**: foundation services and Serviceplatformen client
- **aabenforms_mitid**: authentication (security critical)
- **aabenforms_workflows**: ECA actions including CPR/CVR lookup
- **aabenforms_digital_post**: SF1601 in test/mock modes

---

## Reusing the Mock Stack

The `.ddev/mocks/` setup (Keycloak realm + WireMock mappings) is self-contained and
could be extracted into a separate repository for reuse across other Drupal projects
or open-source efforts. It is standards-based (OIDC, SOAP) so it is not tied to this
project.

---

**Status**: Working - CI runs against mock services.

**Next Action**: Write an integration test and run it with the Danish test data.

**Questions?** See `docs/DDEV_MOCK_SERVICES_GUIDE.md` for the setup guide.
