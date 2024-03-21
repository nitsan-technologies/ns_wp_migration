<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace NITSAN\NsWpMigration\ViewHelpers\Be;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as translate;

/**
 * ViewHelper which renders a record list as known from the TYPO3 list module.
 *
 * .. note::
 *    This feature is experimental!
 *
 * Examples
 * ========
 *
 * Minimal::
 *
 *    <f:be.tableList tableName="fe_users" />
 *
 * List of all "Website user" records stored in the configured storage PID.
 * Records will be editable, if the current backend user has got edit rights for the table ``fe_users``.
 *
 * Only the title column (username) will be shown.
 *
 * Context menu is active.
 *
 * Full::
 *
 *    <f:be.tableList tableName="fe_users" fieldList="{0: 'name', 1: 'email'}"
 *        storagePid="1"
 *        levels="2"
 *        filter="foo"
 *        recordsPerPage="10"
 *        sortField="name"
 *        sortDescending="true"
 *        readOnly="true"
 *        enableClickMenu="false"
 *        enableControlPanels="true"
 *        clickTitleMode="info"
 *        />
 *
 * List of "Website user" records with a text property of ``foo`` stored on PID ``1`` and two levels down.
 * Clicking on a username will open the TYPO3 info popup for the respective record
 */
class TableListViewHelper extends AbstractBackendViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('tableName', 'string', 'name of the database table', true);
        $this->registerArgument('fieldList', 'array', 'list of fields to be displayed. If empty, only the title column (configured in $TCA[$tableName][\'ctrl\'][\'title\']) is shown', false, []);
        $this->registerArgument('storagePid', 'int', 'by default, records are fetched from the storage PID configured in persistence.storagePid. With this argument, the storage PID can be overwritten');
        $this->registerArgument('levels', 'int', 'corresponds to the level selector of the TYPO3 list module. By default only records from the current storagePid are fetched', false, 0);
        $this->registerArgument('filter', 'string', 'corresponds to the "Search String" textbox of the TYPO3 list module. If not empty, only records matching the string will be fetched', false, '');
        $this->registerArgument('recordsPerPage', 'int', 'amount of records to be displayed at once. Defaults to $TCA[$tableName][\'interface\'][\'maxSingleDBListItems\'] or (if that\'s not set) to 100', false, 0);
        $this->registerArgument('sortField', 'string', 'table field to sort the results by', false, '');
        $this->registerArgument('sortDescending', 'bool', 'if TRUE records will be sorted in descending order', false, false);
        $this->registerArgument('readOnly', 'bool', 'if TRUE, the edit icons won\'t be shown. Otherwise edit icons will be shown, if the current BE user has edit rights for the specified table!', false, false);
        $this->registerArgument('enableClickMenu', 'bool', 'enables context menu', false, true);
        $this->registerArgument('enableControlPanels', 'bool', 'enables control panels', false, false);
        $this->registerArgument('clickTitleMode', 'string', 'one of "edit", "show" (only pages, tt_content), "info');
    }

    /**
     * Renders a record list as known from the TYPO3 list module
     * Note: This feature is experimental!
     *
     * @return string the rendered record list
     * @throws \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     * @see \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
     */
    public function render()
    {
        $tableName = $this->arguments['tableName'];
        $fieldList = $this->arguments['fieldList'];
        $storagePid = $this->arguments['storagePid'];
        $levels = $this->arguments['levels'];
        $filter = $this->arguments['filter'];
        $recordsPerPage = $this->arguments['recordsPerPage'];
        $sortField = $this->arguments['sortField'];
        $sortDescending = $this->arguments['sortDescending'];
        $readOnly = $this->arguments['readOnly'];
        $enableClickMenu = $this->arguments['enableClickMenu'];
        $clickTitleMode = $this->arguments['clickTitleMode'];
        $enableControlPanels = $this->arguments['enableControlPanels'];

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        if (version_compare(TYPO3_branch, '11.5', '>=')) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/NsHelpdesk/AjaxDataHandler');
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/RecordDownloadButton');
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ActionDispatcher');
            if ($enableControlPanels === true) {
                $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/MultiRecordSelection');
                $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
            }   
        }
        if (version_compare(TYPO3_branch, '10.4', '>=') && version_compare(TYPO3_branch, '10.5', '<=')) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/NsHelpdesk/AjaxDataHandler10');
        }
        if (version_compare(TYPO3_branch, '9.5', '>=') && version_compare(TYPO3_branch, '9.6', '<=')) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/NsHelpdesk/AjaxDataHandler9');
        }
        // We need to include the language file, since DatabaseRecordList is heavily using ->getLL
        if (version_compare(TYPO3_branch, '9.5', '>=')) {
            $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        }
        $pageinfo = BackendUtility::readPageAccess(GeneralUtility::_GP('id'), $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $dblist = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $dblist->pageRow = $pageinfo;
        if ($readOnly) {
            $dblist->setIsEditable(false);
        } else {
            $dblist->calcPerms = new Permission($GLOBALS['BE_USER']->calcPerms($pageinfo));
            if (version_compare(TYPO3_branch, '8.7', '<=')) {
                $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/NsHelpdesk/AjaxDataHandlerv8');
            }
            if (version_compare(TYPO3_branch, '10.4', '<=')) {
                $dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms(BackendUtility::getRecord('pages', GeneralUtility::_GP('id')));
            }
        }
        $dblist->disableSingleTableView = true;
        $dblist->clickTitleMode = $clickTitleMode;
        $dblist->clickMenuEnabled = $enableClickMenu;
        if ($storagePid === null) {
            $storagePid = GeneralUtility::_GP('id');
        }
        $dblist->start($storagePid, $tableName, (int)GeneralUtility::_GP('pointer'), $filter, $levels, $recordsPerPage);
        // Column selector is disabled since fields are defined by the "fieldList" argument
        $dblist->displayColumnSelector = false;
        $dblist->setFields = [$tableName => $fieldList];
        $dblist->noControlPanels = !$enableControlPanels;
        $dblist->sortField = $sortField;
        $dblist->sortRev = $sortDescending;
        $html = $dblist->generateList();
        if (version_compare(TYPO3_branch, '10.4', '<=')) {
            $js = 'var T3_THIS_LOCATION = ' . GeneralUtility::quoteJSvalue(rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')));
            if ($dblist->HTMLcode) {
                $html = GeneralUtility::wrapJS($js) . $dblist->HTMLcode;
            }
        }
        if (is_null($html) || empty($html)) {
            $html = '
            <div class="alert alert-warning alert--custom" role="alert">
            <h4 class="alert-heading">Opps!</h4>
            <p>There are no any records are found!</p>
            <hr>
            <p class="mb-0">To create a new record please click <a class="alert-link" href="'.$this->redirectToCreateNewRecord($tableName).'">Add new</a></p>
            </div>
            ';
        }
        return $html;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }


    /**
     * Redirect to tceform creating a new record
     *
     * @param string $table table name
     */
    private function redirectToCreateNewRecord($table)
    {
        $pid = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('id');
        $returnUrl = GeneralUtility::getIndpEnv('REQUEST_URI');
        $url = $this->getModuleUrl('record_edit', [
            'edit[' . $table . '][' . $pid . ']' => 'new',
            'returnUrl' => $returnUrl
        ]);
        return $url;
    }


    /**
     * Get a CSRF token
     *
     * @param bool $tokenOnly Set it to TRUE to get only the token, otherwise including the &moduleToken= as prefix
     * @return string
     */
    protected function getToken(bool $tokenOnly = false): string
    {
        if (self::is9up()) {
            $tokenParameterName = 'token';
            $token = FormProtectionFactory::get('backend')->generateToken('route', 'nitsan_NsHelpdeskHelpdeskmi1');
        } else {
            $tokenParameterName = 'moduleToken';
            $token = FormProtectionFactory::get()->generateToken('moduleCall', 'nitsan_NsHelpdeskHelpdeskmi1');
        }

        if ($tokenOnly) {
            return $token;
        }

        return '&' . $tokenParameterName . '=' . $token;
    }
    /**
     * Returns the URL to a given module mainly used for visibility settings or deleting a record via AJAX
     * @param string $moduleName Name of the module
     * @param array $urlParameters URL parameters that should be added as key value pairs
     * @return string Calculated URL
     */
    public static function getModuleUrl($moduleName, $urlParameters = [])
    {
        if (version_compare(TYPO3_branch, '10', '>=')) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            return $uriBuilder->buildUriFromRoute($moduleName, $urlParameters);
        } else {
            return BackendUtility::getModuleUrl($moduleName, $urlParameters);
        }
    }
}
