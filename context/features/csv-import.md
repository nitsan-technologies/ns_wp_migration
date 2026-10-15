# Feature — CSV import (WordPress posts → TYPO3 pages)

*Status: Done. Code-read; no import was run during the 2026-10-14 session (it writes pages and downloads remote
images). Known issues are listed in `core.md`.*

## Input

Form in `Partials/Importmanager.html` (Import Manager page), posted to `importFormAction()`:

| Field | Notes |
|---|---|
| `dataFile` | CSV upload, `accept=".csv"`. v12+ reads `$_FILES['dataFile']` directly, v11 the Extbase argument |
| `postType` | Select with only "Page" (`page`) |
| `storageId` | Page uid; required, must exist (`PageRepository::getPage()`), otherwise an error flash message |

`Main.js` intercepts the submit click: without a file it alerts "Please select a CSV file to process.", otherwise
it alerts "Processing, please wait…", disables the button and submits. "Download Sample" (`downloadSampleAction()`)
streams `Resources/Public/sample.csv` and exits.

## Validation (`importCsvData()`)

- MIME type must be in `AbstractController::checkValideFile()` (CSV types, `application/octet-stream`,
  `text/plain`, Excel types).
- First row = column names. Any row with a different column count aborts the whole import
  (`error.invalidfileData`).
- Row 1 must have `post_title` and `post_type`, otherwise `error.invalidfileData`.

## Per row (`createPagesAndBlog()`)

- Rows without `post_title` count as fails. Rows with `post_status` `trash` are skipped (counted in
  `number_of_records` only).
- Page: `title` = `post_title`, `slug` = `/` + title lower-cased, non-alphanumerics removed, spaces → `-`;
  `pid` = storage page, `doktype` 1, `hidden` = 1 for `draft`, `crdate` from `post_date` (`d/m/y`, else the
  date part as is).
- If a page with that slug already exists below the storage page, it is deleted and re-created (counted as
  update); otherwise inserted (counted as success).
- `post_content` → one `tt_content` `text` element (`colPos` 0) on the page:
  1. HTMLPurifier (empty tags removed, no auto paragraphs; `data-htmlarea-*`, `data-title/alt-override`
     allowed on `img`).
  2. Each `<img src>` is downloaded to `fileadmin/user_upload/<basename>`, looked up in the default storage
     and rewritten to the FAL public URL with `data-htmlarea-file-uid`, 930×523, title/alt = file name.
  3. Purified again. Any exception keeps the first purified HTML.
- Finally one `LogManage` row (pid = storage page, counts, date, current backend user) and
  `BackendUtility::setUpdateSignal('updatePageTree')`, flash message `import.success`, redirect to Import.
