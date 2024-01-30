<?php
namespace NITSAN\NsWpMigration\Controller;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use NITSAN\NsWpMigration\NsTemplate\TypoScriptTemplateConstantEditorModuleFunctionController;
use NITSAN\NsWpMigration\NsTemplate\TypoScriptTemplateModuleController;

/***
 *
 * This file is part of the "[NITSAN] NS Wp Migration" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2023 Navdeepsinh Jethwa <navdeep@nitsantech.com>, Nitsan Technologies Pvt. Ltd.
 *
 ***/

/**
 * AbstractController
 */
abstract class AbstractController extends ActionController implements LoggerAwareInterface
{

    use LoggerAwareTrait;
  
    protected $persistenceManager = null;

    protected $constantObj;
    protected $constants;

    /**
     * @var TypoScriptTemplateModuleController
     */
    protected $pObj;
    
    
    public function initializeAction() : void
    {
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->setConfiguration();
    }

    /**
     * Initializes this object
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->constantObj = GeneralUtility::makeInstance(TypoScriptTemplateConstantEditorModuleFunctionController::class);
    }

    /*
     * Api configuration
     */
    private function setConfiguration()
    {
        $this->constantObj->init($this->pObj);
        $this->constants = $this->constantObj->main();
    }

    /**
     * Checks is file is valide type or not
     * @param array $file
     * @return bool
     */
    function checkValideFile(array $file): bool
    {
        $fileMimes = [
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/octet-stream',
            'application/vnd.ms-excel',
            'application/x-csv',
            'text/x-csv',
            'text/csv',
            'application/csv',
            'application/excel',
            'application/vnd.msexcel',
            'text/plain'
        ];

        if (in_array($file['type'], $fileMimes)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate the fileData
     * @param array $fileData
     * @return bool
     */
    function validateFileData(array $fileData): bool
    {
        if ($fileData) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Trim html and remove unwanted space from the htmls 
     */
    function minifier($code)
    {
        $search = array(

            // Remove whitespaces after tags
            '/\>[^\S ]+/s',

            // Remove whitespaces before tags
            '/[^\S ]+\</s',

            // Remove multiple whitespace sequences
            '/(\s)+/s'
        );
        $replace = array('>', '<', '\\1');
        $code = preg_replace($search, $replace, $code);
        return $code;
    }

}
