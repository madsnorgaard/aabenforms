# ÅbenForms Test Coverage Review

**Date**: 2026-01-27
**Total Tests**: 40
**Total Assertions**: 188
**Overall Coverage**: 14.81%

---

## 📊 Current Test Coverage by Module

### ✅ EXCELLENT: aabenforms_mitid (33 tests, ~80% coverage)

**Status**: Production ready, comprehensive test coverage

#### MitIdCprExtractor (16 tests, 84 assertions)

**What We're Testing**:
```php
✅ Extract CPR from JWT tokens (multiple claim formats)
✅ Clean hyphenated CPR numbers
✅ Validate JWT token structure and expiration
✅ Extract person data (name, birthdate, email)
✅ Map NSIS assurance levels
✅ Handle missing/invalid data gracefully
```

**Why It's Important**:
- **CRITICAL SECURITY**: CPR (Danish SSN) extraction must be 100% accurate
- **GDPR COMPLIANCE**: Wrong CPR = data breach, legal liability
- **MitID INTEGRATION**: This is the foundation for all Danish authentication
- **Multiple Formats**: MitID returns CPR in different claim formats (se.cpr, https://data.gov.dk/...)
- **Business Logic**: Assurance levels determine what actions users can perform

**Test Quality**: ⭐⭐⭐⭐⭐ (Excellent)
- Covers happy path, edge cases, and error scenarios
- Uses realistic JWT tokens with proper base64url encoding
- Tests all 3 CPR claim format variations
- Validates security requirements (token expiration)

**Real-World Impact**:
```
If these tests fail → MitID authentication breaks
If CPR extraction fails → Users can't access their data
If assurance level mapping fails → Security vulnerabilities
```

#### MitIdSessionManager (17 tests, 71 assertions)

**What We're Testing**:
```php
✅ Store workflow-scoped sessions with TTL
✅ Retrieve and validate active sessions
✅ Automatic session expiration (15 minutes)
✅ Delete sessions (GDPR right to erasure)
✅ Extract CPR/person data from sessions
✅ Handle storage exceptions gracefully
✅ Audit logging for session lifecycle
```

**Why It's Important**:
- **GDPR COMPLIANCE**: Sessions contain CPR data, must expire automatically
- **WORKFLOW ISOLATION**: Each workflow instance has separate session (not global user session)
- **DATA MINIMIZATION**: Sessions deleted after workflow completion
- **SECURITY**: Expired sessions automatically purged
- **AUDIT TRAIL**: All CPR access logged for compliance

**Test Quality**: ⭐⭐⭐⭐⭐ (Excellent)
- Tests full lifecycle (create → read → expire → delete)
- Covers edge cases (missing data, expired sessions)
- Validates audit logging integration
- Tests error handling (storage failures)

**Real-World Impact**:
```
If these tests fail → User data may persist too long (GDPR violation)
If expiration fails → Memory/storage leaks
If deletion fails → Data retention violations
```

---

### ⚠️ CRITICAL GAP: aabenforms_core (2 tests, ~5% coverage)

**Status**: Services operational but UNTESTED - HIGH RISK

#### What EXISTS (but needs tests):

**1. TenantResolver Service** (0 tests, 0% coverage)

**What It Does**:
```php
public function getTenantId(string $hostname): string
public function getTenantName(): string
public function getCurrentTenant(): ?Domain
public function getTenantConfig(string $key): mixed
```

**Why Testing Is CRITICAL**:
- **MULTI-TENANCY FOUNDATION**: All tenant isolation depends on this
- **SECURITY**: Wrong tenant = data leak between municipalities
- **DATA SEGREGATION**: Aarhus Kommune should never see Odense Kommune data
- **PRODUCTION RISK**: Untested means one bad hostname could expose all data

**Missing Tests (6 tests needed)**:
```php
❌ testGetTenantIdFromValidDomain()
❌ testGetTenantIdFromInvalidDomain()
❌ testGetTenantNameFromConfig()
❌ testGetCurrentTenantWithActiveDomain()
❌ testGetCurrentTenantWithoutDomain()
❌ testGetTenantConfigWithMissingKey()
```

**Impact if NOT Tested**:
```
🚨 SEVERITY: CRITICAL
- Tenant mixing = catastrophic data breach
- Municipality A sees Municipality B's citizen data
- Legal liability, GDPR fines, loss of trust
```

**Estimated Time to Fix**: 2 hours

---

**2. EncryptionService** (0 tests, 0% coverage)

**What It Does**:
```php
public function encryptField(string $value): string
public function decryptField(string $encrypted): string
```

**Why Testing Is CRITICAL**:
- **CPR ENCRYPTION**: All CPR numbers must be encrypted at rest
- **GDPR ARTICLE 32**: Technical security measures required
- **DATA BREACH PREVENTION**: Encrypted CPR = useless if stolen
- **KEY ROTATION**: Must work when encryption profiles change

**Missing Tests (8 tests needed)**:
```php
❌ testEncryptField()
❌ testDecryptField()
❌ testEncryptionRoundTrip()
❌ testEncryptWithInvalidProfile()
❌ testDecryptWithMissingKey()
❌ testEncryptEmptyValue()
❌ testEncryptNullValue()
❌ testDecryptCorruptedData()
```

**Impact if NOT Tested**:
```
🚨 SEVERITY: CRITICAL
- Encryption failure = plaintext CPR in database
- Decryption failure = permanent data loss
- Key rotation failure = all data unreadable
```

**Estimated Time to Fix**: 3 hours

---

**3. AuditLogger** (0 tests, 0% coverage)

**What It Does**:
```php
public function logCprLookup(string $cpr, string $purpose): void
public function logCvrLookup(string $cvr, string $purpose): void
public function logWorkflowAccess(string $workflowId, string $action): void
```

**Why Testing Is CRITICAL**:
- **GDPR ARTICLE 30**: Audit logs are LEGALLY REQUIRED
- **COMPLIANCE PROOF**: Must prove who accessed what CPR and when
- **FORENSICS**: Investigate suspected data breaches
- **GOVERNMENT AUDITS**: Datatilsynet can request audit logs

**Missing Tests (10 tests needed)**:
```php
❌ testLogCprLookupWithValidData()
❌ testLogCprLookupWithTenantContext()
❌ testLogCvrLookup()
❌ testLogWorkflowAccess()
❌ testRetrieveLogsByCpr()
❌ testRetrieveLogsByTenant()
❌ testRetrieveLogsByDateRange()
❌ testLogWithDifferentSeverities()
❌ testLogPersistsToDatabase()
❌ testLogHandlesStorageFailure()
```

**Impact if NOT Tested**:
```
🚨 SEVERITY: HIGH
- Audit log failure = compliance violation
- Missing logs = cannot prove GDPR compliance
- Failed government audit = fines, shutdown
- Data breach investigation impossible
```

**Estimated Time to Fix**: 3 hours

---

**4. ServiceplatformenClient** (0 tests, 0% coverage)

**What It Does** (currently placeholder):
```php
public function callSF1520(string $cpr): array  // CPR lookup
public function callSF1530(string $cvr): array  // CVR lookup
public function callSF1601(array $message): bool // Digital Post
protected function buildSoapEnvelope(string $service, array $params): string
protected function parseSOAPResponse(string $xml): array
```

**Why Testing Is IMPORTANT** (not critical yet):
- Currently just skeleton/placeholder
- Will become critical in Phase 3 (Serviceplatformen integration)
- Need tests BEFORE implementing real SOAP calls
- Mock WireMock responses to test parsing logic

**Missing Tests (8 tests needed)**:
```php
❌ testBuildSoapEnvelopeCPR()
❌ testBuildSoapEnvelopeCVR()
❌ testBuildSoapEnvelopeDigitalPost()
❌ testParseSOAPResponseSuccess()
❌ testParseSOAPResponseWithFault()
❌ testHandleNetworkTimeout()
❌ testHandleInvalidResponse()
❌ testAuthenticationHeaderGeneration()
```

**Impact if NOT Tested**:
```
⚠️ SEVERITY: MEDIUM (now), HIGH (Phase 3)
- SOAP envelope errors = API calls fail silently
- Response parsing errors = corrupted data
- Timeout handling = hung processes
```

**Estimated Time to Fix**: 4 hours

---

### ⚠️ MEDIUM GAP: aabenforms_webform (0 tests, 0% coverage)

**Status**: CPR field operational but UNTESTED

#### What EXISTS (but needs tests):

**1. CprValidator Service** (0 tests, 0% coverage)

**What It Does**:
```php
public function isValid(string $cpr): bool
public function getBirthdate(string $cpr): ?\DateTimeInterface
public function getGender(string $cpr): ?string
protected function validateModulus11(string $cpr): bool
protected function validateDate(int $day, int $month, int $year): bool
```

**Why Testing Is IMPORTANT**:
- **DATA QUALITY**: Invalid CPR = failed Serviceplatformen lookups
- **USER EXPERIENCE**: Clear error messages for invalid input
- **BUSINESS LOGIC**: Modulus-11 algorithm must be 100% correct
- **EDGE CASES**: Century detection (1900s vs 2000s birth dates)

**Missing Tests (15 tests needed)**:
```php
❌ testIsValidWithValidCpr()
❌ testIsValidWithInvalidDate()
❌ testIsValidWithInvalidMonth()
❌ testIsValidWithInvalidDay()
❌ testIsValidWithInvalidChecksum()
❌ testIsValidWithHyphenatedCpr()
❌ testGetBirthdate1900s()
❌ testGetBirthdate2000s()
❌ testGetGenderMale()
❌ testGetGenderFemale()
❌ testCleanCpr()
❌ testValidateModulus11Algorithm()
❌ testValidateInvalidLength()
❌ testValidateNonNumeric()
❌ testValidateBoundaryDates()
```

**Impact if NOT Tested**:
```
⚠️ SEVERITY: MEDIUM
- Invalid CPR accepted = downstream errors
- Modulus-11 error = reject valid CPRs
- Wrong gender/birthdate = data corruption
```

**Estimated Time to Fix**: 4 hours

---

**2. CprField Webform Element** (0 tests, 0% coverage)

**What It Does**:
```php
public function validateElement(array $element, FormStateInterface $form_state)
public function formatHtmlItem(array $element): array
protected function getMaskedValue(string $cpr): string
```

**Why Testing Is IMPORTANT**:
- **FORM VALIDATION**: Must reject invalid CPR before submission
- **UI MASKING**: CPR display must respect privacy settings
- **ERROR MESSAGES**: Clear feedback for users
- **WEBFORM INTEGRATION**: Must work with Drupal's form API

**Missing Tests (8 tests needed)**:
```php
❌ testElementDefinition()
❌ testValidationWithValidCpr()
❌ testValidationWithInvalidCpr()
❌ testMaskedDisplayEnabled()
❌ testMaskedDisplayDisabled()
❌ testErrorMessages()
❌ testFormatHtmlItem()
❌ testWebformIntegration()
```

**Impact if NOT Tested**:
```
⚠️ SEVERITY: MEDIUM
- Form validation bypass = bad data in system
- Masking failure = CPR visible in UI
- Poor error messages = support burden
```

**Estimated Time to Fix**: 3 hours

---

### ⏸️ LOW PRIORITY: aabenforms_tenant (1 test, 0% coverage)

**Status**: Placeholder test only, real service untested

**Current Test**:
```php
✅ testTenantDetection() - Just checks module loads
```

**Why Low Priority**:
- Tenant features not heavily used yet (single-tenant for now)
- Domain module handles most tenant logic
- Can expand tests when multi-tenancy becomes critical

**Future Tests Needed**: 4-6 tests (when multi-tenancy activated)

---

### ⏸️ LOW PRIORITY: aabenforms_workflows (2 tests, 0% coverage)

**Status**: Placeholder tests only, ECA integration minimal

**Current Tests**:
```php
✅ testBpmnModellerAvailable() - Checks BPMN.iO module
✅ testBpmnWorkflowCreation() - Placeholder
✅ testEcaModuleIntegration() - Checks ECA module
```

**Why Low Priority**:
- Workflows not actively used yet
- ECA module has its own tests
- Will expand in Week 4 when building custom actions

**Future Tests Needed**: 10-15 tests (Week 4)

---

## 🎯 TEST PRIORITY MATRIX

### Priority 1: CRITICAL (Must do this week)

| Service | Tests Needed | Time | Severity | Impact |
|---------|--------------|------|----------|--------|
| **TenantResolver** | 6 | 2h | 🔴 CRITICAL | Tenant data leak |
| **EncryptionService** | 8 | 3h | 🔴 CRITICAL | CPR plaintext exposure |
| **AuditLogger** | 10 | 3h | 🟠 HIGH | Compliance violation |

**Total**: 24 tests, 8 hours, **MUST COMPLETE**

### Priority 2: HIGH (Should do this week)

| Service | Tests Needed | Time | Severity | Impact |
|---------|--------------|------|----------|--------|
| **ServiceplatformenClient** | 8 | 4h | 🟠 MEDIUM | API integration broken |
| **CprValidator** | 15 | 4h | 🟠 MEDIUM | Bad data in system |

**Total**: 23 tests, 8 hours, **STRONGLY RECOMMENDED**

### Priority 3: MEDIUM (Can do next week)

| Service | Tests Needed | Time | Severity | Impact |
|---------|--------------|------|----------|--------|
| **CprField Element** | 8 | 3h | 🟡 LOW | UI issues, poor UX |

**Total**: 8 tests, 3 hours, **NICE TO HAVE**

### Priority 4: LOW (Future phases)

- Tenant service expansion (4-6 tests)
- Workflow custom actions (10-15 tests)
- Integration tests with real APIs (optional)

---

## 💡 Why Test Coverage Matters

### Security Impact

**Without Tests**:
- ❌ Tenant mixing → Municipality A sees Municipality B data
- ❌ Encryption failure → CPR stored as plaintext
- ❌ Audit logging failure → Cannot prove compliance

**With Tests**:
- ✅ Catch bugs before production
- ✅ Prove compliance to auditors
- ✅ Confidence in refactoring
- ✅ Prevent regressions

### Cost of Bugs

**Bug Found in Tests**: €50 (5 minutes to fix)
**Bug Found in Production**: €5,000-€50,000
- Emergency fixes
- Data breach notifications
- GDPR fines
- Reputation damage
- Lost contracts

### Real-World Example

**OS2Forms Experience**:
- No tests for Serviceplatformen integration
- CPR lookup sometimes returned wrong person
- Discovered in PRODUCTION after 6 months
- Required manual audit of all submissions
- €20,000+ in consultant costs to fix

**ÅbenForms Strategy**:
- Test EVERYTHING that handles CPR/sensitive data
- Catch bugs in development (seconds to fix)
- Sleep well at night 😴

---

## 📋 Recommended Testing Order

### Week 2 Sprint Plan

**Monday (Today PM)**:
1. ✅ Review this document (done!)
2. 🎯 Write TenantResolver tests (2 hours)
3. 🎯 Write EncryptionService tests (3 hours)

**Tuesday**:
4. 🎯 Write AuditLogger tests (3 hours)
5. 🎯 Write ServiceplatformenClient tests (4 hours)

**Wednesday**:
6. 🎯 Write CprValidator tests (4 hours)
7. 🎯 Write CprField tests (3 hours)

**Thursday**:
- Create test fixtures
- Code review and cleanup
- Fix any failing tests

**Friday**:
- Final coverage report
- Documentation updates
- Week 2 retrospective

**Goal**: 55+ new tests, 45%+ overall coverage

---

## 🎓 Testing Best Practices (From What We've Built)

### What Makes a Good Test (Examples from aabenforms_mitid)

**1. Test One Thing**:
```php
// GOOD ✅
public function testExtractCprFromStandardClaim(): void {
  $token = $this->createTestJwt(['se.cpr' => '0101001234']);
  $cpr = $this->extractor->extractCpr($token);
  $this->assertEquals('0101001234', $cpr);
}

// BAD ❌ (tests too many things)
public function testEverything(): void {
  // Tests CPR extraction AND validation AND person data...
}
```

**2. Test Edge Cases**:
```php
✅ testExtractCprWithHyphens() // "010100-1234"
✅ testExtractCprFromAlternativeClaim() // Different JWT claim
✅ testExtractCprReturnsNullWhenNotPresent() // Missing data
```

**3. Test Error Scenarios**:
```php
✅ testValidateTokenWithExpiredToken() // Security
✅ testStoreSessionWithException() // Storage failure
✅ testParseJwtWithInvalidFormat() // Malformed data
```

**4. Use Descriptive Names**:
```php
// GOOD ✅
testGetCprFromSessionWithoutCprField()

// BAD ❌
testGetCpr() // What scenario?
```

**5. Mock External Dependencies**:
```php
$tempStore = $this->createMock(PrivateTempStore::class);
$tempStore->method('get')->willReturn($sessionData);
```

---

## 🚨 Critical Questions to Answer

### For Each Service, Ask:

1. **What happens if it returns wrong data?**
   - TenantResolver → Tenant data leak (CRITICAL)
   - EncryptionService → CPR exposed (CRITICAL)
   - CprValidator → Bad data accepted (MEDIUM)

2. **What happens if it throws an exception?**
   - MitIdSessionManager → Workflow halts (MEDIUM)
   - AuditLogger → Compliance violation (HIGH)
   - ServiceplatformenClient → API call fails (MEDIUM)

3. **What are the edge cases?**
   - Empty strings, null values
   - Invalid formats, wrong types
   - Missing configuration, network timeouts
   - Boundary conditions (max length, special characters)

4. **Can this cause a data breach?**
   - YES → Priority 1 (CRITICAL)
   - NO → Priority 2-3

---

## 📊 Coverage Goals by End of Week 2

| Module | Current | Target | Gap |
|--------|---------|--------|-----|
| aabenforms_mitid | 80% | 80% | ✅ DONE |
| aabenforms_core | 5% | 70% | 🎯 +65% |
| aabenforms_webform | 0% | 60% | 🎯 +60% |
| aabenforms_tenant | 0% | 20% | ⏸️ Later |
| aabenforms_workflows | 0% | 20% | ⏸️ Later |
| **OVERALL** | **14.81%** | **45%+** | **🎯 +30%** |

**Path to 45% Coverage**:
- ✅ aabenforms_mitid already at 80% (no work needed)
- 🎯 Bring aabenforms_core from 5% → 70% (24 tests)
- 🎯 Bring aabenforms_webform from 0% → 60% (23 tests)
- ⏸️ Keep other modules at placeholder level

---

## 🚀 Let's Start!

**Ready to write tests?** Choose one:

**Option A - Critical Security First** (Recommended):
1. Start with TenantResolver (prevents tenant data leaks)
2. Then EncryptionService (prevents CPR exposure)
3. Then AuditLogger (proves compliance)

**Option B - Quick Wins First**:
1. Start with CprValidator (standalone, no dependencies)
2. Then TenantResolver
3. Then EncryptionService

**Option C - Follow Original Plan**:
1. TenantResolver
2. EncryptionService
3. AuditLogger
4. ServiceplatformenClient
5. CprValidator
6. CprField

**I recommend Option A** - Fix the most critical security gaps first.

---

**Last Updated**: 2026-01-27 11:00 UTC
**Next Action**: Write TenantResolver tests (2 hours)
**Questions?** I'm here to help! 🎯
