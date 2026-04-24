# AabenForms Digital Post

Send SF1601 Digital Post from any modern Drupal 11 site, without the OS2-ecosystem dependency maze that makes `os2forms_digital_post` painful to adopt.

- Hard Drupal dependencies: `webform`, `key`. That's it.
- One composer library dependency: `itk-dev/serviceplatformen` (bundled MeMo and Fjernprint types included).
- Four test modes: `fake_db` (default, zero-config), `wiremock`, `live_test`, `live`.
- Public API is a single value object and a single service call; no webform coupling in the core module.
- Delivery-status receipts, OS2Web certificate storage, and advanced queueing all live in optional bridge submodules (session 3).

## Install

```sh
composer require itk-dev/serviceplatformen -W
drush pm:enable aabenforms_digital_post
```

## Configure

```sh
# Install default is fake_db. Nothing else required to start.
drush af:dp:status

# Set your sender CVR when ready.
drush config:set aabenforms_digital_post.settings sender_cvr 12345678 -y

# Switch transports by flipping one key.
drush config:set aabenforms_digital_post.settings test_mode wiremock -y
drush config:set aabenforms_digital_post.settings test_mode live_test -y   # session 2+
```

Admin form: `/admin/config/aabenforms/digital-post`.

## Send

```php
use Drupal\aabenforms_digital_post\DigitalPost\DigitalPost;
use Drupal\aabenforms_digital_post\DigitalPost\Recipient;
use Drupal\aabenforms_digital_post\DigitalPost\Sender;

$result = \Drupal::service('aabenforms_digital_post.sender')->send(
  new DigitalPost(
    recipient: Recipient::cpr('0101900000'),
    sender: Sender::fromConfig(\Drupal::configFactory()),
    subject: 'Afgørelse',
    body: '<p>Hej</p>',
  )
);
if ($result->isSuccess()) {
  $tx = $result->transactionId;
}
else {
  // $result->reasonCode is one of CERT_INVALID, RECIPIENT_UNKNOWN,
  // RECIPIENT_NOT_REACHABLE, QUOTA, TRANSPORT, VALIDATION, UNKNOWN.
}
```

## Smoke test from the CLI

```sh
drush af:dp:send --to=0101900000 --subject="Test" --body="<p>Hello</p>"
drush af:dp:log:tail
```

## Test modes

| Mode | What it does | Dependencies |
|------|-------------|--------------|
| `fake_db` | Writes the would-be payload to `{aabenforms_digital_post_log}` and returns a synthetic receipt. The "plug-and-play on any Drupal 11" default. | None |
| `wiremock` | POSTs a JSON body to a WireMock endpoint (`wiremock_url` setting). For CI and DDEV dev loops. | WireMock reachable at configured URL |
| `live_test` | Real `itk-dev/serviceplatformen` SF1601 client against Serviceplatformen's exttest endpoint. | Real test certificate |
| `live` | Production Serviceplatformen endpoint. | Real production certificate |

`live_test` and `live` ship in session 2 alongside a real `MemoBuilder` that constructs SF1601-compliant MeMo XML.

## What ships in session 1

- `DigitalPost`, `Attachment`, `Recipient`, `Sender`, `Result` value objects with format validation in constructors.
- `DigitalPostSender` service (`aabenforms_digital_post.sender`).
- `Sf1601ClientInterface` transport contract.
- `FakeSendDatabaseLogger` transport (default).
- `WireMockSoapClient` transport.
- `CertificateLocatorInterface` + `FileCertificateLocator` + factory.
- `AuditEmitterInterface` + `CoreAuditEmitter` (wraps `aabenforms_core.audit_logger`).
- `TransactionIdGenerator` (UUID v7).
- `SettingsForm` with inline validation at save time.
- Drush commands: `af:dp:send`, `af:dp:log:tail`, `af:dp:status`.
- `{aabenforms_digital_post_log}` table.

## What does NOT ship in session 1

- Real MeMo XML via `itk-dev/serviceplatformen`. Both transports currently carry either a JSON body (wiremock) or a serialized DTO in the log table (fake_db). Session 2 adds the real `MemoBuilder` and wires the live and live_test transports.
- Delivery status (Beskedfordeler callback). Separate submodule `aabenforms_digital_post_beskedfordeler`, session 3.
- Fjernprint physical mail fallback. If a recipient is not reachable digitally, we return `RECIPIENT_NOT_REACHABLE` and let the caller decide. A dedicated physical-mail module can ship separately.
- `os2web_key`, `os2web_audit`, `os2web_datalookup`, `advancedqueue` bridges. All optional, session 3.
- Webform handler. Lives in `aabenforms_digital_post_webform` submodule, session 2.
- ECA action plugin. Lives in `aabenforms_digital_post_eca` submodule, session 2.

## Files

```
aabenforms_digital_post/
├── aabenforms_digital_post.info.yml
├── aabenforms_digital_post.services.yml
├── aabenforms_digital_post.routing.yml
├── aabenforms_digital_post.permissions.yml
├── aabenforms_digital_post.links.menu.yml
├── aabenforms_digital_post.install
├── drush.services.yml
├── config/
│   ├── install/aabenforms_digital_post.settings.yml
│   └── schema/aabenforms_digital_post.schema.yml
└── src/
    ├── Audit/                    AuditEmitter interface + Core/Null impls
    ├── Certificate/              Certificate DTO + Locator interface + File impl + Factory
    ├── DigitalPost/              DigitalPost, Attachment, Recipient, Sender, Result DTOs
    ├── Drush/                    DigitalPostCommands
    ├── Event/                    (placeholder; events land in session 2)
    ├── Exception/                DigitalPostException + subtypes
    ├── Form/                     SettingsForm
    ├── Service/                  DigitalPostSender, Sf1601Client interface + factory, TransactionIdGenerator
    └── TestMode/                 FakeSendDatabaseLogger, WireMockSoapClient
```

## License

MIT. The core module has no AabenForms-specific assumptions in runtime code; a project wanting to rebrand can fork and rename. Report issues at https://github.com/madsnorgaard/aabenforms/issues.
