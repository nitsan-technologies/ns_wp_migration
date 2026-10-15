# ns_wp_migration — Session state

## 2026-10-14 — ns_license integration + premium strip + agent context

- Working copy on `main` (dfaab04), uncommitted; no feature branch created.
- Added `nitsan/ns-license ^14.5.4` / `ns_license 14.5.4-14.9.99` (effective minimum now TYPO3 12.4.18).
  Version 14.0.0 → 14.1.0.
- Import Manager premium strip with "Start Free Trial" → ns_license module `?trial=ns_wp_migration#free-section`,
  team-wide wording and design (see `features/premium-cta.md`).
- Verified live on TYPO3 14.3.7 classic (temporary activation via `PackageStates.php`, no schema update,
  temporary system-maintainer admin; all restored byte-identical / removed). PHP 8.4 and 7.4 lint clean.
- Created agent context (`AGENTS.md`, `CLAUDE.md`, `context/`, `tasks/`); no previous context existed.
- Open: ns_license 14.5.4 not yet published; CSV import and Log Manager only code-read; v12/v13 not tested.

## 2026-10-15 — Context audit

- Code-read audit, no wrong facts found; added `ext_tables.php` work on v12+ (icons, preset, stylesheet) and its
  14.3 deprecation, module icons/navigation, `added_by` MM relation, ineffective `RTE.ns-importer` TSconfig.
