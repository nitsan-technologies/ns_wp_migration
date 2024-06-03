<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || defined('TYPO3') || die();

(static function (): void {
    ExtensionManagementUtility::registerPageTSConfigFile(
        'ns_wp_migration',
        'Configuration/TsConfig/Page/rte_preset.tsconfig',
        'Wp Migration :: Config RTE Preset'
    );
})();
