# FunctionalJavascript Tests - Quick Reference Card

##  Quick Start

### Install Chromedriver
```bash
ddev get ddev/ddev-selenium-standalone-chrome
ddev restart
```

### Run All Tests
```bash
ddev exec phpunit --group functional_javascript
```

## [TODO] Test Commands Cheat Sheet

### Run Specific Test Files
```bash
# Approval workflow
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowApprovalE2ETest.php

# Template wizard
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowTemplateWizardTest.php

# Dashboard
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowDashboardTest.php

# Template management
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowTemplateManagementTest.php

# AJAX interactions
ddev exec phpunit web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowAjaxInteractionsTest.php
```

### Run Specific Test Methods
```bash
# Complete approval workflow
ddev exec phpunit --filter testCompleteParentApprovalWorkflow web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowApprovalE2ETest.php

# GDPR masking test
ddev exec phpunit --filter testGdprDataMaskingForParentsApart web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowApprovalE2ETest.php

# Wizard flow
ddev exec phpunit --filter testWorkflowTemplateWizard web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/WorkflowTemplateWizardTest.php
```

### Run with Options
```bash
# Verbose output
ddev exec phpunit --group functional_javascript --verbose

# With coverage
ddev exec phpunit --group functional_javascript --coverage-html coverage/

# With debugging
ddev exec phpunit --group functional_javascript --debug

# Stop on first failure
ddev exec phpunit --group functional_javascript --stop-on-failure
```

## 🧪 Test Coverage Map

| Test File | Tests | Coverage |
|-----------|-------|----------|
| **WorkflowApprovalE2ETest** | 8 | Approval workflow, tokens, GDPR |
| **WorkflowTemplateWizardTest** | 6 | Multi-step wizard, AJAX mapping |
| **WorkflowTemplateManagementTest** | 8 | Template CRUD, BPMN validation |
| **WorkflowDashboardTest** | 11 | Dashboard, filtering, task mgmt |
| **WorkflowAjaxInteractionsTest** | 10 | AJAX, conditional fields, uploads |
| **TOTAL** | **43** | - |

## [TARGET] Key Test Scenarios

### Approval Workflow
```bash
# Complete approval flow (both parents approve)
--filter testCompleteParentApprovalWorkflow

# Rejection scenario
--filter testParentRejectionWorkflow

# Token security
--filter testInvalidTokenAccess
--filter testExpiredTokenAccess

# GDPR compliance
--filter testGdprDataMaskingForParentsApart
```

### Template Wizard
```bash
# Complete wizard flow
--filter testWorkflowTemplateWizard

# Navigation
--filter testWizardNavigation

# Field mapping
--filter testAjaxFieldMappingUpdates
```

### Dashboard
```bash
# Task display
--filter testDashboardDisplaysPendingTasks

# Filtering
--filter testTaskFilteringByStatus
--filter testTaskFilteringByDateRange
--filter testTaskSearch

# Bulk actions
--filter testBulkTaskActions
```

##  Debugging Tips

### Take Screenshot on Failure
```php
try {
  // test code
} catch (\Exception $e) {
  $screenshot = $this->getSession()->getDriver()->getScreenshot();
  file_put_contents('/tmp/failure.png', $screenshot);
  throw $e;
}
```

### View Browser Console
```php
$logs = $this->getSession()->getDriver()->getWebDriverSession()->log('browser');
print_r($logs);
```

### Add Wait Breakpoint
```php
// Wait for manual inspection
$this->getSession()->wait(999999);
```

### Run in Headed Mode
Edit `phpunit.xml` - remove `--headless` from chrome args:
```xml
<env name="MINK_DRIVER_ARGS_WEBDRIVER" value='["chrome", {"browserName":"chrome","goog:chromeOptions":{"args":["--disable-gpu","--no-sandbox"]}}, "http://localhost:9515"]'/>
```

##  Common Assertions

```php
// Page status
$this->assertSession()->statusCodeEquals(200);

// Text presence
$this->assertSession()->pageTextContains('Expected text');
$this->assertSession()->pageTextNotContains('Unwanted text');

// Field checks
$this->assertSession()->fieldExists('field_name');
$this->assertSession()->fieldNotExists('field_name');
$this->assertSession()->fieldValueEquals('field_name', 'value');

// Element visibility
$this->assertSession()->elementExists('css', '.class-name');
$this->assertSession()->elementNotExists('css', '.class-name');

// Wait for elements
$this->assertSession()->waitForElementVisible('css', '.element', 10000);
$this->assertSession()->waitForText('Text', 10000);
$this->assertSession()->waitForElementRemoved('css', '.element', 10000);

// AJAX waits
$this->assertSession()->assertWaitOnAjaxRequest();
```

## 🐛 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Chromedriver not found | `ddev get ddev/ddev-selenium-standalone-chrome` |
| Element not found | Add explicit wait: `waitForElementVisible()` |
| Stale element | Re-query element after page update |
| Test hangs | Add timeout to wait: `waitForText('text', 10000)` |
| Random failures | Increase wait times, ensure AJAX completes |

## 📁 File Locations

```
web/modules/custom/aabenforms_workflows/tests/src/FunctionalJavascript/
├── WorkflowApprovalE2ETest.php         # Approval workflow E2E
├── WorkflowTemplateWizardTest.php      # Template wizard
├── WorkflowTemplateManagementTest.php  # Template CRUD
├── WorkflowDashboardTest.php           # Dashboard & tasks
├── WorkflowAjaxInteractionsTest.php    # AJAX interactions
├── README.md                           # Full documentation
└── QUICK_REFERENCE.md                  # This file
```

## 🎓 Learning Resources

- [Drupal FunctionalJavascript Testing](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal/functional-javascript-testing)
- [WebDriver Documentation](https://www.selenium.dev/documentation/webdriver/)
- [Mink Documentation](http://mink.behat.org/)
- [Chromedriver Docs](https://chromedriver.chromium.org/)

## [PASS] Pre-flight Checklist

Before running tests:

- [ ] Chromedriver installed
- [ ] DDEV running (`ddev start`)
- [ ] Database accessible
- [ ] Webform module enabled
- [ ] ECA module enabled
- [ ] aabenforms_workflows module enabled
- [ ] Test database configured in phpunit.xml

## 📈 Coverage Goals

- **Current**: 43 tests created
- **Target**: All tests passing
- **Coverage**: 60%+ code coverage

## 🚨 Known Limitations

- Some routes may not exist yet (dashboard, wizard)
- Controllers need implementation
- AJAX endpoints may need creation
- MitID mocking requires configuration

---

**Quick Help**: For detailed documentation, see `README.md` in this directory.
