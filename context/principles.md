# ns_wp_migration — Operating Principles

*Every change to code, context or docs must serve the goals below.*

---

## Goal 1 — Nothing that works may break

The code still carries TYPO3 v11 branches next to v12–v14 (Composer and Classic mode), see `core.md`.

- Keep every declared major working. Follow the existing version switches (`Typo3Version` in `PostController`
  and `ext_tables.php`, `VersionNumberUtility` in `Configuration/Backend/Modules.php`) instead of inventing new ones.
- `ext_emconf.php` still declares PHP 7.4: no promoted constructor properties, `readonly`, `match`, enums or
  `catch` without a variable in extension code.
- Prefer the smallest change that fixes the root cause. No drive-by refactors.
- Run `tasks/run-quality.md` and re-test the affected flow in the backend before claiming done.

## Goal 2 — Token-cost efficiency (agent context)

- **DRY context:** each fact lives in exactly ONE file. Reference, don't copy.
- **Load on demand:** read `context/features/<area>.md` for the task, not everything.
- **Prune as you go:** keep `context/session-state.md` short.

## Goal 3 — Accuracy and trust

- Answer from context + code, never from invented API, table or field names.
- Mark statements as verified live or code-read; conflicting facts across files = fix the source file.

---

## Guardrails (non-negotiable)

- An import writes straight into `pages` / `tt_content` and replaces pages with the same slug (see
  `features/csv-import.md`). Test imports only on a throw-away storage page and remove the pages afterwards.
- Imported HTML always goes through HTMLPurifier; never store `post_content` unpurified.
- `Library/` is the bundled HTMLPurifier for Classic mode (classmap in `ext_emconf.php`). Don't edit it.
- Do not change ns_license from an ns_wp_migration task; reuse its module and trial modal.
- All UI text goes into `Resources/Private/Language/locallang.xlf`, no hard-coded labels. Only the English
  source files are edited; translations come from Crowdin (`.crowdin.yml`).

---

## Apply this whenever you

- Edit `context/` or `AGENTS.md`
- Land a feature or bugfix (update the matching `context/features/*.md` + `session-state.md`)
