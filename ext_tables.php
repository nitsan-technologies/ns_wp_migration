<?php

defined('TYPO3') || die();

use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use NITSAN\NsWpMigration\Controller\PostController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

(static function () {

    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['ns-importer'] = 'EXT:ns_wp_migration/Configuration/RTE/Default.yaml';
    // PageTS
    ExtensionManagementUtility::addPageTSConfig(
        '
        @import EXT:ns_wp_migration/Configuration/TsConfig/Page/RTE.tsconfig'
    );
    if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
        // @extensionScannerIgnoreLine
        if (!array_key_exists('nitsan', $GLOBALS['TBE_MODULES']) || $GLOBALS['TBE_MODULES']['nitsan'] == '') {
            if (!isset($GLOBALS['TBE_MODULES']['nitsan'])) {
                $temp_TBE_MODULES = [];
                foreach ($GLOBALS['TBE_MODULES'] as $key => $val) {
                    if ($key == 'web') {
                        $temp_TBE_MODULES[$key] = $val;
                        $temp_TBE_MODULES['nitsan'] = '';
                    } else {
                        $temp_TBE_MODULES[$key] = $val;
                    }
                }

                $GLOBALS['TBE_MODULES'] = $temp_TBE_MODULES;
                $GLOBALS['TBE_MODULES']['_configuration']['nitsan'] = [
                    'iconIdentifier' => 'module-nswpmigration',
                    'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/BackendModule.xlf',
                    'name' => 'nitsan'
                ];
            }
        }
        $postController = PostController::class;
        ExtensionUtility::registerModule(
            'Nitsan.NsWpMigration',
            'nitsan',
            'wp_migrate',
            '',
            [
                $postController =>  'import, importForm, logManager, downloadSample'
            ],
            [
                'access' => 'user,group',
                'icon'   => 'EXT:ns_wp_migration/Resources/Public/Icons/icon.svg',
                'labels' => 'LLL:EXT:ns_wp_migration/Resources/Private/Language/locallang_psimportexport.xlf',
                'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
                'inheritNavigationComponentFromMainModule' => false
            ]
        );
    } else {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['ns_wp_migration'] = 'EXT:ns_wp_migration/Resources/Public/fontawesome/css/';
    }

    $iconRegistry = GeneralUtility::makeInstance(
        IconRegistry::class
    );

    $identifiers = ['module-nitsan','module-nswpmigration'];
    foreach ($identifiers as $identifier) {
        $iconRegistry->registerIcon(
            $identifier,
            SvgIconProvider::class, 
            ['source' => 'EXT:ns_wp_migration/Resources/Public/Icons/'.$identifier.'.svg']
        );
    }

})();
