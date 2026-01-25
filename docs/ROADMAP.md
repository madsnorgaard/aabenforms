# ÅbenForms - Next Steps & Priorities

**Updated**: 2026-01-27 10:15 UTC
**Current Sprint**: Week 2 (Testing Coverage)
**Phase**: Phase 2 - Authentication & Security

---

## 🎯 Immediate Priorities (This Week)

### 1. Complete Core Service Tests (PRIORITY HIGH)

**Goal**: Bring aabenforms_core from 5% to 70%+ coverage

**Tasks** (8-12 hours total):

#### TenantResolver Tests (~2 hours)
```bash
File: web/modules/custom/aabenforms_core/tests/src/Unit/Service/TenantResolverTest.php

Tests to write (6 tests):
✅ testGetTenantIdFromDomain()
✅ testGetTenantNameFromConfig()
✅ testGetCurrentTenant()
✅ testGetTenantIdForInvalidDomain()
✅ testGetTenantConfigWithMissingDomain()
✅ testGetDefaultTenantId()
```

#### EncryptionService Tests (~3 hours)
```bash
File: web/modules/custom/aabenforms_core/tests/src/Unit/Service/EncryptionServiceTest.php

Tests to write (8 tests):
✅ testEncryptField()
✅ testDecryptField()
✅ testEncryptionRoundTrip()
✅ testEncryptWithInvalidProfile()
✅ testDecryptWithMissingKey()
✅ testEncryptEmptyValue()
✅ testEncryptNullValue()
✅ testEncryptLongValue()
```

#### AuditLogger Tests (~3 hours)
```bash
File: web/modules/custom/aabenforms_core/tests/src/Unit/Service/AuditLoggerTest.php

Tests to write (10 tests):
✅ testLogCprLookup()
✅ testLogCvrLookup()
✅ testLogWorkflowAccess()
✅ testLogWithDifferentSeverities()
✅ testLogWithTenantContext()
✅ testLogWithoutTenant()
✅ testLogWithAdditionalContext()
✅ testRetrieveAuditLogsByCpr()
✅ testRetrieveAuditLogsByWorkflow()
✅ testRetrieveAuditLogsByDateRange()
```

#### ServiceplatformenClient Tests (~4 hours)
```bash
File: web/modules/custom/aabenforms_core/tests/src/Unit/Service/ServiceplatformenClientTest.php

Tests to write (8 tests):
✅ testBuildSoapEnvelopeCPR()
✅ testBuildSoapEnvelopeCVR()
✅ testBuildSoapEnvelopeDigitalPost()
✅ testParseSOAPResponse()
✅ testParseSOAPResponseWithFault()
✅ testAddAuthenticationHeaders()
✅ testHandleNetworkError()
✅ testHandleTimeout()
```

**Deliverable**: 32 new tests, aabenforms_core at 70%+ coverage

---

### 2. Complete Webform Tests (PRIORITY MEDIUM)

**Goal**: Bring aabenforms_webform from 0% to 60%+ coverage

**Tasks** (6-8 hours total):

#### CprValidator Tests (~4 hours)
```bash
File: web/modules/custom/aabenforms_webform/tests/src/Unit/Service/CprValidatorTest.php

Tests to write (15 tests):
✅ testIsValidWithValidCpr()
✅ testIsValidWithInvalidDate()
✅ testIsValidWithInvalidMonth()
✅ testIsValidWithInvalidDay()
✅ testIsValidWithInvalidChecksum()
✅ testIsValidWithHyphenatedCpr()
✅ testGetBirthdate()
✅ testGetBirthdateFrom1800sRange()
✅ testGetBirthdateFrom2000sRange()
✅ testGetGenderMale()
✅ testGetGenderFemale()
✅ testCleanCpr()
✅ testValidateModulus11()
✅ testValidateInvalidLength()
✅ testValidateNonNumeric()
```

#### CprField Element Tests (~3 hours)
```bash
File: web/modules/custom/aabenforms_webform/tests/src/Kernel/Plugin/WebformElement/CprFieldTest.php

Tests to write (8 tests):
✅ testElementDefinition()
✅ testValidationWithValidCpr()
✅ testValidationWithInvalidCpr()
✅ testMaskedDisplayEnabled()
✅ testMaskedDisplayDisabled()
✅ testErrorMessages()
✅ testFormatHtmlItem()
✅ testDefaultConfiguration()
```

**Deliverable**: 23 new tests, aabenforms_webform at 60%+ coverage

---

## 📅 Week-by-Week Roadmap

### Week 2 (Jan 27 - Feb 2): Test Coverage Sprint

**Goals**:
- ✅ 72 total tests passing (currently 40)
- ✅ 70%+ coverage on aabenforms_core
- ✅ 60%+ coverage on aabenforms_webform
- ✅ Overall coverage: 45%+

**Deliverables**:
1. Complete all aabenforms_core service tests
2. Complete all aabenforms_webform validator tests
3. Update coverage reports in CI
4. Document testing patterns

---

### Week 3 (Feb 3-9): MitID Controller Integration

**Goals**:
- Complete MitID OIDC flow end-to-end
- Admin configuration forms
- Full authentication workflow

**Tasks**:

#### 1. MitID Login Controller (6 hours)
```php
File: web/modules/custom/aabenforms_mitid/src/Controller/MitIdAuthController.php

Methods to implement:
- redirect() - Initiate OAuth2 flow
- callback() - Handle OAuth2 callback
- logout() - End MitID session
- error() - Handle auth errors
```

#### 2. Admin Configuration (4 hours)
```php
File: web/modules/custom/aabenforms_mitid/src/Form/MitIdConfigForm.php

Settings:
- MitID client ID
- Client secret (encrypted storage)
- Environment (test vs. production)
- Redirect URI configuration
- Debug mode toggle
```

#### 3. Integration Tests (4 hours)
```bash
File: web/modules/custom/aabenforms_mitid/tests/src/Functional/MitIdAuthFlowTest.php

Tests:
- testOAuthRedirect()
- testCallbackWithValidCode()
- testCallbackWithInvalidCode()
- testSessionCreation()
- testLogoutFlow()
- testErrorHandling()
```

**Deliverable**: Working MitID login ready to integrate with webforms

---

### Week 4 (Feb 10-16): Workflow Foundation

**Goals**:
- Create WorkflowInstance entity
- Build custom ECA actions
- Create BPMN templates

**Tasks**:

#### 1. WorkflowInstance Entity (6 hours)
```php
File: web/modules/custom/aabenforms_workflows/src/Entity/WorkflowInstance.php

Fields:
- uuid (unique identifier)
- workflow_id (ECA model reference)
- state (pending, active, completed, failed)
- data (serialized workflow context)
- ttl (time-to-live in days)
- created (timestamp)
- updated (timestamp)
```

#### 2. ECA Custom Actions (8 hours)
```php
Files:
- src/Plugin/Action/RequireMitIdLogin.php
- src/Plugin/Action/LogAuditEvent.php
- src/Plugin/Action/EncryptField.php
- src/Plugin/Action/ValidateCpr.php

Each action needs:
- Plugin annotation
- Configuration form
- Execute method
- Unit tests
```

#### 3. BPMN Templates (4 hours)
```xml
Files in config/install/:
- eca.eca.citizen_complaint.yml
- eca.eca.building_permit.yml
- eca.eca.document_request.yml

Each template:
- Start event (webform submission)
- MitID login requirement
- Data validation steps
- Audit logging
- End event
```

**Deliverable**: Production-ready workflow templates

---

## 🚀 Future Phases (Overview)

### Phase 3: Serviceplatformen Integration (Weeks 5-8)

**Modules to Build**:
1. **aabenforms_cpr** (SF1520 - CPR Lookup)
   - Person master data retrieval
   - Address history
   - Family relations

2. **aabenforms_cvr** (SF1530 - CVR Lookup)
   - Company data retrieval
   - P-numbers (production units)
   - Industry classifications

3. **aabenforms_digital_post** (SF1601 - Digital Post)
   - Send secure notifications
   - Physical mail fallback
   - Delivery receipts

4. **aabenforms_dawa** (DAWA - Address Autocomplete)
   - Address validation
   - Geolocation
   - Direct integration (no auth)

**Key Tasks**:
- Implement SOAP envelope building
- Add certificate authentication
- Create WireMock stubs for all APIs
- Write integration tests

---

### Phase 4: Case Management (Weeks 9-12)

**Modules to Build**:
1. **aabenforms_sbsys** (SBSYS Integration)
2. **aabenforms_get_organized** (GetOrganized ESDH)

**Key Tasks**:
- Document archiving workflows
- Case creation from webforms
- Status synchronization
- Metadata management

---

### Phase 5: Production Readiness (Weeks 13-16)

**Focus Areas**:
1. Security hardening
2. Performance optimization
3. Production deployment guides
4. User documentation
5. Training materials

---

## 🎯 Success Metrics

### Week 2 Targets:
- [ ] 72 total tests passing
- [ ] 45%+ overall coverage
- [ ] All core services tested
- [ ] All webform validators tested
- [ ] CI pipeline green

### Week 3 Targets:
- [ ] MitID login working end-to-end
- [ ] Admin configuration functional
- [ ] Integration tests passing
- [ ] Demo video of MitID login flow

### Week 4 Targets:
- [ ] WorkflowInstance entity created
- [ ] 4 custom ECA actions working
- [ ] 3 BPMN templates installed
- [ ] Demo citizen complaint workflow

---

## 📋 Daily Checklist (Week 2)

### Monday (Today)
- [x] Update STATUS.md with current progress
- [x] Create NEXT_STEPS.md (this file)
- [ ] Write TenantResolver tests (6 tests)
- [ ] Write EncryptionService tests (8 tests)
- [ ] Push and verify CI passing

### Tuesday
- [ ] Write AuditLogger tests (10 tests)
- [ ] Write ServiceplatformenClient tests (8 tests)
- [ ] Verify aabenforms_core at 70%+ coverage
- [ ] Update coverage report

### Wednesday
- [ ] Write CprValidator tests (15 tests)
- [ ] Write CprField element tests (8 tests)
- [ ] Verify aabenforms_webform at 60%+ coverage
- [ ] Run full test suite

### Thursday
- [ ] Create Serviceplatformen mock fixtures
- [ ] Create MitID OIDC flow fixtures
- [ ] Document fixture patterns
- [ ] Update TESTING.md

### Friday
- [ ] Code review and cleanup
- [ ] Fix any remaining PHPStan warnings
- [ ] Update all documentation
- [ ] Week 2 retrospective
- [ ] Plan Week 3 in detail

---

## 🛠️ Development Commands

### Start Working on Tests

```bash
# Start DDEV
ddev start

# Create new test file
ddev ssh
cd /var/www/html

# Example: Create TenantResolver test
mkdir -p web/modules/custom/aabenforms_core/tests/src/Unit/Service
touch web/modules/custom/aabenforms_core/tests/src/Unit/Service/TenantResolverTest.php

# Run tests as you write
ddev test --group aabenforms_core --testdox

# Watch for changes (optional, requires entr)
find web/modules/custom -name "*Test.php" | entr ddev test --testdox
```

### Check Progress

```bash
# Overall test status
ddev test --testdox

# Coverage report
ddev test-coverage
# Then open: backend/coverage/index.html

# Coding standards
ddev exec vendor/bin/phpcs --standard=Drupal web/modules/custom

# Auto-fix
ddev exec vendor/bin/phpcbf --standard=Drupal web/modules/custom

# Static analysis
ddev exec vendor/bin/phpstan analyse web/modules/custom --level=6
```

### Commit & Push

```bash
# After each service test completion
git add web/modules/custom/aabenforms_core/tests/
git commit -m "Add unit tests for TenantResolver service

- 6 tests covering domain detection, config lookup
- Tests for edge cases (missing domain, invalid config)
- 100% coverage on TenantResolver"

git push origin main

# Watch CI
gh run watch
```

---

## 🎓 Testing Resources

### Test Examples to Reference

**Good unit test examples**:
- `web/modules/custom/aabenforms_mitid/tests/src/Unit/Service/MitIdCprExtractorTest.php`
- `web/modules/custom/aabenforms_mitid/tests/src/Unit/Service/MitIdSessionManagerTest.php`

**Good kernel test examples**:
- `web/modules/contrib/eca/tests/src/Kernel/` (ECA module)
- `web/modules/contrib/webform/tests/src/Kernel/` (Webform module)

### Testing Patterns

**Unit Test Pattern**:
```php
<?php

namespace Drupal\Tests\aabenforms_core\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\aabenforms_core\Service\ServiceName;

/**
 * @coversDefaultClass \Drupal\aabenforms_core\Service\ServiceName
 * @group aabenforms_core
 */
class ServiceNameTest extends UnitTestCase {

  protected ServiceName $service;

  protected function setUp(): void {
    parent::setUp();

    // Mock dependencies
    $dependency = $this->createMock(DependencyInterface::class);
    $dependency->method('someMethod')->willReturn('value');

    $this->service = new ServiceName($dependency);
  }

  /**
   * @covers ::methodName
   */
  public function testMethodName(): void {
    $result = $this->service->methodName('input');
    $this->assertEquals('expected', $result);
  }
}
```

**Kernel Test Pattern**:
```php
<?php

namespace Drupal\Tests\aabenforms_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group aabenforms_core
 */
class FeatureTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'aabenforms_core',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['aabenforms_core']);
  }

  public function testFeature(): void {
    $service = \Drupal::service('aabenforms_core.service_name');
    $this->assertInstanceOf(ServiceClass::class, $service);
  }
}
```

---

## 🚨 Blockers & Risks

### Current Blockers: NONE ✅

All infrastructure is in place and working.

### Potential Risks:

1. **Time Estimates Too Optimistic**
   - Mitigation: Tests can be added incrementally
   - Priority: Core services first, nice-to-haves later

2. **Test Maintenance Burden**
   - Mitigation: Following proven patterns from aabenforms_mitid
   - Keep tests simple and focused

3. **Serviceplatformen API Changes**
   - Mitigation: Using WireMock mocks for development
   - Real integration testing happens in Phase 3

---

## 📞 Support & Questions

**Getting Stuck?**
- Check existing test files in aabenforms_mitid for patterns
- Reference contrib module tests (ECA, Webform)
- Ask Claude for help with specific test scenarios

**Documentation**:
- **TESTING.md** - Testing guide
- **docs/CI_CD_STRATEGY.md** - CI/CD details
- **STATUS.md** - Current project status

**External Resources**:
- PHPUnit Docs: https://phpunit.de/documentation.html
- Drupal Testing: https://www.drupal.org/docs/automated-testing
- ECA Tests: web/modules/contrib/eca/tests/

---

**Last Updated**: 2026-01-27 10:15 UTC
**Next Update**: End of Week 2 (Feb 2)
**Questions?**: Update this file with Q&A section as needed
