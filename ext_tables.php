<?php
defined('TYPO3') || die();

(static function() {
    
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['ps-import'] = 'EXT:ns_wp_migration/Configuration/RTE/Default.yaml';
    // PageTS
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ns_wp_migration/Configuration/TsConfig/Page/RTE.tsconfig">');
    if (version_compare(TYPO3_branch, '10.0', '>=')) {
        $PostController = \NITSAN\NsWpMigration\Controller\PostController::class;
    } else {
        $PostController = 'Post';
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'NITSAN.NsWpMigration',
        'web',
        'wp_migrate',
        '',
        [
            $PostController =>  'import, importform, logmanager'
        ],
        [
            'access' => 'user,group',
            'icon'   => 'EXT:ns_wp_migration/Resources/Public/Icons/user_mod_psimportexport.svg',
            'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/locallang_psimportexport.xlf',
        ]
    );

})();
