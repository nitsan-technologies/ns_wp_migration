# Feature — Premium strip and ns_license free trial

*Status: Done. Verified live on TYPO3 14.3.7 (2026-10-14): strip renders, trial modal opens with "WordPress to
TYPO3 Migration (ns_wp_migration)" preselected. Requires ns_license 14.5.4 (deep-link support) to be released.*

## Licensing status

Freemium. Premium product (T3Planet catalog): "WordPress to TYPO3 Migration", €229,
https://t3planet.de/en/typo3-wordpress-migration-extension (not linked from the UI; the trial stays inside the
TYPO3 backend).

Premium adds (catalog): custom field mapping, WordPress page export, text media and multilingual content
migration, custom table fields; it also depends on EXT:news, EXT:blog and md_news_author. The free version only
creates standard pages with one text element each.

## Dependency

`composer.json` `"nitsan/ns-license": "^14.5.4"`, `ext_emconf.php` `'ns_license' => '14.5.4-14.9.99'`. This
raises the effective minimum to TYPO3 12.4.18 (see `core.md`).

## Strip

- Defined as section `premiumStrip` in `Templates/Post/Import.html` only; rendered by `Layouts/Default.html` with
  `<f:render section="premiumStrip" optional="true" />` directly below the navbar, above the flash messages. The
  Log Manager doesn't define the section, so nothing renders there. Same pattern as ns_backup.
- The section is wrapped in `<f:if condition="{trialUrl}">`, so it disappears when the ns_license route is
  missing (e.g. the v11 code path).
- Content: `actions-rocket` core icon, headline and description on two lines, one `btn btn-primary`
  "Start Free Trial →" link (the arrow is `aria-hidden` markup, not part of the label; same tab).
- Wording and design are the team-wide standard from the 2026-10-14 review, identical in every NITSAN strip
  except for the extension name.
- Labels in `locallang.xlf`:
  - `premium.strip.headline`: "You're using the free version of NS WP Migration"
  - `premium.strip.description`: "Unlock premium features and get more out of NS WP Migration."
  - `premium.strip.trial`: "Start Free Trial"
- Styling: `.ns-wp-migration-premium-strip` (+ `-icon`, `-headline`, `-description`) in
  `Resources/Public/Css/main.css` — light-blue card (`#e7f2fc`, border `#8eb6e8`, 4px `#2b6cb0` left accent, 6px
  radius, padding 14px 18px, text `#0d2c4a`), semibold 1.1em headline, `light-dark()` for dark mode, button
  `#007bff` (hover `#0062cc`, `6px 14px` padding, no wrap). Font family and size are pinned to
  `--typo3-font-family` / `--typo3-font-size`, because the module also loads Bootstrap 4.3.1 and its own fonts;
  computed sizes match the other NITSAN strips (12px text, 13.2px headline, 12px button).

## Trial link

`PostController::getTrialUrl()` builds the URL with the backend `UriBuilder`: route `nitsan_nslicensemodule`
(`NS_LICENSE_MODULE`), param `trial=ns_wp_migration`, anchor `#free-section` (`NS_LICENSE_FREE_SECTION_ANCHOR`).
It returns `''` on `RouteNotFoundException`.

ns_license's `get-license.js` (`maybeOpenTrialDeepLink()`) reads `?trial=ns_wp_migration`, removes it from the
URL and opens ns_license's own trial modal with the extension preselected (verified live). The ns_license
module requires system maintainer access, while this module is open to all backend users (`access: user`).
