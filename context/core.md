# ns_wp_migration — Core facts

## Identity

| Item | Value |
|---|---|
| Extension key | `ns_wp_migration` |
| Composer name | `nitsan/ns-wp-migration` |
| Title (ext_emconf) / product name | TYPO3 WordPress Migration Tool / WordPress to TYPO3 Migration |
| Namespace | `NITSAN\NsWpMigration\` → `Classes/` |
| Version (ext_emconf) | 14.1.0 (latest tag 14.0.0) |
| Repository | https://github.com/nitsan-technologies/ns_wp_migration (default branch `main`) |
| Licensing status | **Freemium** — this repo is the free version. The NITSAN Free/Freemium sheet now needs a Google login; status taken from the T3Planet catalog (`isFree: false` for the premium product) |
| Premium version (T3Planet catalog) | "WordPress to TYPO3 Migration", €229, https://t3planet.de/en/typo3-wordpress-migration-extension |

## Compatibility

| Source | TYPO3 | PHP |
|---|---|---|
| `ext_emconf.php` | `11.5.0-14.9.99` | `7.4.0 - 8.5` |
| `composer.json` | `^11.5 \|\| ^12.4 \|\| ^13.4 \|\| ^14.0` | not declared |
| ns_license 14.5.4 (dependency) | `12.4.18-14.9.99` | not declared (TYPO3 12.4 needs 8.1+) |
| Premium product (catalog) | v13, v14 | `^8.2 \|\| ^8.3 \|\| ^8.4` |

- Effective minimum is **TYPO3 12.4.18 / PHP 8.1** because of ns_license. The TYPO3 v11 branches in the code
  (`ext_tables.php` module registration, `DefaultV11.html`, `Mainv11.js`) can no longer be installed with the
  declared dependency; they are kept, not removed.

## Dependencies

| Package | Constraint | Why |
|---|---|---|
| `typo3/cms-core` | see above | core |
| `nitsan/ns-license` / `ns_license` | `^14.5.4` / `14.5.4-14.9.99` | TYPO3 Shop module + free-trial deep link (see `features/premium-cta.md`) |
| `ezyang/htmlpurifier` | `^4.19` (Composer) | Cleans `post_content`; Classic mode uses the bundled copy in `Library/htmlpurifier` |
| `netresearch/rte-ckeditor-image` / `rte_ckeditor_image` | `^11 … ^14` / `11.0.2-14.9.99` | Only imported by the `ns-importer` RTE preset (`Configuration/RTE/Default.yaml`) |
| `typo3/cms-beuser` | not declared | `PostController` injects `BackendUserRepository` (log `added_by`) |

## Tooling

- No PHPUnit, PHPStan, CS fixer or package.json in the repo.
- CI: `.github/workflows/ter-release.yml` only (publishes tags to TER).
- `.gitattributes` export-ignores `.crowdin.yml`, `.github/`, `AGENTS.md`, `CLAUDE.md`, `context/`, `tasks/` and
  itself, so they are not in the GitHub/Composer archive or the TER package (the TER release uploads the GitHub tag
  archive).
- Translations: Crowdin (`.crowdin.yml`), source files `Resources/Private/Language/*.xlf`.

## Conventions

- Extbase backend module; v12+ renders through `ModuleTemplateFactory::create()->renderResponse()`, v11 through
  `$this->view`.
- Labels from `locallang.xlf` via `<f:translate>`; tab-indented XLF.
- Backend CSS: `Resources/Public/Css/global.css` and `main.css` (4-space indents, LF), vendored Bootstrap 4.3.1,
  all linked from `Layouts/Default.html`. Font Awesome is added to every backend page on v12+
  (`$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']`).

## Known issues (existing, not fixed)

Behaviour details: `features/csv-import.md`, `features/backend-module.md`.

Verified live on v14.3.7:

- The navbar brand is empty: the layouts translate `brandname`, which is missing from `locallang.xlf`.

Code-read:

- `Configuration/JavaScriptModules.php` maps `select2.min.js` and `tom-select.complete.js`, which don't exist in
  `Resources/Public/JavaScript/`; `importAction()` loads `tom-select.complete.js`.
- `logManagerAction()` loads no JavaScript, so `Main.js` never initialises DataTables on the log list (v12+).
- Re-importing a slug hard-deletes the old page row and inserts a new one (new uid); the old page's content
  elements stay behind with the deleted pid, and the post content is inserted again.
- Pages and content are written with raw `QueryBuilder` inserts, bypassing DataHandler: no sorting, history,
  reference index, workspace or cache handling, and no page permission check.
- Every `<img src>` in `post_content` is fetched with `file_get_contents()` (any URL, no validation or timeout)
  and written to `fileadmin/user_upload/<basename>`, overwriting files with the same name.
- The post type select only offers "Page"; `createPagesAndBlog()` ignores `$dockType` and always creates
  `doktype` 1. `pages.postid` (`ext_tables.sql`) is never written.
- `createPagesAndBlog()` logs every created page with `$this->logger->error()`.
- `Templates/Post/Import.html` hard-codes the card header "Import WP Posts".
- `ext_tables.php` (RTE preset, icons, Font Awesome stylesheet; see `architecture.md`) is deprecated since TYPO3
  14.3 and removed in v15; the RTE preset belongs in `ext_localconf.php`, the icons in `Configuration/Icons.php`.
- `Layouts/Default.html` links `backend.css?{bust}` with an undefined `bust` and loads Bootstrap 4.3.1 on top
  of the backend CSS.
- README badges show v14.0.0 and v2.0.0.
