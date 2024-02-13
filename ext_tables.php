<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use NITSAN\NsWpMigration\Controller\PostController;
(static function() {
    
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['ps-import'] = 'EXT:ns_wp_migration/Configuration/RTE/Default.yaml';
    // PageTS
    ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ns_wp_migration/Configuration/TsConfig/Page/RTE.tsconfig">');
    if (version_compare(TYPO3_branch, '10.0', '>=')) {
        $PostController = PostController::class;
    } else {
        $PostController = 'Post';
    }

    ExtensionUtility::registerModule(
        'Nitsan.NsWpMigration',
        'web',
        'wp_migrate',
        '',
        [
            $PostController =>  'import, importform, logmanager'
        ],
        [
            'access' => 'user,group',
            'icon'   => 'EXT:ns_wp_migration/Resources/Public/Icons/icon.svg',
            'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/locallang_psimportexport.xlf',
        ]
    );

    if (!defined('HTMLPURIFIER_PREFIX')) {
        define('HTMLPURIFIER_PREFIX', dirname(__FILE__));
    }

})();
