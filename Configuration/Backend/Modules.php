<?php

declare(strict_types=1);

use NITSAN\NsWpMigration\Controller\PostController;

/**
 * Definitions for modules provided by EXT:ns_wp_migration
 */
return [
    'nitsan_module' => [
        'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/BackendModule.xlf',
        'iconIdentifier' => 'module-nitsan',
        'position' => ['after' => 'web'],
    ],
    'importModule' => [
        'parent' => 'nitsan_module',
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