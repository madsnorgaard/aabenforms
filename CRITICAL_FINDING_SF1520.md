# 🚨 CRITICAL FINDING: SF1520 Status

**Date**: 2026-01-25 15:00 UTC
**Severity**: HIGH
**Impact**: Architecture Change Required

---

## Discovery

**Source**: Official KOMBIT Documentation
- https://digitaliseringskataloget.dk/integration/sf1520
- https://docs.kombit.dk/integration/sf1520/4.0/pakke

**Finding**:
> **"Integrationen er lukket for tilgang af nye anvendere"**
>
> Translation: **"The integration is closed for access by new users"**

---

## What This Means

### ❌ CANNOT Do (New Clients)

- Cannot get NEW access to SF1520 CPR Replika
- Cannot rely on SF1520 as primary CPR lookup method
- Cannot use SF1520 for new ÅbenForms installations

### ✅ CAN Do (Existing Access)

- Municipalities with existing SF1520 access can continue using it
- We can support SF1520 as **optional/legacy** backend
- SF1520 test data still available for development

---

## Impact on ÅbenForms

### Original Architecture (BEFORE)

```
CPR Access: SF1520 (Primary)
├── aabenforms_cpr module (Week 9)
├── Serviceplatformen SOAP client
└── CPR-replika test data
```

### Revised Architecture (AFTER)

```
CPR Access: MitID Claims (Primary) + SF1520 (Optional Legacy)
├── aabenforms_mitid module (Week 7-8) ← NEW PRIORITY
│   └── Extract CPR from MitID authentication token
├── aabenforms_cpr module (Week 9) ← NOW ORCHESTRATION LAYER
│   ├── MitID Claims (primary backend)
│   ├── SF1520 (optional backend - if available)
│   └── Validation Only (fallback)
└── Three-tier strategy for CPR access
```

---

## Solution: MitID Claims Strategy

### How It Works

```
┌─────────────────────────────────────────────┐
│  Citizen Authentication Flow (MitID)       │
└─────────────────────────────────────────────┘

1. Citizen clicks "Log in with MitID"
   ↓
2. MitID authentication (via OIDC)
   ↓
3. Receive id_token (JWT) with claims:
   {
     "cpr": "0101001234",
     "name": "Hans Hansen",
     "given_name": "Hans",
     "family_name": "Hansen",
     "birthdate": "1900-01-01",
     "acr": "substantial"  // NSIS assurance level
   }
   ↓
4. ÅbenForms extracts CPR from token
   ↓
5. Use CPR in workflow (no API call needed!)
```

### Benefits

✅ **No SF1520 needed** - Works for ALL new clients
✅ **Real-time** - No replica delay
✅ **GDPR compliant** - Authenticated user = consent
✅ **No API costs** - CPR from token, not external lookup
✅ **NSIS assured** - Authentication level included

### Limitations

❌ **Only authenticated user** - Cannot look up other people's CPR
❌ **Limited data** - Only what MitID provides (no address/family)

**Solution for Limitations**: Keep SF1520 as optional backend for caseworker lookups

---

## Implementation Changes

### Phase Priorities REVISED

| Phase | Original Plan | Revised Plan | Reason |
|-------|--------------|--------------|--------|
| **Week 7-8** | aabenforms_workflows | **aabenforms_mitid** ✅ | Primary CPR access method |
| **Week 9** | aabenforms_cpr (SF1520) | **aabenforms_cpr (orchestration)** | Support multiple backends |
| **Week 10** | aabenforms_cvr | aabenforms_cvr (unchanged) | - |
| **Week 11** | aabenforms_digital_post | aabenforms_digital_post (unchanged) | - |

### Code Already Future-Proof ✅

Our **ServiceplatformenClient** is designed for multiple services:

```php
// This STILL works for SF1530 (CVR) and SF1601 (Digital Post)
$client->request('SF1530', 'CompanyLookup', $params);
$client->request('SF1601', 'SendDigitalPost', $params);

// SF1520 becomes optional
if ($this->hasSf1520Access()) {
  $client->request('SF1520', 'PersonLookup', $params);
}
```

**No rewrite needed** - Just add MitID Claims as primary method.

---

## Documentation Updates

### Created

1. ✅ **SF1520_OFFICIAL_DOCUMENTATION.md** (17 KB)
   - Complete SF1520 specifications
   - Test data types (Basis/Arketype/Custom)
   - Support services and pricing
   - Production alternatives

2. ✅ **CPR_ACCESS_STRATEGY_REVISED.md** (15 KB)
   - Three-tier CPR access strategy
   - MitID Claims implementation
   - SF1520 legacy support
   - Migration guide

3. ✅ **CRITICAL_FINDING_SF1520.md** (this file)
   - Executive summary
   - Quick reference
   - Action plan

### To Update

- ⏭️ IMPLEMENTATION_PLAN.md - Revise Week 7-8 priorities
- ⏭️ ARCHITECTURE_FLOW_AUTH_DATA_MOVEMENT.md - Add MitID Claims flow
- ⏭️ STATUS.md - Update current status with SF1520 finding

---

## Client Communication

### For New Clients

**Message**:
> "ÅbenForms uses modern MitID authentication for CPR access. Your citizens authenticate with MitID, and we extract their CPR number directly from the authentication token. This eliminates the need for separate CPR lookup services and provides real-time, GDPR-compliant data access."

**Benefit**: Simpler setup, no SF1520 service agreement needed

### For Legacy Clients (OS2Forms Migration)

**Message**:
> "ÅbenForms supports your existing SF1520 access for caseworker workflows. For citizen-facing forms, we recommend using MitID Claims for improved security and real-time data. We provide a hybrid approach during migration."

**Benefit**: Gradual migration path, backward compatibility

---

## Testing Strategy

### MitID Test Environment

**URL**: https://pp.mitid.dk/test-tool/

**Provides**:
- Test users with valid CPR numbers
- Test authentication flow
- id_token with CPR claims
- NSIS assurance level testing

### SF1520 Test Data (Optional)

**CPR-Replika Basis-testdata**: Available for clients with test access

**Use Case**: Testing SF1520 legacy backend (caseworker lookups)

---

## Next Actions

### THIS WEEK

1. ✅ **Documentation complete** (SF1520 official docs + revised strategy)
2. ⏭️ **Update IMPLEMENTATION_PLAN.md** with new priorities
3. ⏭️ **Update STATUS.md** with critical finding

### WEEK 7-8 (NEW PRIORITY)

4. ⏭️ **Build aabenforms_mitid module**
   - MitID OIDC client
   - CPR Claims extractor service
   - Flow-scoped session storage
   - Integration with aabenforms_webform

### WEEK 9

5. ⏭️ **Build aabenforms_cpr orchestration module**
   - Multi-backend support (MitID Claims + SF1520 + Validation Only)
   - Configuration for method selection
   - ECA actions for all backends

---

## Questions & Answers

### Q: Can we still use SF1520 for testing?

**A**: YES - CPR-replika test data (Basis/Arketype) is still available. We can develop and test SF1520 integration, but mark it as "optional" for clients with existing access.

### Q: What about other Serviceplatformen services (CVR, Digital Post)?

**A**: UNAFFECTED - SF1530 (CVR) and SF1601 (Digital Post) are still available for new users. Only SF1520 (CPR) is closed.

### Q: Do we need to rewrite existing code?

**A**: NO - Our ServiceplatformenClient already supports multiple services. We just add MitID Claims as primary CPR access method and keep SF1520 as optional backend.

### Q: What if a caseworker needs to look up someone else's CPR?

**A**: Three options:
1. **SF1520 (if available)** - Use SF1520 backend for caseworker lookups
2. **Manual entry** - Caseworker enters CPR manually (validation only)
3. **Future: CPR Direkte** - Investigate direct CPR access for municipalities

### Q: Does this affect our timeline?

**A**: MINIMAL - We just reprioritize:
- Move aabenforms_mitid from "Phase 2" to "Week 7-8" (higher priority)
- aabenforms_cpr becomes orchestration layer instead of SF1520-only
- Overall timeline unchanged (still 16 weeks to MVP)

---

## Positive Outcomes

### Blessing in Disguise?

**Discovery timing**: Found BEFORE implementing SF1520 integration (Week 5)
**Cost saved**: ~40 hours of SF1520-specific code that would need rewrite
**Better architecture**: MitID Claims is simpler, faster, more GDPR-friendly

### Competitive Advantage

**ÅbenForms vs. OS2Forms**:
- ✅ OS2Forms: Relies on SF1520 (closed for new users)
- ✅ ÅbenForms: MitID Claims (works for all new clients)

**Marketing message**: "Modern architecture, future-proof design, works out of the box"

---

## References

### Official Documentation

- **SF1520 Status**: https://digitaliseringskataloget.dk/integration/sf1520
- **SF1520 Technical Docs**: https://docs.kombit.dk/integration/sf1520/4.0/pakke
- **MitID Test Tool**: https://pp.mitid.dk/test-tool/
- **CPR Office**: https://www.cpr.dk/

### ÅbenForms Documentation

- **SF1520 Official Documentation**: `reports/SF1520_OFFICIAL_DOCUMENTATION.md`
- **Revised CPR Strategy**: `reports/CPR_ACCESS_STRATEGY_REVISED.md`
- **Implementation Plan**: `reports/IMPLEMENTATION_PLAN.md` (to be updated)

---

## Conclusion

### Summary

🚨 **SF1520 is closed for new users**

✅ **Solution: MitID Claims as primary CPR access method**

✅ **Impact: Minimal - reprioritize MitID module to Week 7-8**

✅ **Benefits: Simpler, faster, more GDPR-friendly, works for all new clients**

✅ **Legacy support: Keep SF1520 as optional backend for existing access**

### Recommendation

**PROCEED** with revised architecture:
1. **Primary**: MitID Claims (citizen-facing workflows)
2. **Optional**: SF1520 legacy (caseworker lookups, if available)
3. **Fallback**: Validation only (no external lookup)

**Status**: ✅ Architecture revised, documentation complete, ready to implement

---

**Last Updated**: 2026-01-25 15:15 UTC
**Status**: ✅ RESOLVED - New strategy approved
**Next**: Begin aabenforms_mitid module (Week 7-8)
