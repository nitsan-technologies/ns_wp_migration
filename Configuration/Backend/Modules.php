<?php

declare(strict_types=1);

use NITSAN\NsWpMigration\Controller\PostController;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$typo3Version = (int)VersionNumberUtility::convertVersionStringToArray(
    VersionNumberUtility::getNumericTypo3Version()
)['version_main'];
/**
 * Definitions for modules provided by EXT:ns_wp_migration
 */

return [
    'nitsan_module' => [
        'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/BackendModule.xlf',
        'icon'   => 'EXT:ns_wp_migration/Resources/Public/Icons/module-nswpmigration.svg',
        'position' => ['after' => 'web'],
    ],
    'nsWpMigrationModule' => [
        'parent' => 'nitsan_module',
        'position' => ['before' => 'top'],
        'access' => 'user',
        'icon'   => 'EXT:ns_wp_migration/Resources/Public/Icons/icon.svg',
        'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/locallang_psimportexport.xlf',
        'path' => '/module/web/importModule',
        'inheritNavigationComponentFromMainModule' => false,
        'extensionName' => 'ns_wp_migration',
        'navigationComponent' => $typo3Version >= 13
        ? '@typo3/backend/tree/page-tree-element'
        : '@typo3/backend/page-tree/page-tree-element',
        'controllerActions' => [
            PostController::class => 'import, importForm, logManager, downloadSample',
        ],
    ],
];

