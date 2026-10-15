# Run quality checks

The repo has no PHPUnit, PHPStan or CS config. Available checks:

```bash
# PHP syntax (skip the bundled HTMLPurifier); also run once with PHP 7.4, the lowest declared version
for f in $(find . -name "*.php" -not -path "./.git/*" -not -path "./Library/*"); do php -l "$f"; done

# composer.json
composer validate --no-check-publish

# XLF well-formedness
for f in Resources/Private/Language/*.xlf; do xmllint --noout "$f"; done
```

## Manual test (per TYPO3 major 12 / 13 / 14)

Setup: activate the extension (database schema needed for the log table). Use a throw-away storage page.

1. Backend: T3Planet → WP Migration. Import Manager shows the premium strip below the navbar, then the
   "Import WP Posts" form.
2. "Start Free Trial" (as system maintainer) opens the ns_license module and its trial modal with
   "WordPress to TYPO3 Migration (ns_wp_migration)" preselected. Cancel; don't start a trial.
3. Submit without a file → alert. Wrong storage id → "error.pageId" flash message.
4. Import `sample.csv` (Download Sample) into the storage page: 10 pages (the 3 `trash` rows are skipped), one
   text element each, images in `fileadmin/user_upload/` (needs outgoing HTTP). Success flash message.
5. Import again: same 10 slugs are counted as updates (pages get new uids; see `core.md` known issues).
6. Log Manager lists both runs; no premium strip there.
7. Check light and dark backend themes.

Remove the imported pages, their content elements, the downloaded files and the log rows afterwards.
