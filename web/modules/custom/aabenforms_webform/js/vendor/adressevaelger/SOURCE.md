# Vendored: Klimadatastyrelsen Adressevælger

Address autocomplete widget (DAWA's successor; DAWA is decommissioned 17 Aug 2026).

- Upstream: https://github.com/Klimadatastyrelsen/adressevaelger
- Files: `dist/adressevaelger.iife.js`, `dist/adressevaelger.css`
- Commit: `a84b91477f93c9b4d63b60b45ab8262ef93011b3` (main)
- Vendored because the project is not published to npm/CDN.

Requests are routed through the in-app proxy (`/aabenforms/adressevaelger/...`,
see `AdressevaelgerProxyController`) so the access token stays server-side. To
update, re-download both files from the same `dist/` path and bump the commit above.
