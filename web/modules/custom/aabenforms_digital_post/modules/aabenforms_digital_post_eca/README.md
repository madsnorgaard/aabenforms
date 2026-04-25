# AabenForms Digital Post - ECA

ECA action plugin that lets BPMN workflows and ECA flows send a SF1601 Digital Post via the `aabenforms_digital_post.sender` service.

A thin bridge between two concerns: BPMN/ECA orchestration on one side, the Digital Post transport on the other. The plugin does not know about SOAP, MeMo XML, certificates, or test modes — those all live behind the `DigitalPostSender` service boundary.

## Install

```sh
drush pm:enable aabenforms_digital_post_eca
```

Hard deps: `aabenforms_digital_post`, `aabenforms_workflows`, `eca:eca`, `eca:eca_content`. Configure the underlying core module first (sender CVR + `test_mode`) — see `aabenforms_digital_post/README.md`.

## Plugin id

`aabenforms_digital_post_send` — appears in the ECA modeller and is callable from any BPMN template via the `<aabenforms:ecaAction>` extension element our `WorkflowTemplateInstantiator` reads.

## Configuration

| Key | Required | Description |
|-----|----------|-------------|
| `recipient_token` | Yes | ECA token resolving to a CPR or CVR. Examples: `[citizen_session:cpr]`, `[webform_submission:values:applicant_cpr:raw]`. |
| `recipient_type` | Yes | `cpr` (citizen) or `cvr` (company). |
| `sender_cvr_token` | No | Override the module-default sender CVR. Empty = use `aabenforms_digital_post.settings.sender_cvr`. |
| `subject_template` | Yes | Literal subject or `[token]`-containing template. |
| `body_template` | Yes | HTML body (Digital Post supports limited HTML). |
| `type` | No | One of `Digital Post`, `Automatisk Valg`, `Fysisk Post`, `NemSMS`. Default `Digital Post`. |
| `result_token` | No | Token name that receives the typed Result. Default `digital_post_result`. Keys: `success` (bool), `transaction_id`, `reason_code`, `message`. |

## Recipient resolution

The action resolves `recipient_token` in two strategies, in order:

1. **Webform submission shortcut.** If the token matches `[webform_submission:values:FIELD:raw]`, read directly from the submission entity exposed by the triggering ECA event via `getElementData()`. Handles the common case without depending on ECA's key-based token registry.
2. **ECA token data registry.** Falls back to `tokenService->getTokenData()` for session-backed tokens populated by earlier actions (e.g. `MitIdValidateAction` writes a `citizen_session` token containing CPR).

Empty string → action records a "skipped" step with reason `RECIPIENT_EMPTY` and writes a failure Result. Common in demo mode without a real MitID session, or when the webform field is unset.

## Usage in BPMN

```xml
<bpmn:serviceTask id="Task_DigitalPost" name="Deliver Decision via Digital Post">
  <bpmn:extensionElements>
    <aabenforms:ecaAction plugin="aabenforms_digital_post_send">
      <aabenforms:config key="recipient_token">[webform_submission:values:applicant_cpr:raw]</aabenforms:config>
      <aabenforms:config key="recipient_type">cpr</aabenforms:config>
      <aabenforms:config key="subject_template">Afgørelse på din ansøgning</aabenforms:config>
      <aabenforms:config key="body_template">&lt;p&gt;Se bilag i Digital Post.&lt;/p&gt;</aabenforms:config>
      <aabenforms:config key="type">Digital Post</aabenforms:config>
      <aabenforms:config key="result_token">digital_post_result</aabenforms:config>
    </aabenforms:ecaAction>
  </bpmn:extensionElements>
  ...
</bpmn:serviceTask>
```

The shipped `citizen_service_application.bpmn` template is the reference example: both the Approved and Rejected branches call this plugin with distinct subject + body templates.

## What the action does

1. Resolves the recipient via the strategies above.
2. Builds a `DigitalPost` DTO using config + `Sender::fromConfig()` (or the override token).
3. Calls `DigitalPostSender::send()`, which routes through whatever `test_mode` is configured.
4. Records a `workflow.steps` entry (`Digital Post sent` / `Digital Post failed`) including the active mode and reason.
5. Writes the typed Result (`success`, `transaction_id`, `reason_code`, `message`) into the configured result token so downstream actions can branch.

All exceptions are caught and surfaced as a failed Result with `reason_code=VALIDATION` rather than crashing the flow.

## License

MIT.
