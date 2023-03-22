<?php
namespace NITSAN\NsWpMigration\Controller;

use NITSAN\NsWpMigration\NsTemplate\ExtendedTemplateService;
use NITSAN\NsWpMigration\NsTemplate\TypoScriptTemplateConstantEditorModuleFunctionController;
use NITSAN\NsWpMigration\NsTemplate\TypoScriptTemplateModuleController;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as translate;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Resource\StorageRepository;
use DOMDocument;
use TYPO3\CMS\Extbase\Service\CacheService;
/**
 * AuthController
 */
class PostController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    protected $extConfig = array();
    protected $constantObj;
    protected $constants;
    /**
     * @var TypoScriptTemplateModuleController
     */
    protected $pObj;

    /**
     * Initializes this object
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->constantObj = GeneralUtility::makeInstance(TypoScriptTemplateConstantEditorModuleFunctionController::class);
    }

    /**
     * Initialize action
     * @return void
     * @throws \Exception
     */
    public function initializeAction()
    {
        parent::initializeAction();
        if (TYPO3_MODE === 'BE') {
            $this->setConfiguration();
        }
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
     * action formsSettings
     *
     * @return void
     */
    public function importAction(array $data = [])
    {
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($data, __FILE__.' at line:'.__LINE__);die();
        session_start();
        $assign = [
            'action' => 'import',
            'constant' => $this->constants
        ];
        // $assign['redirect'] = $_SESSION['redirect'];
        // $assign['missing'] = $_SESSION['missing'];
        if ($data) {
            $assign['redirect'] = $_SESSION['redirect'];
            $assign['missing'] = $_SESSION['missing'];
        }
        $this->view->assignMultiple($assign);
    }

    /**
     * action ImportCSV
     * 
     * @return void
     */
    public function importformAction()
    {
        // Allowed mime types
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

        $BeUser = $GLOBALS['BE_USER']->user;
        $payload = GeneralUtility::_GP('tx_web_ns_wp_migration');

        if (!$payload['storage_id']) {
            $this->addFlashMessage('Sorry, Please provide storage folder id.', 'An error occurred', FlashMessage::ERROR);
            $this->redirect('import');
        }

        if($payload['storage_id']) {

            $storageExist = 1;
            $pqueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $storageId = $pqueryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $pqueryBuilder->expr()->eq('uid', $pqueryBuilder->createNamedParameter($payload['storage_id']))
                )
                ->executeQuery()
                ->fetchOne();

            if ($storageId) {
                $storageExist = 1;
            } else {
                $storageExist = 0;
            }

            if ($storageExist == 0) {
                $this->addFlashMessage('Sorry,Invalid storage folder id.', 'An error occurred', FlashMessage::ERROR);
                $this->redirect('import');
            }

        }

        if (!empty($_FILES['tx_web_ns_wp_migration']['name']) && in_array($_FILES['tx_web_ns_wp_migration']['type']['file'], $fileMimes))
        {
            $csvFile = fopen($_FILES['tx_web_ns_wp_migration']['tmp_name']['file'], 'r');
            fgetcsv($csvFile);
            $rowno = 1;
            $numberofRecord = 0;
            $fails_records = 0;
            $success_records = 0;
            $update_records = 0;
            $total_records = 0;
            $redirect_url = [];
            $image_fails = 0;
            $base_url = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl();
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $header = NULL;
            $postData = [];
            $csv = array();
            $record = 0;
            $errorData = [];
            if (($handle = fopen($_FILES['tx_web_ns_wp_migration']['tmp_name']['file'], 'r')) !== false) {
                $columns = fgetcsv($handle, 10000, ",");
                $klm = 0;
                foreach($columns as $col) {
                    $columns[$klm] = strtolower(str_replace(' ', '_', $col));
                    $klm++;
                }

                while (($row = fgetcsv($handle, 10000, ",")) !== false) {
                    
                    $postData[$record] = array_combine($columns, $row);
                    $storageSlug = $pqueryBuilder
                    ->select('slug')
                    ->from('pages')
                    ->where(
                        $pqueryBuilder->expr()->eq('uid', $pqueryBuilder->createNamedParameter($payload['storage_id']))
                        )
                    ->executeQuery()
                    ->fetchOne();
                        if ($postData[$record]) {
                            $validation = $this->validateCsv($postData[$record], $rowno);
                            if(!empty($validation)) {
                                $errorData[] = $validation;
                            }
                            //parser Html
                            $newImagePath = '';
                            $parseObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Html\HtmlParser::class);
                            $htmlStr = $parseObj->removeFirstAndLastTag($postData[$record]['content']);
                            $result = $parseObj->splitIntoBlock('div', $htmlStr);
                            $html_elements = array_unique($result);
                            $doc = new DOMDocument();
                            $doc->preserveWhiteSpace = FALSE;
                            @$doc->loadHTML($postData[$record]['content']);
                            $doc->formatOutput = true;
                                        
                            // if(preg_match('/<img/', $postData[$record]['content']) > 0) {
                            //     $tags = $doc->getElementsByTagName('img');
                            //     foreach ($tags as $tag) {
                            //         try {
                            //             $url = $tag->getAttribute('src');
                                        
                            //             $filename = basename($url);
                            //             $folder = 'fileadmin/user_upload/';
                            //             $dstFolder = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . $folder;
                            //             $out = file_get_contents($url);
                                
                            //             if (!file_exists($dstFolder)) {
                            //                 GeneralUtility::mkdir_deep($dstFolder);
                            //             }

                            //             file_put_contents($dstFolder . '/' . $filename, $out);
                            //             $newImagePath = $dstFolder . '/' . $filename;
                            //             $tag->setAttribute('src', $newPath);
                            //             $doc->saveHTML($tag);
                            //         } catch (Exception $e) {
                            //             $image_fails++;
                            //         }
                                
                            //     }
                            // }

                            $data = [];
                            if ($postData[$record]['slug']) {
                                $slug = $storageSlug.'/'.$postData[$record]['slug'];
                            } else {
                                $slug = $storageSlug.'/'.strtolower(str_replace(' ', '-', $postData[$record]['title']));
                            }
                            
                            $data = ['cruser_id' => $BeUser['uid'],
                                    'title' => $postData[$record]['title'],
                                    'slug' => $slug,
                                    'doktype' => '137',
                                    'hidden' => '0',
                                    'pid' => $payload['storage_id'],
                                    'categories' => '0',
                                    'tags' => '0',
                                    'authors' => '0',
                                    'postid' => 'wppost_'.$postData[$record]['id'],
                                    'backend_layout' => 'pagets__Blogdetail',
                                    'abstract' => substr(strip_tags($postData[$record]['content']),0,200)
                                    ];
                            
                            $data['og_title'] = $postData[$record]['title'];
                            $data['twitter_title'] = $postData[$record]['title'];
                            $data['seo_title'] = $postData[$record]['title'];
                            $data['description'] = substr(strip_tags($postData[$record]['content']),0,120);
                            $data['og_description'] = substr(strip_tags($postData[$record]['content']),0,120);

                            if(isset($postData[$record]['_yoast_wpseo_metadesc']) && !empty($postData[$record]['_yoast_wpseo_metadesc'])) {
                                $data['description'] = $postData[$record]['_yoast_wpseo_metadesc'];
                                $data['og_description'] = $postData[$record]['_yoast_wpseo_metadesc'];
                                $data['twitter_description'] = $postData[$record]['_yoast_wpseo_metadesc'];
                            }

                            if(isset($postData[$record]['_yoast_wpseo_focuskw']) && !empty($postData[$record]['_yoast_wpseo_focuskw'])) {
                                $data['keywords'] = $postData[$record]['_yoast_wpseo_focuskw'];
                            }

                            if(isset($postData[$record]['_yoast_wpseo_title']) && !empty($postData[$record]['_yoast_wpseo_title'])) {
                                $data['seo_title'] = $postData[$record]['_yoast_wpseo_title'];
                                $data['og_title'] = $postData[$record]['_yoast_wpseo_title'];
                                $data['twitter_title'] = $postData[$record]['_yoast_wpseo_title'];
                            }

                            $isExist = 0;

                            $pqueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
                            $postId = $pqueryBuilder
                                ->select('uid')
                                ->from('pages')
                                ->where(
                                    $pqueryBuilder->expr()->eq('postid', $pqueryBuilder->createNamedParameter('wppost_'.$postData[$record]['id'])),
                                    $pqueryBuilder->expr()->eq('pid', $pqueryBuilder->createNamedParameter($payload['storage_id']))
                                    )
                                ->executeQuery()
                                ->fetchOne();
                            
                            if ($postId) {
                                $isExist = 1;
                            }
                            
                            $categories = [];
                            $authorList = [];
                            $tagsList = [];
                            $fileId = '';
                            $images = [];

                            if (isset($postData[$record]['date']) && !empty($postData[$record]['date'])) {
                                $data['tstamp'] = strtotime($postData[$record]['date']);
                                $data['crdate'] = strtotime($postData[$record]['date']);
                                $data['publish_date'] = strtotime($postData[$record]['date']);
                            }
                            
                            $sysFilequeryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
                            $imageId = 0;
                            if (isset($postData[$record]['image_url']) && !empty($postData[$record]['image_url'])) {
                                $urlDecode = parse_url($postData[$record]['image_url']);
                                $paths = $urlDecode['path'];
                                $path = trim(str_replace('/wp-content','',$paths));
                                $dir = explode('/', $path);
                                
                                try {

                                    $url = $postData[$record]['image_url'];
                                    $filename = basename($url);
                                    $mimetype = 'image/'.pathinfo($filename, PATHINFO_EXTENSION);
                                    $folder = '/fileadmin/'.$dir[1];

                                    

                                    $dstFolder = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . $folder;
                                    
                                    if (!file_exists($dstFolder)) {
                                        GeneralUtility::mkdir_deep($dstFolder);
                                    }
                                    
                                    $folder = '/fileadmin'.trim(str_replace('/'.$filename,'',$path));
                                    
                                    $file = array(
                                        'folder' => $folder,
                                        'identifier' => $postData[$record]['image_url']
                                      );
                                      
                                    $identifierHash = sha1($file['identifier']);
                                    $folderHash = sha1($file['folder']);
                                    $sha1 = sha1_file($file['identifier']);

                                   

                                    $records = ['tstamp'=> strtotime(date('Y-m-d h:s:i')),
                                    'storage' => 1,
                                    'type' => 2,
                                    'identifier' => $path,
                                    'identifier_hash' => $identifierHash,
                                    'folder_hash' => $folderHash,
                                    'extension'=> pathinfo($filename, PATHINFO_EXTENSION),
                                    'mime_type' => $mimetype,
                                    'name' => $filename,
                                    'sha1' =>$sha1,
                                    'creation_date' => strtotime(date('Y-m-d h:s:i'))
                                            ];
                                    
                                    $insertImages = $sysFilequeryBuilder
                                            ->insert('sys_file')
                                            ->values($records)
                                            ->execute();
                    
                                    $imageId = $sysFilequeryBuilder->getConnection()->lastInsertId();
                                    
                                } catch (Exception $e) {
                                    $image_fails++;
                                }
                                    
                            
                            }

                            $filename = '';
                            $featureImageId = 0;
                            //Feature image setup
                            if (isset($postData[$record]['image_featured']) && !empty($postData[$record]['image_featured'])) {
                                $urlDecode = parse_url($postData[$record]['image_featured']);
                                $paths = $urlDecode['path'];
                                $path = trim(str_replace('/wp-content','',$paths));
                                $dir = explode('/', $path);
                                try {

                                    $url = $postData[$record]['image_featured'];
                                    $filename = basename($url);
                                    $mimetype = 'image/'.pathinfo($filename, PATHINFO_EXTENSION);

                                    $folder = '/fileadmin/'.$dir[1];
                                    $dstFolder = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . $folder;
                                    
                                    if (!file_exists($dstFolder)) {
                                        GeneralUtility::mkdir_deep($dstFolder);
                                    }

                                    $folder = '/fileadmin'.trim(str_replace('/'.$filename,'',$path));

                                    $file = array(
                                        'folder' => $folder,
                                        'identifier' => $postData[$record]['image_featured']
                                      );
                                      
                                    $identifierHash = sha1($file['identifier']);
                                    $folderHash = sha1($file['folder']);
                                    $sha1 = sha1_file($file['identifier']);
                                   
                                    $records = ['tstamp'=> strtotime(date('Y-m-d h:s:i')),
                                    'storage' => 1,
                                    'type' => 2,
                                    'identifier' => $path,
                                    'identifier_hash' => $identifierHash,
                                    'folder_hash' => $folderHash,
                                    'extension'=> pathinfo($filename, PATHINFO_EXTENSION),
                                    'mime_type' => $mimetype,
                                    'name' => $filename,
                                    'sha1' =>$sha1,
                                    'creation_date' => strtotime(date('Y-m-d h:s:i'))
                                            ];
                                    

                                    $insertfeatureImage = $sysFilequeryBuilder
                                            ->insert('sys_file')
                                            ->values($records)
                                            ->execute();
                    
                                    $featureImageId = $sysFilequeryBuilder->getConnection()->lastInsertId();
                                    //$data['og_image'] = '1';
                                    
                                } catch (Exception $e) {
                                    $image_fails++;
                                }
                            }

                            // search categories , if matche then get id else insert new and get id
                            if (isset($postData[$record]['categories']) && !empty($postData[$record]['categories'])) {
                                $cats = GeneralUtility::trimExplode('|', $postData[$record]['categories']);
                                foreach ($cats as $c) {
                                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
                                    $category = $queryBuilder
                                        ->select('uid')
                                        ->from('sys_category')
                                        ->where(
                                            $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($c)),
                                            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($payload['storage_id']))
                                            )
                                        ->executeQuery()
                                        ->fetchOne();
                                    
                                    if ( $category ) {

                                        $categories[] = $category;

                                    } else {
                                        
                                        $slug = str_replace(" ", "-", strtolower($c));
                                        $cat = ['pid' => $payload['storage_id'],
                                                'hidden' => '0',
                                                'title' => $c,
                                                'record_type' => '100',
                                                'slug' => $slug
                                            ];
                                        $insertCategory = $queryBuilder
                                            ->insert('sys_category')
                                            ->values($cat)
                                            ->execute();
                    
                                        $category = $queryBuilder->getConnection()->lastInsertId();
                                        $categories[] = $category;

                                    }
                                }
                                $data['categories'] = (string)count($categories);
                                
                            }

                            // Search author , id match then get id elese insert new and get id
                            if (isset($postData[$record]['author_email']) && !empty($postData[$record]['author_email'])) {
                                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_blog_domain_model_author');
                                $authodId = $queryBuilder
                                    ->select('uid')
                                    ->from('tx_blog_domain_model_author')
                                    ->where(
                                        $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($postData[$record]['author_email'])),
                                        $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($payload['storage_id']))
                                        )
                                    ->executeQuery()
                                    ->fetchOne();
                                
                                if ($authodId) {

                                    $authorList[] = $authodId;

                                } else {

                                    $slug = str_replace(" ", "-", strtolower($postData[$record]['author_username']));
                                    $authorData = ['pid' => $payload['storage_id'],
                                                'hidden' => '0',
                                                'name' => $postData[$record]['author_username'],
                                                'slug' => $slug,
                                                'title' => $postData[$record]['author_first_name'].' '.$postData[$record]['author_last_name'],
                                                'email' => $postData[$record]['author_email']
                                            ];

                                    $insertAuthor = $queryBuilder
                                        ->insert('tx_blog_domain_model_author')
                                        ->values($authorData)
                                        ->execute();

                                    $authorList[] = $queryBuilder->getConnection()->lastInsertId();
                                }
                                $data['authors'] = (string)count($authorList);
                            }
                        
                            // Search for tag explode , loop match exist the collect else add and collects
                            if (isset($postData[$record]['tags']) && !empty($postData[$record]['tags'])) {
                                $tags =  GeneralUtility::trimExplode('|', $postData[$record]['tags']);
                                foreach ($tags as $t) {
                                    $tqueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_blog_domain_model_tag');
                                    $tagItem = $tqueryBuilder
                                        ->select('uid')
                                        ->from('tx_blog_domain_model_tag')
                                        ->where(
                                            $tqueryBuilder->expr()->eq('title', $tqueryBuilder->createNamedParameter($t)),
                                            $tqueryBuilder->expr()->eq('pid', $tqueryBuilder->createNamedParameter($payload['storage_id']))
                                        )
                                        ->executeQuery()
                                        ->fetchOne();
                                    if ( $tagItem ) {

                                        $tagsList[] = $tagItem;

                                    } else {
                                        
                                        $slug = str_replace(" ", "-", strtolower($t));
                                        $tagData = ['l18n_diffsource' => 0,
                                                'pid' => $payload['storage_id'],
                                                'hidden' => '0',
                                                'title' => $t,
                                                'slug' => $slug
                                            ];
                                        
                                        $insertTag = $tqueryBuilder
                                            ->insert('tx_blog_domain_model_tag')
                                            ->values($tagData)
                                            ->execute();
                    
                                        $tagItem = $tqueryBuilder->getConnection()->lastInsertId();
                                        $tagsList[] = $tagItem;
                                    }
                                
                                }
                                $data['tags'] = (string)count($tagsList);
                                
                            }
                            
                            // Import Blog
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
                            if ($isExist == 1) {

                                unset($data['postid']);
                                if($data['title'] && $data['postid'])
                                {
                                    foreach ($data as $key => $val) {
                                        try {
                                            $queryBuilder
                                                ->update('pages')
                                                ->where(
                                                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($postId))
                                                )
                                                ->set($key, $val)
                                                ->executeStatement();
                                        } catch (\Throwable $th) {
                                            $fails_records++;
                                        }
                                        
                                    }
                                }
                                
                                $tableUid = $postId;
                                $update_records++;

                            } else {

                                if($data['title'] && $data['postid'])
                                {
                                    try {
                                        $affectedRows = $queryBuilder
                                            ->insert('pages')
                                            ->values($data)
                                            ->execute();
                                        $tableUid = $queryBuilder->getConnection()->lastInsertId();
                                        $success_records++;
                                    } catch (\Throwable $th) {
                                        $tableUid = 0;
                                        $fails_records++;
                                    }
                                }

                            }
                            
                            if($tableUid > 0)
                            {
                                //feature Image
                                if (isset($postData[$record]['image_featured']) && !empty($postData[$record]['image_featured']) ) {
                                    if ($featureImageId != 0) {
                                        $frqueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
                                        if ($isExist == 1) { 
                                            $affectedRows = $frqueryBuilder
                                                ->delete('sys_file_reference')
                                                ->where(
                                                    $frqueryBuilder->expr()->eq('pid', $frqueryBuilder->createNamedParameter($tableUid))
                                                )
                                                ->executeStatement();
                                        }

                                        $imageData = ['pid' => $tableUid,
                                                    'cruser_id' =>  $BeUser['uid'],
                                                    'deleted' => '0',
                                                    'uid_local' => $featureImageId,
                                                    'uid_foreign' => $tableUid,
                                                    'tablenames' => 'pages',
                                                    'fieldname' => 'featured_image',
                                                    'table_local' => 'sys_file'
                                                    ];

                                        $affectedRows = $frqueryBuilder
                                            ->insert('sys_file_reference')
                                            ->values($imageData)
                                            ->execute();
                                        
                                        // $imageData['fieldname'] = 'og_image';
                                        // $affectedRows = $frqueryBuilder
                                        //     ->insert('sys_file_reference')
                                        //     ->values($imageData)
                                        //     ->execute();
                                    }
                                }
                                //categories set
                                if ($categories) {
                                    $cqueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category_record_mm');

                                    if ($isExist == 1) {

                                        $affectedRows = $queryBuilder
                                            ->delete('sys_category_record_mm')
                                            ->where(
                                                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($tableUid))
                                            )
                                            ->executeStatement();

                                    }

                                    foreach($categories as $cat) {
                                        $catData = ['uid_local' => $cat,
                                                    'uid_foreign' => $tableUid,
                                                    'tablenames' => 'pages',
                                                    'fieldname' => 'categories'
                                                ];
                                        $affectedRows = $cqueryBuilder
                                                ->insert('sys_category_record_mm')
                                                ->values($catData)
                                                ->execute();
                                    }
                                }

                                //tags set
                                if ($tagsList) {
                                    $tqueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_blog_tag_pages_mm');

                                    if ($isExist == 1) {

                                        $affectedRows = $queryBuilder
                                            ->delete('tx_blog_tag_pages_mm')
                                            ->where(
                                                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($postId))
                                            )
                                            ->executeStatement();

                                    }

                                    foreach($tagsList as $tag) {
                                        $tagData = ['uid_local' => $tableUid, 
                                                    'uid_foreign' => $tag
                                                ];
                                        $affectedRows = $tqueryBuilder
                                                    ->insert('tx_blog_tag_pages_mm')
                                                    ->values($tagData)
                                                    ->execute();
                                    }
                                }

                                //author set
                                if ($authorList) {
                                    $aqueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_blog_post_author_mm');

                                    if ($isExist == 1) {

                                        $affectedRows = $queryBuilder
                                            ->delete('tx_blog_post_author_mm')
                                            ->where(
                                                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($tableUid))
                                            )
                                            ->executeStatement();

                                    }

                                    foreach ($authorList as $auther) {
                                        $author = ['uid_local' => $tableUid,
                                                'uid_foreign' => $auther
                                                ];
                                        $affectedRows = $aqueryBuilder
                                                        ->insert('tx_blog_post_author_mm')
                                                        ->values($author)
                                                        ->execute();
                                    }
                                }

                                // Import Conenet
                                if(isset($postData[$record]['content']) && !empty($postData[$record]['content'])) {
                                    $i = 0;
                                    $total = count($html_elements);
                                    $tqueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');

                                    if ($isExist == 1) {

                                        $affectedRows = $tqueryBuilder
                                            ->delete('tt_content')
                                            ->where(
                                                $tqueryBuilder->expr()->eq('pid', $tqueryBuilder->createNamedParameter($tableUid))
                                            )
                                            ->executeStatement();

                                    }

                                    foreach ($html_elements as $elements) {
                                        $defaultHtml = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", trim($elements));
                                        $ContentHtml = $this->minifier($defaultHtml);
                                        $content = $ContentHtml;
                                        if(strlen($content) > 1) {
                                            $contentObject = ['pid' => $tableUid,
                                                        'hidden' => '0',
                                                        'cruser_id' => $BeUser['uid'], 
                                                        'CType'=>'text',
                                                        'bodytext'=>$content,
                                                        'colPos' => 123];

                                            $affectedRows = $tqueryBuilder
                                                ->insert('tt_content')
                                                ->values($contentObject)
                                                ->execute();
                                        }
                                        $i++;
                                    }
                                    
                                }

                                //check for redirect rules
                                if(isset($postData[$record]['permalink']) && !empty($postData[$record]['permalink'])) {
                                    $slug = trim(parse_url($postData[$record]['permalink'], PHP_URL_PATH), '/');
                                    $active_slug = ltrim($data['slug'], '/');
                                    if($active_slug != $slug) {
                                        $redirect_url[] = array('active-url'=>$base_url.$active_slug, 'past-url'=>$postData[$record]['permalink']);
                                    }
                                }
                            }

                            $tableUid = $queryBuilder->getConnection()->lastInsertId();
                            $rowno++;
                            $total_records++;
                        } else {
                            $rowno++;
                            $$fails_records++;
                        }
                    $record++;
                }
                fclose($handle);
            }
            /**
             *  Add Records in logs
             */
            $logData = ['pid' => $payload['storage_id'],
                        'tstamp' => strtotime(date('Y-m-d h:s:i')),
                        'crdate' => strtotime(date('Y-m-d h:s:i')),
                        'cruser_id' => $BeUser['uid'],
                        'number_of_records' => $total_records,
                        'total_success' => $success_records,
                        'total_fails' => $fails_records,
                        'total_update' => $update_records,
                        'added_by' => $BeUser['uid'],
                        'created_date' => date('Y-m-d h:s:i'),
                        'records_log' =>  json_encode($errorData, TRUE)
                        ];

            if($redirect_url){
                $i=1;
                $urlstructture = '';
                foreach ($redirect_url as $url) {
                   $urlstructture .=$i.' : '.$url['active-url'].'(Previous Link : '.$url['past-url'].')';
                   $i++;
                }
                $logData['redirect_json'] = $urlstructture;
            }

            try {
    
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_log_manage');

                $affectedRows = $queryBuilder
                    ->insert('tx_log_manage')
                    ->values($logData)
                    ->execute();

                $tableUid = $queryBuilder->getConnection()->lastInsertId();

                $this->addFlashMessage('Data imported successfully. See Log Manager for more..', 'Success');
                $content = ['redirect'=>0,'missing' => 0];
                session_start();
                if($redirect_url) {
                    $content['redirect'] = 1;
                    $_SESSION["redirect"] = $redirect_url;
                } else {
                    $_SESSION["redirect"] = '';
                }
                if($errorData) {
                    $content['missing'] = 1;
                    $_SESSION["missing"] = $errorData;
                } else {
                    $_SESSION["missing"] = '';
                }

                $this->redirect('import', null, null, [ 'data' => $content]);   

            } catch (Exception $e) {
                $this->addFlashMessage('Data imported successfully. Log not generated', 'Warning');
                $content = ['redirect'=>$redirect_url,'missing' => $errorData];
                $this->redirect('import', null, null, [ 'data' => $content]);
            }

        } else {

            $this->addFlashMessage('Sorry, Imported File type is not supported it should be : CSV.', 'An error occurred', FlashMessage::ERROR);
            $this->redirect('import');

        }
        
    }

    /**
    *  Server side validation check
    */ 
    function validateCsv($postData, $rowno) {

        $validationError = [];
        if (empty($postData['id'])) {
            $validationError[] = ['rowno'=>$rowno, 'value'=>'Id'];
            // $this->addFlashMessage('Sorry, Post Id is required. Row : '.$rowno.'.', 'An error occurred', FlashMessage::ERROR);
            // $this->redirect('import');
            //add log
        }

        if (empty($postData['title'])) {
            $validationError[] = ['rowno'=>$rowno, 'value'=>'Title'];
            // $this->addFlashMessage('Sorry, Post name is required. Row : '.$rowno.'.', 'An error occurred', FlashMessage::ERROR);
            // $this->redirect('import');
            //add log
        }

        if (empty($postData['content'])) {
            $validationError[] = ['rowno'=>$rowno, 'value'=>'Content'];
            // $this->addFlashMessage('Sorry, Content is required. Row : '.$rowno.'.', 'An error occurred', FlashMessage::ERROR);
            // $this->redirect('import');
            //add log
        }        
        return $validationError;
    }

    /**
     * action Log Manager
     *
     * @return void
     */
    public function logmanagerAction()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_log_manage');
        $data = $queryBuilder
            ->select('*')
            ->from('tx_log_manage')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
            )
            ->orderBy('uid', 'DESC')
            ->execute()
            ->fetchAll();
        
        $assign = [
            'action' => 'logmanager',
            'constant' => $this->constants,
            'logs-data' => $data
        ];
        
        $this->view->assignMultiple($assign);
    }

    /**
    * Trim html and remove unwanted space from the htmls 
    */

    public function minifier($code) {
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
