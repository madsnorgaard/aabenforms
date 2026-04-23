# ADR 0001: async queueing for external APIs

**Status:** Accepted in principle, deferred for implementation.
**Trigger:** First real Serviceplatformen integration shipping
(CPR/CVR/Digital Post no longer mocks). Reopen this ADR then.

## Context

Every ECA flow today runs synchronously inside the HTTP request that
triggered it. A POST to `/api/webform/{id}/submit` does not return
until the whole action DAG has executed. Right now this is fine because
every external-facing service (`PaymentService`, `SmsService`,
`PdfService`, `CalendarService`, and the `ServiceplatformenClient`
stubs for SF1520/SF1530/SF1601) is a mock that logs and returns
immediately.

The moment one of those becomes a real integration, submission
latency becomes the sum of all external API round-trips invoked by
the flow. A slow Serviceplatformen SOAP call, a Nets Easy 500, a
timeout from a gov-operated endpoint - any of them stalls the
citizen's browser waiting for `201`. That is unacceptable at any
volume above a hobby load.

## Decision

When the first real external integration lands:

1. **Install and enable `eca_queue`** (already shipped with the ECA
   contrib module at `web/modules/contrib/eca/modules/queue/`).
2. **Split flows that call external services into two halves:**
   - A synchronous "fast half" that runs inline on the submit
     request. Does audit logging, input validation, token
     pre-population, and enqueues a follow-up task via
     `eca_queue`'s `EnqueueTask` action.
   - A queued "slow half" that subscribes to `ProcessingTaskEvent`
     (`web/modules/contrib/eca/modules/queue/src/Event/ProcessingTaskEvent.php`),
     does the SOAP call, and writes results back to the submission
     entity (or to a dedicated state entity).
3. **Opt-in, not global.** Each flow decides whether a given action
   is sync or queued. Flows that only audit-log stay fully sync.
4. **Drive the worker via Drupal cron** (or a systemd timer on the
   prod container if cron runs need to be faster than every 15
   minutes).

## Pattern template

One synchronous flow:

```yaml
actions:
  log_submission_received:
    plugin: aabenforms_audit_log
    # ... fast stuff ...
    successors:
      - id: enqueue_lookup
  enqueue_lookup:
    plugin: eca_enqueue_task
    configuration:
      task_name: 'citizen_service_cpr_lookup'
      data_token: '[webform_submission:uuid]'
    successors: {  }
```

One queued flow subscribing to the task:

```yaml
events:
  on_cpr_lookup_task:
    plugin: 'eca_queue:processing_task'
    configuration:
      task_name: 'citizen_service_cpr_lookup'
    successors:
      - id: mitid_validate
actions:
  mitid_validate:
    plugin: aabenforms_mitid_validate
    # ... slow stuff ...
```

This keeps both halves declarative and discoverable in config/sync.

## Consequences

- **Submission latency stays bounded** to the fast half (audit log +
  enqueue), independent of external service health.
- **Failure modes shift.** A failed SOAP call no longer fails the
  submission; the submission succeeds and a queued retry runs. Means
  the frontend needs a way to surface late-arriving decisions
  (already partly solved by `WorkflowExecutionCollector` returning a
  steps array; that contract will extend to "pending" steps).
- **Testing changes.** Playwright API tests that today assert the
  full flow completed synchronously will need to either run cron or
  assert "accepted for processing" instead. Plan: add a
  `test:process-queue` drush command that tests call after the
  submit.
- **Operational cost.** Cron worker slots become a shared resource.
  At ~10k submissions/day and ~3 queued tasks each, that's ~30k
  tasks/day to work through. A single-worker cron running every 15
  min handles ~10 tasks per run if each task averages 90s - that's
  too slow. Means either a tighter cron cadence or dedicated worker
  processes on the container.

## Why not now

- **No real integration has shipped.** Rebuilding the split
  sync/queued pattern for six stubs that all log-and-return is
  premature complexity.
- **The split is cheap to retrofit.** Converting a flow from sync to
  the two-flow pattern is mechanical (copy the heavy actions into
  the task subscriber flow, add an `EnqueueTask` in the original).
  No schema migration, no data rework.
- **Better once we have data.** The right batch size, cron cadence,
  and retry policy depend on real service latency profiles. Guessing
  numbers now risks locking in the wrong defaults.

## What to do when the trigger fires

1. Pick the first real integration (likely `CprLookupAction` against
   the test SF1520 endpoint).
2. Convert `citizen_service_application_flow` to the split pattern
   as the reference implementation.
3. Add a Playwright assertion for "submission accepted, decision
   pending" state.
4. Measure one week of production queue depth before repeating for
   CVR, Digital Post, payment.
