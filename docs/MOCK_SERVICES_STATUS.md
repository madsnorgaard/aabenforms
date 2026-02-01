# ÅbenForms Mock Services - Status

**Date**: 2026-01-25
**DDEV Project**: aabenforms

---

## Service Status

| Service | Port | Status | Purpose |
|---------|------|--------|---------|
| **Keycloak** | 8080 | **RUNNING** | MitID authentication mock (OIDC) |
| **WireMock** | 8081 | **RUNNING** | Serviceplatformen SOAP mocks (SF1520, SF1530, SF1601) |
| **Prism** | 4010 | **DISABLED** | DAWA address API mock (OpenAPI) - Not needed yet |

---

## Working Services

### 1. Keycloak (MitID Mock) - Port 8080

**Admin UI**: http://localhost:8080/admin
- Username: `admin`
- Password: `admin`

**OIDC Discovery**: http://localhost:8080/realms/danish-gov-test/.well-known/openid-configuration

**Test Users** (10 Danish personas):
- freja.nielsen / test1234
- mikkel.jensen / test1234
- sofie.hansen / test1234
- lars.andersen / test1234
- emma.pedersen / test1234
- karen.christensen / test1234 (Business user, CVR: 12345678)
- protected.person / test1234 (Protected person)
- morten.rasmussen / test1234
- ida.mortensen / test1234
- peter.larsen / test1234

**Features**:
- OIDC 1.0 compliant
- Custom Danish claims (CPR, CVR, birthdate, assurance_level)
- Both backend (confidential) and frontend (public) clients configured

**Verification**:
```bash
curl http://localhost:8080/realms/danish-gov-test/.well-known/openid-configuration
# Should return OIDC discovery document with authorization_endpoint, token_endpoint, etc.
```

---

### 2. WireMock (Serviceplatformen Mock) - Port 8081

**Admin UI**: http://localhost:8081/__admin
**Mappings API**: http://localhost:8081/__admin/mappings

**Current Stubs**:
- **SF1520** (CPR Lookup) - Freja Nielsen (CPR: 0101904521)

**Missing Stubs** (TODO):
- ⏳ SF1520 for remaining 9 test users
- ⏳ SF1530 (CVR Lookup)
- ⏳ SF1601 (Digital Post)

**Verification**:
```bash
# Check loaded mappings
curl http://localhost:8081/__admin/mappings

# Test CPR lookup (Freja Nielsen)
curl -X POST http://localhost:8081/sf1520 \
  -H "Content-Type: text/xml" \
  --data '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns="http://kombit.dk/xml/schemas/RequestPersonBaseDataExtended/1/"><soapenv:Body><ns:GetPersonBaseDataExtended><ns:CPRNumber>0101904521</ns:CPRNumber></ns:GetPersonBaseDataExtended></soapenv:Body></soapenv:Envelope>'
```

---

## Disabled Service

### 3. Prism (DAWA Address Mock) - Port 4010

**Status**: Commented out in `.ddev/docker-compose.mocks.yaml`

**Reason**:
- OpenAPI spec file not created yet
- Not critical for Phase 1 (MitID + Serviceplatformen)
- Will add in Phase 3 when working on `aabenforms_dawa` module

**To Enable Later**:
1. Create OpenAPI spec: `.ddev/mocks/prism/dawa-openapi.yaml`
2. Uncomment Prism service in docker-compose
3. Restart: `ddev restart`

---

## DDEV Commands

```bash
# Check service status
ddev describe

# View service logs
docker logs ddev-aabenforms-keycloak
docker logs ddev-aabenforms-wiremock

# Restart all services
ddev restart

# Stop services
ddev stop

# Access Keycloak container
docker exec -it ddev-aabenforms-keycloak /bin/bash

# Access WireMock container
docker exec -it ddev-aabenforms-wiremock /bin/sh
```

---

## Integration with Drupal

### settings.local.php Configuration

```php
<?php

// MitID Mock (Keycloak)
$config['aabenforms_mitid.settings']['oidc']['issuer'] = 'http://localhost:8080/realms/danish-gov-test';
$config['aabenforms_mitid.settings']['oidc']['client_id'] = 'aabenforms-backend';
$config['aabenforms_mitid.settings']['oidc']['client_secret'] = 'aabenforms-backend-secret-change-in-production';
$config['aabenforms_mitid.settings']['mock_mode'] = TRUE;

// Serviceplatformen Mock (WireMock)
$config['aabenforms_core.settings']['serviceplatformen']['endpoint'] = 'http://localhost:8081';
$config['aabenforms_core.settings']['serviceplatformen']['sf1520_path'] = '/sf1520';
$config['aabenforms_core.settings']['serviceplatformen']['sf1530_path'] = '/sf1530';
$config['aabenforms_core.settings']['serviceplatformen']['sf1601_path'] = '/sf1601';
$config['aabenforms_core.settings']['serviceplatformen']['mock_mode'] = TRUE;

// DAWA Mock (Prism - when enabled)
// $config['aabenforms_dawa.settings']['endpoint'] = 'http://localhost:4010';
// $config['aabenforms_dawa.settings']['mock_mode'] = TRUE;
```

---

## Troubleshooting

### Issue: Can't access Keycloak Admin UI

**Symptoms**: http://localhost:8080/admin shows "Unable to connect"

**Solutions**:
1. Check if services are running:
   ```bash
   ddev describe
   ```

2. Restart DDEV:
   ```bash
   ddev restart
   ```

3. Check container logs:
   ```bash
   docker logs ddev-aabenforms-keycloak
   ```

4. Verify port is accessible:
   ```bash
   curl http://localhost:8080
   ```

---

### Issue: Keycloak realm not imported

**Symptoms**: No test users in Keycloak Admin UI

**Solutions**:
1. Check realm file exists:
   ```bash
   ls -la .ddev/mocks/keycloak/realms/danish-gov-test.json
   ```

2. Validate JSON syntax:
   ```bash
   python3 -m json.tool .ddev/mocks/keycloak/realms/danish-gov-test.json > /dev/null
   echo "JSON is valid"
   ```

3. Manually import via Admin UI:
   - Go to http://localhost:8080/admin
   - Login: admin / admin
   - Click "Add realm" → "Import" → Select file
   - Or restart DDEV (auto-imports on startup)

---

### Issue: WireMock mappings not loading

**Symptoms**: CPR lookup returns 404

**Solutions**:
1. Check mappings exist:
   ```bash
   ls -la .ddev/mocks/wiremock/mappings/
   ```

2. Verify JSON syntax:
   ```bash
   python3 -m json.tool .ddev/mocks/wiremock/mappings/sf1520-freja-nielsen.json
   ```

3. Check loaded mappings:
   ```bash
   curl http://localhost:8081/__admin/mappings
   ```

4. Restart WireMock:
   ```bash
   ddev restart
   ```

---

### Issue: Port 4010 not accessible (Prism)

**This is expected!** Prism is intentionally disabled. See "Disabled Service" section above.

---

## Next Steps

### Immediate (Today)
1. Keycloak running with 10 test users
2. WireMock running with 1 CPR lookup stub
3. CI/CD pipeline configured with mock services
4. Documentation complete

### Short-term (Next Week)
1. Add remaining 9 CPR lookup stubs (SF1520)
2. Add CVR lookup stubs (SF1530) for karen.christensen
3. Add Digital Post stubs (SF1601)
4. Write first integration tests for `aabenforms_mitid`

### Medium-term (Phase 3)
1. Create DAWA OpenAPI spec
2. Enable Prism service
3. Test address autocomplete with `aabenforms_dawa` module

---

## Documentation

- **Complete Guide**: `docs/DDEV_MOCK_SERVICES_GUIDE.md`
- **Quick Reference**: `docs/MOCK_SERVICES_QUICK_REFERENCE.md`
- **CI/CD Integration**: `docs/CI_WITH_MOCK_SERVICES.md`
- **Standards Analysis**: `docs/INTERNATIONAL_STANDARDS_AND_TOOLS.md`
- **Implementation Summary**: `docs/MOCK_SERVICES_IMPLEMENTATION_SUMMARY.md`

---

**Last Updated**: 2026-01-25
**Status**: Ready for Development
