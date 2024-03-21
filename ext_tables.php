<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use NITSAN\NsWpMigration\Controller\PostController;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
(static function() {
    
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:ns_wp_migration/Configuration/RTE/Default.yaml';
    // PageTS
    ExtensionManagementUtility::addPageTSConfig('
        @import EXT:ns_wp_migration/Configuration/TsConfig/Page/RTE.tsconfig'
    );
    if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
        $postController = PostController::class;
        ExtensionUtility::registerModule(
            'Nitsan.NsWpMigration',
            'web',
            'wp_migrate',
            '',
            [
                $postController =>  'import, importform, logmanager'
            ],
            [
                'access' => 'user,group',
                'icon'   => 'EXT:ns_wp_migration/Resources/Public/Icons/icon.svg',
                'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/locallang_psimportexport.xlf',
            ]
        );
    } else {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['ns_wp_migration'] = 'EXT:ns_wp_migration/Resources/Public/Css/';
        $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['ns_wp_migration'] = 'EXT:ns_wp_migration/Resources/Public/fontawesome/css/';
    }
})();
