# FunctionalJavascript Tests for ÅbenForms Workflows

This directory contains end-to-end browser tests that verify the complete user journey through the approval system using a real headless browser.

## Test Files

### WorkflowApprovalE2ETest.php
**Purpose**: Tests the complete dual parent approval workflow end-to-end.

**Test Cases**:
- `testCompleteParentApprovalWorkflow()` - Full approval flow with both parents approving
- `testParentRejectionWorkflow()` - Parent rejection scenario
- `testInvalidTokenAccess()` - Invalid token handling
- `testExpiredTokenAccess()` - Expired token handling
- `testGdprDataMaskingForParentsApart()` - GDPR data masking for separated parents
- `testAlreadyProcessedSubmission()` - Prevent duplicate processing
- `testConcurrentParentApprovals()` - Concurrent parent approvals

**Coverage**:
- Webform submission
- Token generation and validation
- MitID authentication simulation
- Parent approval form
- GDPR data masking
- Workflow state transitions

### WorkflowTemplateWizardTest.php
**Purpose**: Tests the workflow template wizard interface.

**Test Cases**:
- `testWorkflowTemplateWizard()` - Complete wizard flow
- `testWizardNavigation()` - Back/Next button navigation
- `testWizardValidation()` - Form validation at each step
- `testAjaxFieldMappingUpdates()` - AJAX field mapping updates
- `testTemplatePreview()` - Template preview modal
- `testTemplateCategoryFiltering()` - Category filtering

**Coverage**:
- Multi-step wizard navigation
- AJAX-powered field mapping
- Template preview with BPMN diagram
- Category filtering
- Workflow activation

### WorkflowTemplateManagementTest.php
**Purpose**: Tests template import/export and management operations.

**Test Cases**:
- `testTemplateImport()` - Import BPMN template
- `testTemplateExport()` - Export BPMN template
- `testTemplateDeletion()` - Delete template
- `testTemplateValidation()` - Validate BPMN XML on import
- `testDuplicateTemplateId()` - Prevent duplicate template IDs
- `testTemplatePreviewInManagement()` - Preview template in management UI
- `testTemplateSearch()` - Search/filter templates
- `testTemplateEditing()` - Edit existing template

**Coverage**:
- Template CRUD operations
- BPMN XML validation
- Template search and filtering
- BPMN diagram rendering

### WorkflowDashboardTest.php
**Purpose**: Tests the case worker dashboard and task management.

**Test Cases**:
- `testDashboardDisplaysPendingTasks()` - Display pending tasks
- `testTaskFilteringByStatus()` - Filter tasks by status
- `testTaskFilteringByDateRange()` - Filter tasks by date
- `testTaskSearch()` - Search tasks
- `testTaskSorting()` - Sort task columns
- `testTaskActionButtons()` - Task action buttons
- `testBulkTaskActions()` - Bulk actions on multiple tasks
- `testTaskStatistics()` - Task statistics display
- `testTaskDetailView()` - Task detail page
- `testRealTimeDashboardUpdates()` - Real-time updates via AJAX
- `testTaskAssignment()` - Task assignment to case workers

**Coverage**:
- Dashboard display
- Task filtering and search
- Task sorting
- Bulk actions
- Task statistics
- Real-time updates

### WorkflowAjaxInteractionsTest.php
**Purpose**: Tests AJAX interactions and dynamic form behaviors.

**Test Cases**:
- `testConditionalFieldVisibility()` - Conditional field show/hide
- `testAjaxValidationMessages()` - AJAX validation messages
- `testDynamicFieldPopulation()` - Dynamic field population via AJAX
- `testApprovalConfirmationDialog()` - Confirmation dialog for rejection
- `testMultiStepFormWizard()` - Multi-step wizard with AJAX
- `testAjaxFileUpload()` - File upload with progress
- `testAjaxErrorHandling()` - AJAX error handling
- `testDebouncedAjaxSearch()` - Debounced search
- `testLiveValidation()` - Live form validation

**Coverage**:
- AJAX form interactions
- Conditional fields
- Dynamic validation
- Confirmation dialogs
- File uploads
- Debounced search

## Requirements

### System Requirements
- **Chromedriver**: Required for WebDriver tests
- **Chrome/Chromium**: Headless browser
- **Selenium**: Optional, for grid testing

### Installation

#### Install Chromedriver (DDEV)
```bash
# Install via DDEV addon
ddev get ddev/ddev-selenium-standalone-chrome

# Or manually install chromedriver
ddev exec apt-get update && apt-get install -y chromium-chromedriver
```

#### Install Chromedriver (Local)
```bash
# macOS
brew install --cask chromedriver

# Ubuntu/Debian
sudo apt-get install chromium-chromedriver

# Verify installation
chromedriver --version
```

### Configuration

Configure PHPUnit for browser tests:

**phpunit.xml**:
```xml
<phpunit>
  <php>
    <!-- URL to Drupal site -->
    <env name="SIMPLETEST_BASE_URL" value="https://aabenforms.ddev.site"/>

    <!-- Webdriver settings -->
    <env name="MINK_DRIVER_CLASS" value="Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver"/>
    <env name="MINK_DRIVER_ARGS_WEBDRIVER" value='["chrome", {"browserName":"chrome","goog:chromeOptions":{"args":["--disable-gpu","--headless","--no-sandbox"]}}, "http://localhost:9515"]'/>

    <!-- Database -->
    <env name="SIMPLETEST_DB" value="mysql://db:db@db/db"/>
  </php>
</phpunit>
```

## Running Tests

### Run All FunctionalJavascript Tests
```bash
# Via DDEV
ddev exec phpunit --group functional_javascript

# With coverage
ddev exec phpunit --group functional_javascript --coverage-html coverage/
```

### Run Specific Test Class
```bash
# Approval E2E tests
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowApprovalE2ETest.php

# Template wizard tests
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowTemplateWizardTest.php

# Dashboard tests
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowDashboardTest.php
```

### Run Specific Test Method
```bash
ddev exec phpunit --filter testCompleteParentApprovalWorkflow web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowApprovalE2ETest.php
```

### Run with Verbose Output
```bash
ddev exec phpunit --group functional_javascript --verbose --debug
```

### Run in Headed Mode (Show Browser)
To see the browser during test execution (helpful for debugging):

1. Update `phpunit.xml`:
```xml
<!-- Remove --headless from chrome args -->
<env name="MINK_DRIVER_ARGS_WEBDRIVER" value='["chrome", {"browserName":"chrome","goog:chromeOptions":{"args":["--disable-gpu","--no-sandbox"]}}, "http://localhost:9515"]'/>
```

2. Run tests:
```bash
ddev exec phpunit --filter testCompleteParentApprovalWorkflow web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowApprovalE2ETest.php
```

## Debugging Tests

### Take Screenshots on Failure
Tests can be configured to take screenshots on failure:

```php
// In test method
try {
  // Test code
} catch (\Exception $e) {
  $screenshot = $this->getSession()->getDriver()->getScreenshot();
  file_put_contents('/tmp/test-failure.png', $screenshot);
  throw $e;
}
```

### View Browser Console Logs
```php
$logs = $this->getSession()->getDriver()->getWebDriverSession()->log('browser');
foreach ($logs as $log) {
  var_dump($log);
}
```

### Add Wait Breakpoints
```php
// Wait indefinitely for manual inspection
$this->getSession()->wait(999999);
```

### Enable Verbose Mink Output
```bash
export MINK_DRIVER_ARGS_WEBDRIVER='["chrome", {"browserName":"chrome","goog:chromeOptions":{"args":["--disable-gpu","--verbose"]}}, "http://localhost:9515"]'
```

## Mocking External Services

### MitID Authentication
Tests use `mockMitIdAuthentication()` to simulate MitID login:

```php
protected function mockMitIdAuthentication(int $submission_id, int $parent_number): void {
  $tempstore = \Drupal::service('tempstore.private')->get('aabenforms_workflows');
  $tempstore->set("mitid_authenticated_parent{$parent_number}", TRUE);
  $tempstore->set('mitid_cpr', $parent_number == 1 ? '1234567890' : '0987654321');
}
```

### Serviceplatformen Services
Configure mock responses in `settings.ddev.php`:

```php
$config['aabenforms_cpr.settings']['mock_mode'] = TRUE;
$config['aabenforms_cvr.settings']['mock_mode'] = TRUE;
```

## Best Practices

### Wait Strategies
Always use explicit waits instead of fixed sleeps:

```php
// Good - Wait for specific element
$this->assertSession()->waitForElementVisible('css', '#submit-button', 10000);

// Bad - Fixed sleep
sleep(5);
```

### AJAX Assertions
Wait for AJAX to complete before asserting:

```php
// Wait for AJAX
$this->assertSession()->assertWaitOnAjaxRequest();

// Or wait for specific element
$this->assertSession()->waitForText('Success message', 10000);
```

### Clean Test Data
Always clean up in `tearDown()`:

```php
protected function tearDown(): void {
  // Delete test entities
  if ($this->testEntity) {
    $this->testEntity->delete();
  }

  parent::tearDown();
}
```

### Stable Element Selectors
Use stable CSS selectors:

```php
// Good - Use data attributes or classes
$page->find('css', '[data-test-id="submit-button"]');
$page->find('css', '.approval-form-submit');

// Bad - Brittle selectors
$page->find('css', 'div > div > button:nth-child(3)');
```

## Common Issues

### Issue: Chromedriver not found
**Solution**:
```bash
# Install chromedriver
ddev get ddev/ddev-selenium-standalone-chrome

# Or set path in phpunit.xml
<env name="MINK_DRIVER_ARGS_WEBDRIVER" value='["chrome", null, "http://chrome:4444/wd/hub"]'/>
```

### Issue: Element not found
**Solution**: Add explicit wait:
```php
$this->assertSession()->waitForElementVisible('css', '#my-element', 10000);
```

### Issue: Stale element reference
**Solution**: Re-query element after page update:
```php
$page = $this->getSession()->getPage();
$button = $page->find('css', '#submit');
```

### Issue: Test hangs indefinitely
**Solution**: Add timeout to waits:
```php
$this->assertSession()->waitForText('Expected text', 10000); // 10 second timeout
```

### Issue: Random test failures
**Solution**: Increase wait times and ensure AJAX completes:
```php
$this->assertSession()->assertWaitOnAjaxRequest();
$this->getSession()->wait(1000); // Additional buffer
```

## Performance Tips

### Run Tests in Parallel
```bash
# Install paratest
composer require --dev brianium/paratest

# Run in parallel
ddev exec paratest --functional --processes=4
```

### Use Test Database
Tests should use a separate database to avoid conflicts:

```xml
<env name="SIMPLETEST_DB" value="mysql://db:db@db/test_db"/>
```

### Disable Slow Modules
Disable unnecessary modules in test setup:

```php
protected static $modules = [
  // Only include required modules
  'system',
  'user',
  'webform',
  'aabenforms_workflows',
];
```

## Coverage Reports

Generate coverage reports for browser tests:

```bash
# Generate HTML coverage report
ddev exec phpunit --group functional_javascript --coverage-html coverage/

# View report
open coverage/index.html
```

## CI/CD Integration

### GitHub Actions Example
```yaml
jobs:
  functional-javascript-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Install Chrome
        run: |
          sudo apt-get update
          sudo apt-get install -y chromium-browser chromium-chromedriver

      - name: Run FunctionalJavascript tests
        run: |
          vendor/bin/phpunit --group functional_javascript
```

## Additional Resources

- [Drupal FunctionalJavascript Testing](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal/functional-javascript-testing)
- [WebDriver Documentation](https://www.selenium.dev/documentation/webdriver/)
- [Mink Documentation](http://mink.behat.org/)
- [Chromedriver Documentation](https://chromedriver.chromium.org/)

---

**Total Test Coverage**:
- 5 test classes
- 40+ test methods
- End-to-end workflows
- AJAX interactions
- Template management
- Dashboard functionality
