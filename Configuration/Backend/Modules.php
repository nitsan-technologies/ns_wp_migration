<?php

declare(strict_types=1);

use NITSAN\NsWpMigration\Controller\PostController;

/**
 * Definitions for modules provided by EXT:ns_wp_migration
 */
return [
    'nsWpMigration' => [
        'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/BackendModule.xlf',
        'icon'   => 'EXT:ns_wp_migration/Resources/Public/Icons/module-nswpmigration.svg',
        'position' => ['after' => 'web'],
    ],
    'nsWpMigrationModule' => [
        'parent' => 'nsWpMigration',
        'position' => ['before' => 'top'],
        'access' => 'user',
        'icon'   => 'EXT:ns_wp_migration/Resources/Public/Icons/icon.svg',
        'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/locallang_psimportexport.xlf',
        'path' => '/module/web/importModule',
        'inheritNavigationComponentFromMainModule' => false,
        'extensionName' => 'ns_wp_migration',
        'navigationComponent' => '@typo3/backend/page-tree/page-tree-element',
        'controllerActions' => [
            PostController::class => 'import, importForm, logManager, downloadSample',
        ],
    ],
];
