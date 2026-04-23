# ADR 0002: flow namespacing for multi-tenant deployments

**Status:** Accepted in principle, deferred for implementation.
**Trigger:** Before the workflow wizard is used by more than one
municipality, or before any tenant customizes a shipped template.

## Context

AabenForms runs as a multi-tenant platform via the Domain module
(`aabenforms_tenant`). In production today only one tenant exists
(demo), so all ECA flows, webforms, and BPMN templates live in the
shared global namespace:

- `config/sync/eca.eca.hr_onboarding_flow.yml`
- `config/sync/webform.webform.hr_onboarding.yml`
- `web/modules/custom/aabenforms_workflows/workflows/hr_onboarding.bpmn`

The workflow wizard (`WorkflowTemplateWizardForm` +
`WorkflowTemplateInstantiator`) can produce new flows and webforms
on the fly. Today those outputs also land in the global namespace.
That is correct for one tenant. It is wrong for many.

If municipality A customizes `hr_onboarding` (different approver
routing, different manager email source) and municipality B wants the
stock template, the wizard's current behavior either overwrites A's
customization or fails to let B have its own copy.

## Decision

Introduce a tenant prefix convention on all wizard-generated
artifacts. **Shipped templates stay unprefixed** (they are the
defaults every tenant inherits). **Tenant customizations are
prefixed by the tenant's Domain machine name.**

Examples:

| Artifact | Stock (shipped) | Aarhus-customized | Ballerup-customized |
|----------|-----------------|-------------------|---------------------|
| ECA flow | `eca.eca.hr_onboarding_flow` | `eca.eca.aarhus__hr_onboarding_flow` | `eca.eca.ballerup__hr_onboarding_flow` |
| Webform | `webform.webform.hr_onboarding` | `webform.webform.aarhus__hr_onboarding` | `webform.webform.ballerup__hr_onboarding` |
| BPMN template | `workflows/hr_onboarding.bpmn` | `workflows/aarhus__hr_onboarding.bpmn` | `workflows/ballerup__hr_onboarding.bpmn` |

Double-underscore separator so tenant prefixes never collide with
a template name containing single underscores.

At request time, `TenantResolver::getCurrentTenant()` (already wired
via Domain) is consulted. If a tenant-prefixed flow exists for the
current domain, it fires. Otherwise the stock flow fires. Exactly
one fires per submission.

## Implementation sketch

1. **`WorkflowTemplateInstantiator::instantiate()`** takes a new
   `tenant_id` argument (optional; defaults to current tenant).
   Prepends `{tenant}__` to every output ID unless `tenant_id` is
   the global sentinel (new constant `TenantResolver::GLOBAL`).
2. **ECA bundle filter gains a prefix-aware matcher.** The
   `content_entity:insert` filter today is bundle-exact. For
   multi-tenant, we need flows to bind to their own prefixed
   webform bundle. Already handled implicitly by the bundle filter
   pattern shipped in commits `1d09fd9` and follow-up: each
   tenant-customized flow sets
   `type: 'webform_submission {tenant}__{template}'`.
3. **Domain-scoped webform routes.** Webform submission routes
   resolve the effective webform ID per request:
   `GET/POST /api/webform/{id}` checks for a tenant-prefixed form
   first, falls back to the stock form. Today's
   `WebformApiController::submitWebform()` needs a resolver hook.
4. **`aabenforms_workflows.module`** adds a `hook_form_alter` or
   equivalent on the wizard to surface "save as tenant override"
   vs "edit shipped template" (admins-only for the latter).
5. **config_split** (already in Drupal contrib) cleanly separates
   per-tenant config exports from shipped defaults. Worth adopting
   before this becomes a config/sync export nightmare.

## Consequences

- **Clean tenant isolation.** A tenant can freely customize any
  template; their changes don't bleed into other tenants, and
  upstream template updates don't clobber customizations.
- **Flow count grows linearly with tenants × customized templates.**
  ECA lookup cost is O(1) per event dispatch (hashmap), but config
  export size scales linearly. At 50 tenants × 10 customizations =
  500 flow YAML files in config/sync. Workable but config_split
  becomes mandatory, not optional.
- **Test matrix multiplies.** Playwright API tests today assume one
  `/api/webform/hr_onboarding/submit` endpoint. With tenant routing,
  tests need to exercise multiple domain hosts (or mock the tenant
  resolver). Container Playwright setup already has `extra_hosts`
  so this is tractable.
- **Wizard UX becomes load-bearing.** Admins need a clear signal of
  "this is the shipped template (read-only), this is your tenant's
  override (editable)." Today's wizard has no such distinction.
  Real UX work, not just backend config.

## Why not now

- **One tenant in production.** The demo domain is the only active
  tenant on `api.aabenforms.dk`. Building a multi-tenant naming
  system for a single-tenant deployment is premature.
- **The prefix convention is non-breaking to adopt later.** Stock
  templates today have no prefix; they'd remain that way. New
  tenant-prefixed templates would layer on top without disturbing
  existing deployments.
- **`aabenforms_tenant` module is present but untested at scale.**
  Adding another concern on top before the tenant-resolution path
  itself has a second customer is building on sand.

## What to do when the trigger fires

1. Stand up a second Domain record (e.g. `aarhus.aabenforms.dk`) and
   confirm `TenantResolver` returns different tenants for different
   hosts.
2. Pick ONE template to customize for the second tenant as the
   reference implementation (probably `citizen_service_application`
   with a municipality-specific case-worker email).
3. Update `WorkflowTemplateInstantiator` + `WebformApiController`
   per the sketch above. Ship both changes in one PR so they stay
   consistent.
4. Adopt `config_split` and separate per-tenant config from shared
   defaults.
5. Extend Playwright to exercise per-tenant routing via the
   `extra_hosts` mechanism already in `docker-compose.test.yml`.
