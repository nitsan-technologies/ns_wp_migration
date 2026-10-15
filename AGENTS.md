# AGENTS.md — ns_wp_migration (WordPress to TYPO3 Migration)

Agent router for the WordPress Migration free extension. Load only what your task needs.

> **Principles:** `context/principles.md`
> **Session log:** `context/session-state.md`
> **Doc index:** `context/docs-map.md`

---

## Always load

| File | Why |
|---|---|
| `context/principles.md` | Guardrails, "nothing that works may break" rule |
| `context/core.md` | Identity, TYPO3/PHP/ns_license constraints, tooling, known issues |

---

## Task router

| Task | Load |
|---|---|
| CSV upload, page / content creation, image download, log record | `context/features/csv-import.md` |
| Backend module (layout, navbar, Import Manager page, Log Manager list) | `context/features/backend-module.md` |
| Premium strip / free trial / ns_license dependency | `context/features/premium-cta.md` |
| Classes, tables, templates, routes, assets, RTE preset | `context/architecture.md` |
| Lint / validate / manual test | `tasks/run-quality.md` |
| Update agent context after a change | `tasks/context-update.md` |

---

## Feature status

| Feature | Status | Agent entry |
|---|---|---|
| CSV import of WordPress posts into standard pages with one text element each | Done (code-read) | `context/features/csv-import.md` |
| Backend module: Import Manager form, Log Manager list | Done | `context/features/backend-module.md` |
| Premium strip + ns_license free-trial deep link | Done (needs ns_license 14.5.4 release) | `context/features/premium-cta.md` |

---

## Quick commands

```bash
cd packages/ns_wp_migration        # or typo3conf/ext/ns_wp_migration in Classic mode
for f in $(find . -name "*.php" -not -path "./.git/*" -not -path "./Library/*"); do php -l "$f"; done
composer validate --no-check-publish
for f in Resources/Private/Language/*.xlf; do xmllint --noout "$f"; done
```
