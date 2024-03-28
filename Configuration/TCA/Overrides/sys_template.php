<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile('ns_wp_migration', 'Configuration/TypoScript', '[NITSAN] Wp Migration');
