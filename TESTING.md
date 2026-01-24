# ÅbenForms Testing Guide

## Quick Start

```bash
# Update local environment after pulling changes
ddev local_update

# Run all tests
ddev test

# Run specific test suite
ddev test --testsuite=unit
ddev test --testsuite=kernel

# Run tests for specific module
ddev test --group aabenforms_core

# Run with coverage report
ddev test-coverage
# Then open: backend/coverage/index.html
```

## Prerequisites

- DDEV environment running: `ddev start`
- Dependencies installed: `ddev composer install`

## DDEV Commands

### Local Update
After pulling code changes from Git, run the local update script:

```bash
# Update main site
ddev local_update

# Update specific tenant (when multi-tenancy is configured)
ddev local_update aarhus.aabenforms.ddev.site

# Update all sites
ddev local_update all
```

This command:
1. Installs/updates Composer dependencies
2. Clears Drupal cache
3. Runs database updates
4. Imports configuration changes
5. Runs deployment hooks
6. Clears cache again

### Testing Commands
```bash
# Run tests
ddev test [phpunit-args]

# Generate coverage report
ddev test-coverage
```

## Test Structure

```
web/modules/custom/aabenforms_core/
└── tests/
    ├── src/
    │   ├── Unit/          # Fast, isolated PHP tests (no Drupal)
    │   └── Kernel/        # Lightweight Drupal integration tests
    └── fixtures/          # Mock data for tests
```

## Test Types

### Unit Tests
**Location**: `tests/src/Unit/`
**Speed**: Very fast (milliseconds)
**Use for**: Pure PHP logic, services, utilities

**Example**:
```php
<?php
namespace Drupal\Tests\aabenforms_core\Unit\Services;

use Drupal\Tests\UnitTestCase;

class MyServiceTest extends UnitTestCase {
  public function testMyFunction() {
    $this->assertEquals('expected', 'expected');
  }
}
```

### Kernel Tests
**Location**: `tests/src/Kernel/`
**Speed**: Fast (seconds)
**Use for**: Entity operations, database queries, module integration

**Example**:
```php
<?php
namespace Drupal\Tests\aabenforms_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

class MyKernelTest extends KernelTestBase {
  protected static $modules = ['system', 'user', 'aabenforms_core'];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  public function testSomething() {
    $moduleHandler = \Drupal::moduleHandler();
    $this->assertTrue($moduleHandler->moduleExists('aabenforms_core'));
  }
}
```

### Functional Tests (Future)
**Location**: `tests/src/Functional/`
**Speed**: Slow (minutes)
**Use for**: Full browser workflows, form submissions

## Running Tests Locally

### All Tests
```bash
ddev test
```

### By Test Suite
```bash
# Unit tests only (fastest)
ddev test --testsuite=unit

# Kernel tests only
ddev test --testsuite=kernel

# Functional tests only
ddev test --testsuite=functional
```

### By Module/Group
```bash
# All aabenforms_core tests
ddev test --group aabenforms_core

# All aabenforms_workflows tests
ddev test --group aabenforms_workflows

# BPMN-specific tests
ddev test --group bpmn
```

### Specific Test File
```bash
ddev test web/modules/custom/aabenforms_core/tests/src/Unit/Services/PlatformUtilitiesTest.php
```

### With Coverage
```bash
# Generate HTML coverage report
ddev test-coverage

# View report
# Open: backend/coverage/index.html in your browser
```

## Writing New Tests

### 1. Choose Test Type
- **Unit**: Pure logic, no Drupal dependencies
- **Kernel**: Needs database, entities, or services
- **Functional**: Full user workflows (future)

**Rule of thumb**: Prefer faster tests (Unit > Kernel > Functional)

### 2. Create Test File

**File naming**:
- Class: `MyServiceTest.php`
- Namespace: `Drupal\Tests\{module}\{Unit|Kernel|Functional}\{Subfolder}`

**Example structure**:
```
web/modules/custom/aabenforms_mitid/
└── tests/
    └── src/
        ├── Unit/
        │   └── Services/
        │       └── MitIdAuthenticatorTest.php
        └── Kernel/
            └── MitIdIntegrationTest.php
```

### 3. Use Test Fixtures

Store mock data in `tests/fixtures/`:

```php
protected function loadFixture(string $filename): string {
  $path = __DIR__ . '/../../fixtures/' . $filename;
  return file_get_contents($path);
}

public function testCprLookup() {
  $mockResponse = $this->loadFixture('serviceplatformen/cpr_lookup_response.json');
  // Use $mockResponse in your test
}
```

### 4. Tag Your Tests

```php
/**
 * Tests MitID authentication.
 *
 * @group aabenforms_mitid
 * @group authentication
 */
class MitIdAuthenticatorTest extends UnitTestCase {
  // ...
}
```

Run tagged tests:
```bash
ddev test --group authentication
```

## Code Quality Checks

### PHP CodeSniffer (Drupal Coding Standards)
```bash
# Check all custom modules
ddev exec vendor/bin/phpcs --standard=Drupal web/modules/custom

# Check specific module
ddev exec vendor/bin/phpcs --standard=Drupal web/modules/custom/aabenforms_core

# Auto-fix issues
ddev exec vendor/bin/phpcbf --standard=Drupal web/modules/custom/aabenforms_core
```

### PHPStan (Static Analysis)
```bash
# Analyze all custom modules
ddev exec vendor/bin/phpstan analyse web/modules/custom --level=6

# Analyze specific module
ddev exec vendor/bin/phpstan analyse web/modules/custom/aabenforms_core --level=6
```

### Composer Validation
```bash
# Validate composer.json
ddev composer validate --strict
```

## Continuous Integration (CI)

Every pull request automatically runs:

1. **Composer Validation** - Ensures dependencies are valid
2. **PHPUnit Tests** - Unit and Kernel test suites
3. **PHPCS** - Drupal coding standards
4. **PHPStan** - Static analysis (level 6)

**View CI results**: Check the "Actions" tab on GitHub

### CI Configuration Files
- `.github/workflows/ci.yml` - Main test pipeline
- `.github/workflows/coding-standards.yml` - Code quality
- `.github/workflows/security.yml` - Security audits (weekly)

## Debugging Tests

### Enable Verbose Output
```bash
ddev test --testdox --verbose
```

### Run Single Test Method
```bash
ddev test --filter testMySpecificMethod
```

### Debug with dump()
```php
public function testSomething() {
  $value = ['foo' => 'bar'];
  dump($value);  // Will print to console
  $this->assertTrue(TRUE);
}
```

### Check Test Database
```bash
# Tests use a separate database: drupal_test
ddev mysql -e "USE drupal_test; SHOW TABLES;"
```

## Common Issues

### "Class not found" errors
```bash
# Rebuild autoloader
ddev composer dump-autoload
```

### Database connection errors
```bash
# Restart DDEV
ddev restart
```

### Browser output directory errors
```bash
# Create directory
ddev exec mkdir -p /tmp/browser_output && ddev exec chmod 777 /tmp/browser_output
```

### Tests hang or timeout
```bash
# Increase PHP memory limit in phpunit.xml
# Already configured to unlimited: memory_limit = -1
```

## Test Coverage Goals

| Module | Target Coverage | Status |
|--------|----------------|--------|
| aabenforms_core | 70% | 🟡 In Progress |
| aabenforms_tenant | 70% | 🟡 In Progress |
| aabenforms_workflows | 60% | 🟡 In Progress |
| aabenforms_mitid (future) | 80% | 📋 Planned |
| aabenforms_gdpr (future) | 80% | 📋 Planned |

## Testing Danish Integration Modules

### Mock Serviceplatformen Responses

For modules integrating with Danish government APIs (MitID, CPR, CVR):

**Use fixtures instead of real API calls**:

```php
// tests/fixtures/serviceplatformen/cpr_lookup_response.json
{
  "PersonBaseDataExtended": {
    "PersonGivenName": "Test",
    "PersonSurnameName": "Testesen",
    "PersonCprNumber": "0101001234"
  }
}
```

**In tests**:
```php
protected function createMockServiceplatformenResponse(): Response {
  $fixture = $this->loadFixture('serviceplatformen/cpr_lookup_response.json');
  return new Response(200, ['Content-Type' => 'application/json'], $fixture);
}
```

### Integration Tests (Optional)

For testing with **real** Danish APIs (requires test credentials):

```php
/**
 * Tests real MitID authentication.
 *
 * @group integration
 * @group mitid
 * @requires env MITID_TEST_ENABLED
 */
class MitIdIntegrationTest extends FunctionalTestBase {
  public function testRealMitIdLogin() {
    // Only runs if MITID_TEST_ENABLED=1 environment variable is set
  }
}
```

Run integration tests:
```bash
MITID_TEST_ENABLED=1 ddev test --group integration
```

**Test credentials**: Available from https://pp.mitid.dk/test-tool/

## Best Practices

### 1. Keep Tests Fast
- Prefer Unit > Kernel > Functional
- Mock external APIs
- Use fixtures for data

### 2. One Assertion Per Test
```php
// Good
public function testUserCreation() {
  $user = $this->createUser();
  $this->assertNotNull($user->id());
}

public function testUserName() {
  $user = $this->createUser(['name' => 'Test']);
  $this->assertEquals('Test', $user->getAccountName());
}

// Avoid
public function testUser() {
  $user = $this->createUser(['name' => 'Test']);
  $this->assertNotNull($user->id());  // Multiple assertions
  $this->assertEquals('Test', $user->getAccountName());
}
```

### 3. Descriptive Test Names
```php
// Good
public function testCprNumberIsEncryptedAfterSubmission() { }

// Avoid
public function testEncryption() { }
```

### 4. Clean Up After Tests
```php
protected function tearDown(): void {
  // Clean up any created entities, files, etc.
  parent::tearDown();
}
```

### 5. Use Data Providers
```php
/**
 * @dataProvider invalidCprProvider
 */
public function testInvalidCprValidation($cpr) {
  $this->assertFalse($this->validator->isValid($cpr));
}

public function invalidCprProvider() {
  return [
    ['123'],
    ['abc123'],
    ['0000000000'],
  ];
}
```

## Resources

- [Drupal Testing Documentation](https://www.drupal.org/docs/automated-testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [ECA Module Tests](backend/web/modules/contrib/eca/tests/)
- [Webform Module Tests](backend/web/modules/contrib/webform/tests/)

## Questions?

- Check existing tests in contrib modules for examples
- Review PHPUnit documentation
- Ask in team chat or create a GitHub issue

---

**Last Updated**: 2026-01-24
**Maintained By**: ÅbenForms Development Team
