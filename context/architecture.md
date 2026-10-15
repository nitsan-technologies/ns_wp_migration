# ns_wp_migration — Architecture

## Backend module

v12+ (`Configuration/Backend/Modules.php`):

| Key | Parent | Access | Path |
|---|---|---|---|
| `nitsan_module` | — (main module, `after web`, label "T3Planet") | — | — |
| `nsWpMigrationModule` | `nitsan_module` (`before top`), label "WP Migration" | `user` | `/module/web/importModule` |

- ns_license and other NITSAN extensions register `nitsan_module` as well, so they share that main-menu entry.
- Icons: main module `Icons/module-nswpmigration.svg`, submodule `Icons/icon.svg` (file paths, no identifiers).
- Page tree navigation: `@typo3/backend/tree/page-tree-element` on v13+, `@typo3/backend/page-tree/page-tree-element`
  on v12 (`inheritNavigationComponentFromMainModule` false). The selected page is not used by any action.
- Extbase actions: `PostController` → `import` (default), `importForm`, `logManager`, `downloadSample`.
- No `Configuration/Backend/Routes.php` or `AjaxRoutes.php`; no `ext_localconf.php`, plugins or content elements.

v11 (`ext_tables.php`): `ExtensionUtility::registerModule('Nitsan.NsWpMigration', 'nitsan', 'wp_migrate', …)`,
creating the `nitsan` main module in `$GLOBALS['TBE_MODULES']` if missing.

`ext_tables.php` on every major: registers the RTE preset (below), the Font Awesome stylesheet (Assets) and
the icons `module-nitsan` / `module-nswpmigration` (`Icons/<identifier>.svg`) through
`IconRegistry`; there is no `Configuration/Icons.php`.

## Classes

| Class | Role |
|---|---|
| `Controller/AbstractController` | Logger + `PersistenceManager` in `initializeAction()`, `checkValideFile()` (MIME whitelist) |
| `Controller/PostController` | All module actions, CSV parsing, page/content creation, image download, trial URL (`getTrialUrl()`) |
| `Domain/Model/LogManage` | One import run: record counts, storage pid, date, `addedBy` backend user |
| `Domain/Repository/ContentRepository` | Raw `QueryBuilder` writes: `createPageRecord()`, `updatePageRecord()` (delete + insert), `insertContnetElements()`, `findPageBySlug()` |
| `Domain/Repository/LogManageRepository` | `getAllLogs()` (all non-deleted rows, newest first) |

DI: `Configuration/Services.yaml` autowires `NITSAN\NsWpMigration\` and wires `PostController` explicitly
(`PageRepository`, `ContentRepository`, `LogManageRepository`, EXT:beuser `BackendUserRepository`, Extbase `UriBuilder`).

## Tables (`ext_tables.sql`)

| Table | Content |
|---|---|
| `pages` | adds `postid` (unused) |
| `tx_nswpmigration_domain_model_logmanage` | `number_of_records`, `total_success`, `total_fails`, `total_update`, `added_by`, `created_date`, plus unused `redirect_json`, `records_log`; `pid` = storage page of the import. TCA: `hideTable`; `added_by` is a `be_users` select with MM table `tx_nswpmigration_domain_model_logmanage_mm` (auto-created from TCA; the model maps it as `ObjectStorage<BackendUser>`) |

## Views

| File | Used by |
|---|---|
| `Layouts/Default.html` | v12+ both actions, v11 Import: CSS links, navbar (Import Manager, Log Manager), optional `premiumStrip` section, flash messages, `content` |
| `Layouts/DefaultV11.html` | v11 Log Manager: same plus `<script>` tags for jQuery, DataTables, `Mainv11.js` |
| `Templates/Post/Import.html` | Premium strip section + "Import WP Posts" card with `Partials/Importmanager.html` |
| `Partials/Importmanager.html` | Upload form: CSV file, post type (Page only), storage page id, Submit, "Download Sample" |
| `Templates/Post/LogManager.html` | Log table: records, inserted, updated, storage id, import date |

## Assets

- Backend CSS (layout `<link>`): `Vendor/bootstrap4.3.1.min.css`, `Css/global.css`, `Css/main.css`; Font Awesome
  via `BE.stylesheets` on v12+.
- `Configuration/JavaScriptModules.php` maps `@nitsan/ns-wp-migration/*` (two mapped files missing, see `core.md`).
  `importAction()` (v12+) loads `jquery.js`, `datatable.js`, `tom-select.complete.js`, `main.js` and the
  `locallang.xlf` labels inline.
- `Resources/Public/sample.csv`: 13 WordPress posts (10 `publish`, 3 `trash`, all `post_type` "post") in the
  column layout of the export plugin linked in the README (`jc-importer`); 8 contain remote `<img>` URLs.

## RTE preset

`ext_tables.php` registers the RTE preset `ns-importer` (`Configuration/RTE/Default.yaml`, imports
EXT:rte_ckeditor_image). `Configuration/page.tsconfig` (v12+, auto-loaded for every page) and `ext_tables.php`
(v11) import `TsConfig/Page/RTE.tsconfig` (`RTE.ns-importer.proc` allowed tags). Code-read: core reads RTE page
TSconfig only from `RTE.default.*` and `RTE.config.<table>.<field>.*`, a preset name is no TSconfig path, so
this block has no effect. `TCA/Overrides/pages.php` registers the selectable page TSconfig "Wp Migration ::
Config RTE Preset", which sets `RTE.default.preset = ns-importer`.

## Labels

`locallang.xlf` (UI), `BackendModule.xlf` (main module "T3Planet"), `locallang_psimportexport.xlf` (submodule
"WP Migration"), `locallang_db.xlf` (TCA).
