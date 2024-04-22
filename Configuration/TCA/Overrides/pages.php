<?php
defined('TYPO3_MODE') || defined('TYPO3') || die();

(static function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        'ns_wp_migration',
        'Configuration/TSconfig/Page/rte_preset.tsconfig',
        'Ns Wp Migration :: Config RTE Preset'
    );
})();
