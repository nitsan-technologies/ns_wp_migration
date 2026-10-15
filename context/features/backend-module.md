# Feature — Backend module (WP Migration)

*Status: Done. Import Manager page verified live on TYPO3 14.3.7 (2026-10-14); Log Manager code-read.*

Registration and routes: see `architecture.md`. Menu: **T3Planet → WP Migration**, with the page tree (the
selected page is ignored).

## Layout (`Layouts/Default.html`)

Links its own CSS (backend.css, Bootstrap 4.3.1, `global.css`, `main.css`), then a navbar with an (empty, see
`core.md`) brand and two tabs: **Import Manager** (`import`) and **Log Manager** (`logManager`). Below the navbar:
the optional `premiumStrip` section, flash messages, then the `content` section. No doc header buttons.

## Import Manager (`importAction()`)

- Premium strip below the navbar (`premium-cta.md`).
- Card "Import WP Posts" with the upload form; behaviour in `csv-import.md`.

## Log Manager (`logManagerAction()`)

- One row per import run from `tx_nswpmigration_domain_model_logmanage` (all runs, newest first, not filtered by
  the selected page): total records, inserted, updated, storage page id, import date (`Y-m-d`).
- Fails and the backend user are stored but not shown.
- No premium strip (the template defines no `premiumStrip` section).
