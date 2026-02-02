# Testing Guide

## Overview

ÅbenForms backend includes comprehensive automated testing across unit, kernel, and integration test levels. This guide covers running tests, writing new tests, and understanding the testing infrastructure.

**Current Test Metrics** (Phase 3):
- **Total Tests**: 156 tests
- **Coverage**: 45%
- **Test Types**: Unit (68), Kernel (52), Integration (36)

---

## Running Tests

### Prerequisites

Ensure DDEV environment is running:
```bash
cd /home/mno/ddev-projects/aabenforms/backend
ddev start
```

### Run All Tests

```bash
# Run full test suite (156 tests)
ddev exec phpunit --configuration phpunit.xml

# Run with coverage report (HTML format)
ddev exec phpunit --configuration phpunit.xml --coverage-html coverage/

# View coverage report
ddev launch coverage/index.html
```

### Run Specific Test Types

#### Unit Tests (68 tests)
Tests individual classes in isolation with mocked dependencies:
```bash
ddev exec phpunit --configuration phpunit.xml --testsuite unit
```

#### Kernel Tests (52 tests)
Tests with Drupal services available but no database:
```bash
ddev exec phpunit --configuration phpunit.xml --testsuite kernel
```

#### Integration Tests (36 tests)
Tests with full Drupal installation and WireMock for external services:
```bash
ddev exec phpunit --configuration phpunit.xml --testsuite integration
```

### Run Specific Test Files

```bash
# Run single test file
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/Unit/BpmnTemplateManagerTest.php

# Run specific test method
ddev exec phpunit --filter testValidateTemplate \
  web/modules/custom/aabenforms_workflows/tests/src/Unit/BpmnTemplateManagerTest.php
```

### Run Tests by Module

```bash
# Test aabenforms_workflows module
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/

# Test aabenforms_core module
ddev exec phpunit web/modules/custom/aabenforms_core/tests/

# Test aabenforms_tenant module
ddev exec phpunit web/modules/custom/aabenforms_tenant/tests/
```

---

## Test Directory Structure

```
web/modules/custom/
├── aabenforms_core/
│   └── tests/
│       ├── src/
│       │   ├── Unit/              # Unit tests (isolated)
│       │   ├── Kernel/            # Kernel tests (with services)
│       │   └── Functional/        # Browser tests (with UI)
│       └── fixtures/              # Test data files
│
├── aabenforms_workflows/
│   └── tests/
│       ├── src/
│       │   ├── Unit/
│       │   │   ├── BpmnTemplateManagerTest.php
│       │   │   └── Service/
│       │   ├── Kernel/
│       │   │   ├── MitIdValidateActionTest.php
│       │   │   ├── CprLookupActionTest.php
│       │   │   ├── CvrLookupActionTest.php
│       │   │   └── AuditLogActionTest.php
│       │   └── Functional/
│       │       ├── WorkflowExecutionTest.php
│       │       └── TemplateSelectFormTest.php
│       └── fixtures/
│           ├── building_permit.bpmn
│           ├── contact_form.bpmn
│           └── mock_responses/
│
└── aabenforms_tenant/
    └── tests/
        ├── src/
        │   ├── Unit/
        │   ├── Kernel/
        │   └── Functional/
        │       └── TenantResolutionTest.php
        └── fixtures/
```

---

## Writing Tests

### Unit Test Example

Unit tests test individual methods in isolation using mocked dependencies:

```php
<?php

namespace Drupal\Tests\aabenforms_workflows\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;

/**
 * Tests for BpmnTemplateManager service.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
 */
class BpmnTemplateManagerTest extends UnitTestCase {

  /**
   * The BPMN template manager.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
   */
  protected $templateManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock dependencies
    $fileSystem = $this->createMock('Drupal\Core\File\FileSystemInterface');
    $moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');

    // Create service instance with mocks
    $this->templateManager = new BpmnTemplateManager(
      $fileSystem,
      $moduleHandler
    );
  }

  /**
   * Tests getAvailableTemplates() method.
   *
   * @covers ::getAvailableTemplates
   */
  public function testGetAvailableTemplates() {
    $templates = $this->templateManager->getAvailableTemplates();

    $this->assertIsArray($templates);
    $this->assertContains('building_permit', $templates);
    $this->assertContains('contact_form', $templates);
    $this->assertCount(5, $templates);
  }

  /**
   * Tests validateTemplate() with valid BPMN XML.
   *
   * @covers ::validateTemplate
   */
  public function testValidateTemplateValid() {
    $xml = '<?xml version="1.0"?>
      <bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL">
        <bpmn:process id="Process_1">
          <bpmn:startEvent id="StartEvent_1"/>
          <bpmn:endEvent id="EndEvent_1"/>
        </bpmn:process>
      </bpmn:definitions>';

    $result = $this->templateManager->validateTemplate($xml);
    $this->assertTrue($result);
  }

  /**
   * Tests validateTemplate() with invalid XML.
   *
   * @covers ::validateTemplate
   */
  public function testValidateTemplateInvalid() {
    $xml = 'Not valid XML';

    $result = $this->templateManager->validateTemplate($xml);
    $this->assertFalse($result);

    $errors = $this->templateManager->getValidationErrors();
    $this->assertNotEmpty($errors);
  }

}
```

**Key Points**:
- Extend `Drupal\Tests\UnitTestCase`
- Mock all dependencies in `setUp()`
- Use `@covers` annotations for code coverage
- Test both success and failure cases

---

### Kernel Test Example

Kernel tests have access to Drupal services but no database:

```php
<?php

namespace Drupal\Tests\aabenforms_workflows\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests MitID validate action plugin.
 *
 * @group aabenforms_workflows
 * @coversDefaultClass \Drupal\aabenforms_workflows\Plugin\Action\MitIdValidateAction
 */
class MitIdValidateActionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'aabenforms_core',
    'aabenforms_workflows',
    'eca',
  ];

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['system']);

    $this->actionManager = $this->container->get('plugin.manager.action');
  }

  /**
   * Tests plugin is discoverable.
   *
   * @covers ::create
   */
  public function testPluginDiscovery() {
    $definitions = $this->actionManager->getDefinitions();
    $this->assertArrayHasKey('aabenforms_mitid_validate', $definitions);

    $definition = $definitions['aabenforms_mitid_validate'];
    $this->assertEquals('Validate MitID Token', $definition['label']);
  }

  /**
   * Tests action execution with valid token.
   *
   * @covers ::execute
   */
  public function testExecuteValidToken() {
    $action = $this->actionManager->createInstance('aabenforms_mitid_validate', [
      'token' => 'valid_test_token',
      'level' => 'SUBSTANTIAL',
    ]);

    // Mock MitID service
    $mitidService = $this->createMock('Drupal\aabenforms_mitid\Service\MitIdService');
    $mitidService->expects($this->once())
      ->method('validateToken')
      ->willReturn([
        'cpr' => '1234567890',
        'name' => 'Test Person',
        'level' => 'SUBSTANTIAL',
      ]);

    $action->setMitIdService($mitidService);

    $result = $action->execute();
    $this->assertEquals('1234567890', $result['cpr']);
  }

}
```

**Key Points**:
- Extend `Drupal\KernelTests\KernelTestBase`
- Declare required modules in `$modules`
- Install schemas and configs in `setUp()`
- Can use real Drupal services

---

### Integration Test Example

Integration tests use WireMock for external service mocking:

```php
<?php

namespace Drupal\Tests\aabenforms_workflows\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests complete workflow execution.
 *
 * @group aabenforms_workflows
 */
class WorkflowExecutionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'webform',
    'eca',
    'aabenforms_core',
    'aabenforms_workflows',
    'aabenforms_mitid',
    'aabenforms_cpr',
  ];

  /**
   * WireMock base URL.
   *
   * @var string
   */
  protected $wiremockUrl = 'http://wiremock:8080';

  /**
   * Tests building permit workflow end-to-end.
   */
  public function testBuildingPermitWorkflow() {
    // Setup WireMock stub for MitID validation
    $this->setupWireMockStub('/mitid/validate', [
      'cpr' => '1234567890',
      'name' => 'Test Citizen',
      'level' => 'SUBSTANTIAL',
    ]);

    // Setup WireMock stub for CPR lookup
    $this->setupWireMockStub('/cpr/lookup', [
      'name' => 'Test Citizen',
      'address' => 'Test Street 1, 8000 Aarhus C',
    ]);

    // Create test user
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);

    // Submit webform
    $this->drupalGet('/webform/building_permit');
    $this->submitForm([
      'mitid_token' => 'test_token',
      'description' => 'Build garage',
    ], 'Submit');

    // Assert workflow executed
    $this->assertSession()->pageTextContains('Application submitted');

    // Verify audit log entry created
    $logs = \Drupal::database()
      ->select('aabenforms_audit_log', 'a')
      ->fields('a')
      ->condition('action', 'cpr_lookup')
      ->execute()
      ->fetchAll();

    $this->assertCount(1, $logs);
    $this->assertEquals('1234567890', $logs[0]->metadata['cpr']);
  }

  /**
   * Setup WireMock stub.
   */
  protected function setupWireMockStub($path, $response) {
    $client = \Drupal::httpClient();
    $client->post($this->wiremockUrl . '/__admin/mappings', [
      'json' => [
        'request' => [
          'method' => 'POST',
          'url' => $path,
        ],
        'response' => [
          'status' => 200,
          'jsonBody' => $response,
        ],
      ],
    ]);
  }

}
```

**Key Points**:
- Extend `Drupal\Tests\BrowserTestBase`
- Use WireMock for external API mocking
- Test complete user workflows
- Verify database state changes

---

## Mock Services

### WireMock Patterns

ÅbenForms uses WireMock (via DDEV service) to mock Danish government services:

#### MitID Mock
```json
{
  "request": {
    "method": "POST",
    "urlPath": "/mitid/validate",
    "bodyPatterns": [
      {"matchesJsonPath": "$.token"}
    ]
  },
  "response": {
    "status": 200,
    "jsonBody": {
      "cpr": "1234567890",
      "name": "Test Person",
      "level": "SUBSTANTIAL",
      "validated_at": "2026-02-01T10:00:00Z"
    }
  }
}
```

#### CPR Lookup Mock (SF1520)
```json
{
  "request": {
    "method": "POST",
    "urlPath": "/serviceplatformen/cpr",
    "bodyPatterns": [
      {"matchesJsonPath": "$.cpr"}
    ]
  },
  "response": {
    "status": 200,
    "xmlBody": "<PersonLookupResponse>
      <Name>Test Person</Name>
      <Address>Test Street 1, 8000 Aarhus C</Address>
    </PersonLookupResponse>"
  }
}
```

#### CVR Lookup Mock (SF1530)
```json
{
  "request": {
    "method": "POST",
    "urlPath": "/serviceplatformen/cvr",
    "bodyPatterns": [
      {"matchesJsonPath": "$.cvr"}
    ]
  },
  "response": {
    "status": 200,
    "xmlBody": "<CompanyLookupResponse>
      <Name>Test Company A/S</Name>
      <Status>ACTIVE</Status>
      <IndustryCode>620100</IndustryCode>
    </CompanyLookupResponse>"
  }
}
```

### Managing WireMock

```bash
# Start WireMock (included in DDEV)
ddev start

# Reset all stubs
ddev exec curl -X DELETE http://wiremock:8080/__admin/mappings

# List active stubs
ddev exec curl http://wiremock:8080/__admin/mappings | jq

# View request history
ddev exec curl http://wiremock:8080/__admin/requests | jq
```

---

## Coverage Reporting

### Generate Coverage Report

```bash
# HTML report (browsable)
ddev exec phpunit --configuration phpunit.xml --coverage-html coverage/

# Clover XML (for CI)
ddev exec phpunit --configuration phpunit.xml --coverage-clover coverage.xml

# Text summary (terminal)
ddev exec phpunit --configuration phpunit.xml --coverage-text
```

### View Coverage in Browser

```bash
ddev launch coverage/index.html
```

### Coverage Thresholds

PHPUnit is configured with minimum coverage thresholds in `phpunit.xml`:

```xml
<coverage processUncoveredFiles="true">
  <report>
    <html outputDirectory="coverage"/>
  </report>
</coverage>
```

**Current Coverage** (Phase 3):
- **Overall**: 45%
- **aabenforms_workflows**: 52%
- **aabenforms_core**: 41%
- **aabenforms_tenant**: 38%

**Target Coverage** (Phase 4):
- **Overall**: 60%
- **Critical modules**: 70%+

---

## Continuous Integration

### GitHub Actions Workflow

Tests run automatically on every push/PR via `.github/workflows/ci.yml`:

```yaml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4

      - name: Install dependencies
        run: composer install

      - name: Run PHPUnit
        run: vendor/bin/phpunit --coverage-clover coverage.xml

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
```

### Viewing CI Results

- **GitHub Actions**: https://github.com/madsnorgaard/aabenforms/actions
- **Coverage Badge**: ![Coverage](https://img.shields.io/badge/coverage-45%25-yellow)
- **Test Badge**: ![Tests](https://img.shields.io/badge/tests-156%20passing-brightgreen)

---

## Debugging Tests

### Enable Verbose Output

```bash
# Show detailed test output
ddev exec phpunit --configuration phpunit.xml --verbose

# Show debug information
ddev exec phpunit --configuration phpunit.xml --debug
```

### Debug Specific Test

```bash
# Add var_dump() or dump() in test code
public function testSomething() {
  $result = $this->someMethod();
  var_dump($result); // Will show in test output
  $this->assertTrue($result);
}
```

### Use Xdebug

```bash
# Enable Xdebug in DDEV
ddev xdebug on

# Run tests with Xdebug
ddev exec phpunit --configuration phpunit.xml

# Disable Xdebug
ddev xdebug off
```

### View Test Database

```bash
# Connect to test database
ddev mysql

# Query audit logs
SELECT * FROM aabenforms_audit_log ORDER BY timestamp DESC LIMIT 10;
```

---

## Best Practices

### 1. Test Naming Conventions

- **Unit Test**: `testMethodName()`
- **Integration Test**: `testFeatureName()`
- **Failure Case**: `testMethodNameWithInvalidInput()`

Examples:
```php
public function testValidateTemplateValid() { }
public function testValidateTemplateInvalid() { }
public function testBuildingPermitWorkflowSuccess() { }
```

### 2. Arrange-Act-Assert Pattern

```php
public function testSomething() {
  // Arrange: Setup test data
  $input = ['key' => 'value'];

  // Act: Execute method
  $result = $this->service->process($input);

  // Assert: Verify outcome
  $this->assertEquals('expected', $result);
}
```

### 3. Use Data Providers

For testing multiple inputs:
```php
/**
 * @dataProvider providerValidationCases
 */
public function testValidation($input, $expected) {
  $result = $this->validator->validate($input);
  $this->assertEquals($expected, $result);
}

public function providerValidationCases() {
  return [
    'valid_cpr' => ['1234567890', TRUE],
    'invalid_cpr' => ['invalid', FALSE],
    'empty_cpr' => ['', FALSE],
  ];
}
```

### 4. Clean Up Test Data

```php
protected function tearDown(): void {
  // Delete test files
  file_unmanaged_delete($this->testFile);

  // Reset WireMock
  $this->resetWireMock();

  parent::tearDown();
}
```

### 5. Test Edge Cases

- Empty inputs
- Null values
- Very long strings
- Special characters
- Concurrent requests
- Network timeouts

---

## Common Issues

### Issue: "Class not found" in Tests

**Solution**: Rebuild autoloader
```bash
ddev composer dump-autoload
```

### Issue: Tests Fail Locally but Pass in CI

**Solution**: Ensure DDEV environment matches CI
```bash
ddev restart
ddev exec composer install
```

### Issue: WireMock Not Responding

**Solution**: Restart DDEV services
```bash
ddev restart
ddev exec curl http://wiremock:8080/__admin/health
```

### Issue: Coverage Report Empty

**Solution**: Ensure Xdebug is enabled
```bash
ddev xdebug on
ddev exec phpunit --coverage-html coverage/
```

---

## Additional Resources

- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **Drupal Testing Guide**: https://www.drupal.org/docs/testing
- **WireMock Documentation**: https://wiremock.org/docs/
- **DDEV Testing Guide**: https://ddev.readthedocs.io/en/stable/users/topics/testing/

---

**Last Updated**: 2026-02-01 (Phase 3 completion - 156 tests, 45% coverage)
