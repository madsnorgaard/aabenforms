# Testing

Working playbook for keeping AabenForms's PHPUnit suite green and coverage
moving up. Lessons from a real test-rot triage session, written down so the
next person does not relearn them. For broader strategy (functional tiers,
browser tests, fixtures) see `TESTING_GUIDE.md`. This doc is about the unit
and kernel suite that gates CI.

## Coverage gate

CI runs the `unit,kernel` suites on every push and pull request that touches
`web/modules/custom/**`, `composer.json`, `composer.lock`, `phpunit.xml`, or
the workflow itself. After the run it parses `coverage/cobertura.xml` and:

- Fails the job if line coverage drops below **20%**. The step is named
  `Enforce minimum coverage threshold` in `.github/workflows/ci.yml`. Raise the
  number in lockstep when the team agrees a new floor; do not lower it.
- Writes two badge JSON files that the README links to live:
  - `.github/badges/coverage.json` - the percent figure plus a colour bucket.
  - `.github/badges/tests.json` - passing-count from the JUnit log.
- On `main` only, commits the regenerated badge JSON back to the branch with
  `[skip ci]` so the README shields refresh.

The README badges read those JSON blobs through `img.shields.io/endpoint`, so
there are no hardcoded numbers anywhere - the badges follow CI automatically.

## Test-rot triage methodology

When a test suite has been red for a while, do not start fixing files in the
order PHPUnit prints them. Bucket every skip and failure first, then attack
the biggest bucket. The four buckets we found in this codebase:

1. **PHPUnit 11 deprecation removals.** Tests using `withConsecutive(...)`,
   `setMethods(...)`, or `->at($n)` no longer compile under PHPUnit 11. The
   replacements are `->willReturnCallback()` driven by a counter, plus
   `onlyMethods()` / `addMethods()`. These are mechanical fixes - do them in
   bulk.
2. **Method signature drift.** The production class API changed (extra
   constructor argument, renamed method, return type tightened) but the test
   still asserts the old shape. Fix the test against the current production
   signature; do not roll back production to match the test.
3. **Misdiagnosed skip reason.** The `markTestSkipped` message blames X but
   production actually does Y. Read the production code, do not trust the
   skip text. We found tests skipped for "needs container" that actually
   needed a one-line mock, and tests skipped for "API drift" where the API
   was identical and only the assertion was wrong.
4. **Genuine production refactor needed.** Rare. The test is right, production
   is wrong, and fixing it is out of scope for the test PR. Document precisely
   what the production gap is and re-skip with a sharper message that names
   the missing capability, not the symptom.

## Patterns the suite relies on

### Multi-event-type service test pattern

When a service exposes both a generic `log($event, $payload)` and convenience
wrappers like `logCprLookup(...)`, multi-event-type ECA actions call the
generic `log()` and pass the event name as a parameter. Tests must mock the
generic call, not the convenience wrapper. Mocking `logCprLookup` for an
action that only ever calls `log('cpr_lookup', ...)` produces a "method never
called" failure that is easy to misread as broken production code.

### The `(bool) SimpleXMLElement` footgun

A `SimpleXMLElement` whose root has only namespaced children evaluates to
`FALSE` in boolean context unless a non-namespace attribute (`id`,
`targetNamespace`, anything in the no-namespace) is present. Production
already gates with `if (!$xml) { return []; }` and never trips this because
real BPMN files always carry both `id` and `targetNamespace` on the root.
Test fixtures must mirror that. A minimal fixture like
`<bpmn:definitions xmlns:bpmn="..."><bpmn:process/></bpmn:definitions>` will
short-circuit to `[]` and your assertion will fail for a reason that is not
in the test.

### ECA 3.1+ kernel test requirement

ECA 3.1.x added a hard dependency on `modeler_api`. Every kernel test that
enables `eca` must also enable `modeler_api` in `$modules`. See
`~/.claude/skills/eca-workflow-expert/SKILL.md` for the full ECA testing
notes. This is not optional - the container will fail to build without it.

### Setter-injected execution collector

`AabenFormsActionBase::setExecutionCollector(ExecutionCollectorInterface)` is
public on purpose. It lets unit tests wire a mock collector without going
through reflection or the container. New ECA actions should subclass
`AabenFormsActionBase` and let tests use this setter rather than rolling
their own injection point.

## Where to look for help

- `~/.claude/skills/eca-workflow-expert/SKILL.md` - ECA, BPMN, and
  modeler_api testing patterns.
- `~/.claude/skills/drupal-playwright-expert/SKILL.md` - Playwright e2e
  patterns for the Nuxt frontend hitting this backend.

These skills live in the developer's home directory, not the repo. Mention
them when onboarding a new contributor.

## Before-you-skip checklist

Before adding `markTestSkipped`, answer all four:

1. Which of the four buckets does this fall into - PHPUnit 11 deprecation,
   signature drift, misdiagnosed reason, or genuine production gap?
2. Have I read the production code the test exercises, or am I only reading
   the test and the failure message?
3. If I skip this, what concrete behaviour stops being verified? Write that
   sentence into the skip message.
4. Is there a tracking issue or follow-up commit that will unskip it? If
   not, the skip is permanent loss of coverage - reconsider fixing it now.
