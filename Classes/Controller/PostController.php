<?php
namespace NITSAN\NsWpMigration\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use GeorgRinger\News\Domain\Model\News;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use GeorgRinger\News\Domain\Repository\NewsRepository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use NITSAN\NsWpMigration\Domain\Repository\CategoryRepository;
use NITSAN\NsWpMigration\Domain\Repository\ContentRepository;
use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Domain\Repository\AuthorRepository;
use Mediadreams\MdNewsAuthor\Domain\Model\NewsAuthor;
use Mediadreams\MdNewsAuthor\Domain\Repository\NewsAuthorRepository;
use NITSAN\NsWpMigration\Domain\Repository\LogManageRepository;
use NITSAN\NsWpMigration\Domain\Model\LogManage;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use GeorgRinger\News\Domain\Model\Tag as NewsTag;
use GeorgRinger\News\Domain\Repository\TagRepository;
use T3G\AgencyPack\Blog\Domain\Model\Tag as BlogTag;
use T3G\AgencyPack\Blog\Domain\Repository\TagRepository as blogTagRepository;
use DOMDocument;
use HTMLPurifier;
use HTMLPurifier_Config;

/***
 *
 * This file is part of the "[Nitsan] NS Wp Migration" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2023 T3: Navdeep <sanjay@nitsan.in>, NITSAN Technologies Pvt Ltd
 *
 ***/

/**
 * PostController
 */
class PostController extends AbstractController
{
    protected ?Post $blogPost = null;
    protected ?News $newsPost = null;
    protected ?PageRepository $pageRepository = null;
    protected ?PostRepository $blogPostRepository = null;
    protected ?NewsRepository $newsPostRepository = null;
    protected ?CategoryRepository $categoryRepository = null;
    protected ?ContentRepository $contentRepository = null;
    protected ?AuthorRepository $authorRepository = null;
    protected ?NewsAuthorRepository $newsAuthorRepository = null;
    protected ?LogManageRepository $logManageRepository = null;
    protected ?BackendUserRepository $backendUserRepository = null;
    protected ?TagRepository $newsTagRepository = null;
    protected ?blogTagRepository $blogTagRepository = null;

    public function __construct(
        Post $blogPost,
        News $newsPost,
        PageRepository $pageRepository,
        PostRepository $blogPostRepository,
        NewsRepository $newsPostRepository,
        CategoryRepository $categoryRepository,
        ContentRepository $contentRepository,
        AuthorRepository $authorRepository,
        NewsAuthorRepository $newsAuthorRepository,
        LogManageRepository $logManageRepository,
        BackendUserRepository $backendUserRepository,
        TagRepository $newsTagRepository,
        blogTagRepository $blogTagRepository
    ) {
        $this->blogPost = $blogPost;
        $this->newsPost = $newsPost;
        $this->pageRepository = $pageRepository;
        $this->blogPostRepository = $blogPostRepository;
        $this->newsPostRepository = $newsPostRepository;
        $this->categoryRepository = $categoryRepository;
        $this->contentRepository = $contentRepository;
        $this->authorRepository = $authorRepository;
        $this->newsAuthorRepository = $newsAuthorRepository;
        $this->logManageRepository = $logManageRepository;
        $this->backendUserRepository = $backendUserRepository;
        $this->newsTagRepository = $newsTagRepository;
        $this->blogTagRepository = $blogTagRepository;
    }

    /**
     * action formsSettings
     *
     * @return void
     */
    public function importAction(array $data = [])
    {
        session_start();
        $assign = [
            'action' => 'import',
            'constant' => $this->constants
        ];
        if ($data) {
            $assign['redirect'] = $_SESSION['redirect'];
            $assign['missing'] = $_SESSION['missing'];
        }
        $this->view->assignMultiple($assign);
    }

    /**
     * Import action for the store migration data
     */
    public function importFormAction()
    {
        $requestData = $this->request->getArguments();
        if (!$requestData['storageId']) {
            $massage = LocalizationUtility::translate('storageId.require', 'ns_wp_migration');
            $this->addFlashMessage($massage, FlashMessage::ERROR);
            $this->redirect('import');
        }

        if ($this->pageRepository->getPage($requestData['storageId'])) {
            if ($requestData['dataFile']) {
                $this->importCsvData($requestData['dataFile'], $requestData['postType'], (int)$requestData['storageId']);
            }
        }

        $massage = LocalizationUtility::translate('import.success', 'ns_wp_migration');
        $this->addFlashMessage($massage, FlashMessage::OK);
        $this->redirect('logmanager');
    }

    /**
     * Get the csv files and 
     * @param array $file
     * @param string $dockType
     * @param int $storageId
     * @return array
     */
    public function importCsvData(array $file, string $dockType, int $storageId): array
    {
        $logs = [];
        if ($this->checkValideFile($file)) {
            $handle = fopen($file['tmp_name'], 'r');
            $columns = fgetcsv($handle, 10000, ",");
            $record = 1;
            $data = [];
            while (($row = fgetcsv($handle, 10000, ",")) !== false) {
                $data[$record] = array_combine($columns, $row);
                $record++;
            }
            $flag = $this->storeData($data, $dockType, $storageId);
        }
        return $logs;
    }

    /**
     * Storing post Types information
     * @param array $data
     * @param string $dockType
     * @param int $storageId
     * @return array
     */
    function storeData(array $data, string $dockType, int $storageId): array
    {
        $response = [];
        if ($dockType == 'news') { 
            $response = $this->createNewsArticle($data, $storageId);
            return $response;
        } else {
            $response = $this->createPagesAndBlog($data, $storageId, $dockType);
            return $response;
        }
    }

    /**
     * Create news articles
     * @param array $data
     * @param int $storageId
     * @return array
     */
    public function createNewsArticle(array $data, int $storageId): array
    {   
        if ($data) {
            $numberOfRecords = count($data);
            $success = 0;
            $fails = 0;
            $updatedRecords = 0;
            $beUserId = $GLOBALS['BE_USER']->user['uid'];
            $beUser = $this->backendUserRepository->findByUid($beUserId);
            $logManager = GeneralUtility::makeInstance(LogManage::class);
            foreach ($data as $newItems) {
                if($newItems['post_title']) {
                    // validate news items first
                    $newsArticle = GeneralUtility::makeInstance(News::class);
                    
                    if($this->contentRepository->findNewsBySlug($newItems['post_name'])) {
                        $newsId = $this->contentRepository->findNewsBySlug($newItems['post_name']);
                        $newsArticle = $this->newsPostRepository->findByUid($newsId);
                    }
                    
                    $newsArticle->setTitle($newItems['post_title']);
                    $newsArticle->setPid($storageId);
                    $newsArticle->setType(0);
                    $newsArticle->setDescription($newItems['post_excerpt']);
                    if(isset($newItems['post_content']) && !empty($newItems['post_content'])) {
                        $htmlContent = $this->processPostContentHtml($newItems);
                        $newsArticle->setBodytext($htmlContent);
                    }
                    $newsArticle->setPathSegment($newItems['post_name']);
                    $newsArticle->setTeaser($newItems['post_excerpt']);
                    $postDate = explode(" ", $newItems['post_date']);
                    if(isset($postDate[0])){
                        $date = \DateTime::createFromFormat('d/m/y', $postDate[0]);
                        $formattedDate = $date->format('Y-m-d');
                        $datetime = new \DateTime($formattedDate);
                        $newsArticle->setDatetime($datetime); // issue regarding to truncate data
                    }
                    $newsArticle->setPathSegment($newItems['post_name']);
                    if($this->contentRepository->findNewsBySlug($newItems['post_name'])) {
                        $this->newsPostRepository->update($newsArticle);
                        $updatedRecords++;
                    } else {
                        $this->newsPostRepository->add($newsArticle);
                        $success++;
                    }
                    $this->persistenceManager->persistAll();
                    $recordId = $newsArticle->getUid();
                    
                    if(isset($newItems['tax_category.name']) && !empty($newItems['tax_category.name'])) {
                        $categories = GeneralUtility::trimExplode(',', $newItems['tax_category.name']);
                        $this->categoryRepository->updateNewsCategoriesCounts($recordId, count($categories));
                        $categories = $this->insertCategories($categories, $storageId, $recordId, 'tx_news_domain_model_news', 1);
                    }

                    if(isset($newItems['tax_post_tag.name']) && !empty($newItems['tax_post_tag.name'])) {
                        $tagsList = GeneralUtility::trimExplode(',', $newItems['tax_post_tag.name']);
                        $this->categoryRepository->updateNewsTagsCounts($recordId, count($tagsList));
                        $this->manageTagsForNews($recordId, $tagsList, $storageId);
                    }

                    if(isset($newItems['image.url']) && !empty($newItems['image.url'])) { 
                        $this->manageFeaturedImages($recordId, $newItems['image.url'], 'tx_news_domain_model_news', 'fal_media', $storageId, $beUserId);
                    }

                    if(isset($newItems['author.user_email']) && !empty($newItems['author.user_email'])) {
                        $this->manageAuthorInformation('news', $recordId, $newItems, $storageId);
                    }

                } else {
                    $fails++;
                }
            }

            $logManager->setPid(1);
            $logManager->setNumberOfRecords($numberOfRecords);
            $logManager->setTotalSuccess($success);
            $logManager->setTotalFails($fails);
            $logManager->setTotalUpdate($updatedRecords);
            $dateTime = new \DateTime(date('Y-m-d'));
            $logManager->setCreatedDate($dateTime);
            $logManager->setAddedBy($beUser);
            $this->logManageRepository->add($logManager);
            $this->persistenceManager->persistAll();
        }

        $massage = LocalizationUtility::translate('import.success', 'ns_wp_migration');
        $response['message'] = $massage;
        $response['result'] = true;
        return $response;
    }

    /**
     * Create pages for sites
     * @param array $data
     * @param int $storageId
     * @param string $dockType
     * @return array
     */
    public function createPagesAndBlog(array $data, int $storageId, $dockType): array
    {
        $response = [];
        $numberOfRecords = count($data);
        $success = 0;
        $fails = 0;
        $updatedRecords = 0;
        $beUserId = $GLOBALS['BE_USER']->user['uid'];
        $beUser = $this->backendUserRepository->findByUid($beUserId);
        $logManager = GeneralUtility::makeInstance(LogManage::class);
        $logManager->setPid(1);
        $logManager->setNumberOfRecords($numberOfRecords);
        
        foreach ($data as $pageItem) {
            // Validate Pages Items First
            if($pageItem['post_title']) {
                // Creating Pages
                $pageData = [
                    'title' => $pageItem['post_title'],
                    'hidden' => 0,
                    'tstamp' => time(),
                    'crdate' => strtotime($pageItem['post_date']),
                    'pid' => $storageId,
                    'slug' => '/'.$pageItem['post_name'],
                    'sys_language_uid' => 0,
                    'doktype' => 1,
                    'description' => $pageItem['post_excerpt']
                ];

                if($dockType == 'blog') {
                    $pageData['doktype'] = 137;
                    $postDate = explode(" ", $pageItem['post_date']);
                    if(isset($postDate[0])){
                        $date = \DateTime::createFromFormat('d/m/y', $postDate[0]);
                        $formattedDate = $date->format('Y-m-d');
                        $pageData['publish_date'] = strtotime($formattedDate);
                        $pageData['crdate_month'] = date('m', strtotime($formattedDate));
                        $pageData['crdate_year'] = date('Y', strtotime($formattedDate));
                    }
                    if(isset($pageItem['author.user_email'])) {
                        $pageData['authors'] = 1;
                    }
                }
                
                if($this->contentRepository->findPageBySlug('/'.$pageItem['post_name'])) {
                    $recordId = $this->contentRepository->findPageBySlug('/'.$pageItem['post_name']);
                    $recordId = $this->contentRepository->updatePageRecord($pageData, $recordId);
                    $updatedRecords++;
                } else {
                    $recordId = $this->contentRepository->createPageRecord($pageData);
                    if($dockType == 'blog') {
                        if(isset($pageItem['author.user_email'])) {
                            $this->manageAuthorInformation($dockType, $recordId, $pageItem, $storageId);
                        }
                    }
                    $success++;
                }

                if($dockType == 'blog') {
                    if(isset($pageItem['author.user_email'])) {
                        $this->manageAuthorInformation($dockType, $recordId, $pageItem, $storageId);
                    }
                }
                
                // post content crete
                if (isset($pageItem['post_content']) && !empty($pageItem['post_content'])) {
                    $htmlContent = $this->processPostContentHtml($pageItem);
                    $contentElements = ['pid' => $recordId,
                                        'hidden' => 0,
                                        'tstamp' => time(),
                                        'crdate' => time(),
                                        'CType' => 'text',
                                        'bodytext' => $htmlContent,
                                        'colPos' => 0,
                                        'sectionIndex' => 1];
                    $this->contentRepository->insertContnetElements($contentElements);
                }
                
                // Category add and Map
                if(isset($pageItem['tax_category.name']) && !empty($pageItem['tax_category.name'])) {
                    $categories = GeneralUtility::trimExplode(',', $pageItem['tax_category.name']);
                    $this->categoryRepository->updateBlogCategoriesCounts($recordId, count($categories));
                    if($dockType == 'blog') {
                        $categories = $this->insertCategories($categories, $storageId, $recordId, 'pages', 100);
                    } else {
                        $categories = $this->insertCategories($categories, $storageId, $recordId, 'pages', 1);
                    }
                }

                if($dockType == 'blog') {
                    if(isset($pageItem['tax_post_tag.name']) && !empty($pageItem['tax_post_tag.name'])) {
                        $tagsList = GeneralUtility::trimExplode(',', $pageItem['tax_post_tag.name']);
                        $this->contentRepository->updateBlogsTagsCounts($recordId, count($tagsList));
                        $this->manageTagsForBlogs($recordId, $tagsList, $storageId);
                    }

                    if(isset($pageItem['image.url']) && !empty($pageItem['image.url'])) { 
                        $this->manageFeaturedImages($recordId, $pageItem['image.url'], 'pages', 'featured_image', $storageId, $beUserId);
                    }
                }
                
            } else {
                $fails++;
            }
        }

        $logManager->setTotalSuccess($success);
        $logManager->setTotalFails($fails);
        $logManager->setTotalUpdate($updatedRecords);
        $dateTime = new \DateTime(date('Y-m-d'));
        $logManager->setCreatedDate($dateTime);
        $logManager->setAddedBy($beUser);
        $this->logManageRepository->add($logManager);
        $this->persistenceManager->persistAll();
        $massage = LocalizationUtility::translate('import.success', 'ns_wp_migration');
        $response['message'] = $massage;
        $response['result'] = true;
        return $response;
    }

    /**
     * Process data and return post types array
     * @param array $data
     * @return string
     */
    function processPostContentHtml(array $data): string
    {
        $doc = new DOMDocument();
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $cleanHtml = $purifier->purify($data['post_content']);
        $htmlString = $cleanHtml;
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        
        // Create a DOMDocument object
        $dom = new DOMDocument();
        // Load HTML content into the DOMDocument
        $dom->loadHTML($htmlString);

        // Get all image tags in the HTML
        $imageTags = $dom->getElementsByTagName('img');

        // Loop through each image tag
        foreach ($imageTags as $img) {
            // Get the value of the src attribute
            $src = $img->getAttribute('src');
            if($src) {
                $fileName = basename($src);
                $folder = 'fileadmin/user_upload/';
                $dstFolder = \TYPO3\CMS\Core\Core\Environment::getPublicPath() .'/'. $folder;
                if (!file_exists($dstFolder)) {
                    GeneralUtility::mkdir_deep($dstFolder);
                }
                $out = file_get_contents($src);
                file_put_contents($dstFolder . '/' . $fileName, $out);
                // Get TYPO3 file storage
                $fileStorage = $resourceFactory->getDefaultStorage();
                $folder = $fileStorage->getFolder('user_upload');
                $fileObject = $fileStorage->getFileInFolder($fileName, $folder);
                // Get the accessible URL of the file
                $accessibleUrl = $fileObject->getPublicUrl();
                $img->setAttribute('src', $accessibleUrl);
            }
        }

        // Get the modified HTML content
        $htmlString = $dom->saveHTML();
        return $htmlString;

    }

    /**
     * action Log Manager
     *
     * @return void
     */
    public function logmanagerAction()
    {
        $data = $this->logManageRepository->getAllLogs();
        $assign = [
            'action' => 'logmanager',
            'constant' => $this->constants,
            'logs-data' => $data
        ];

        $this->view->assignMultiple($assign);
    }

    /**
     * Upload Image
     * @param string $image
     * @return array $image
     */
    function uploadImage(string $image): array 
    {
        $response = [];
        return $response;
    }

    /**
     * Insert Categories
     * @param array $categories
     * @param int $storageId
     * @param  int  $recordId
     * @param string $recordType
     * @param int $categoryType
     * @return array $categoriesCount
     */
    function insertCategories(array $categories, int $storageId, int $recordId, string $tableName, $categoryType): array
    {
        $categoriesLists = [];
        if ($categories) {
            
            foreach ($categories as $key => $value) {
                $categoriesID = $this->categoryRepository->checkIsExist($value, $storageId);
                array_push($categoriesLists, $categoriesID);
                if(empty($categoriesID)) {
                    $slug = str_replace(" ", "-", strtolower($value));
                    $item = ['pid' => $storageId,
                            'hidden' => '0',
                            'title' => $value,
                            'record_type' => $categoryType ? $categoryType: '1',
                            'slug' => $slug
                            ];
                    
                    $newCategory = $this->categoryRepository->insertCategory($item, $recordId, $tableName);
                    array_push($categoriesLists, $newCategory);
                } else {
                    $this->categoryRepository->mapcategories($categoriesID, $recordId, $tableName);
                    array_push($categoriesLists, $categoriesID);
                }
            }
        }
        return $categoriesLists;
        
    }

    /**
     * Create author for post types
     * @param string $record_type
     * @param int $record_id
     */
    function manageAuthorInformation(string $record_type, int $record_id, $data, $storageId) {
        if($record_type == 'blog') {
            if (isset($data['author.user_email']) && !empty($data['author.user_email'])) {
                $author = $this->contentRepository->findAuthorByEmail($data['author.user_email'], $storageId);
                if($author) {
                    $mapingData = ['uid_local' => $record_id, 'uid_foreign'=> $author];
                    $this->contentRepository->assignAuthorToBlogs($mapingData);
                } else {
                    // Fresh author 
                    $authorInfo = GeneralUtility::makeInstance(Author::class);
                    $authorInfo->setPid($storageId);
                    if(isset($data['author.display_name']) && !empty($data['author.display_name'])) {
                        $authorInfo->setName($data['author.display_name']);
                        $authorInfo->setSlug(str_replace(" ", "-", strtolower($data['author.display_name'])));
                    }
                    if(isset($data['author.user_email']) && !empty($data['author.user_email'])) {
                        $authorInfo->setEmail($data['author.user_email']);
                    }
                    if(isset($data['author.user_nicename']) && !empty($data['author.user_nicename'])) {
                        $authorInfo->setName($data['author.user_nicename']);
                    }
                    $blogs = $this->blogPostRepository->findByUid($record_id);
                    if ($blogs) {
                        $authorInfo->addPost($blogs);
                    }

                    $this->authorRepository->add($authorInfo);
                    $this->persistenceManager->persistAll();
                    $author = $authorInfo->getUid();
                    $mapingData = ['uid_local' => $record_id, 'uid_foreign'=> $author];
                    $this->contentRepository->assignAuthorToBlogs($mapingData);
                }
                
            }
        } else {
            if(isset($data['author.user_email']) && !empty($data['author.user_email'])) {
                $newsAuthor = $this->contentRepository->findAuthorByNewsEmail($data['author.user_email'], $storageId);
                if($newsAuthor) {
                    $news = $this->newsPostRepository->findByUid($record_id);
                    if ($news) {
                        $authorRelation = ['uid_local' => $record_id , 'uid_foreign' => $newsAuthor];
                        $this->contentRepository->assignAuthorToNews($authorRelation);
                    }
                } else {

                    $newsAuthor = GeneralUtility::makeInstance(NewsAuthor::class);
                    $newsAuthor->setPid($storageId);
                    if(isset($data['author.display_name']) && !empty($data['author.display_name'])) {
                        $newsAuthor->setFirstname($data['author.display_name']);
                        $newsAuthor->setSlug(str_replace(" ", "-", strtolower($data['author.display_name'])));
                    }

                    if(isset($data['author.user_email']) && !empty($data['author.user_email'])) {
                        $newsAuthor->setEmail($data['author.user_email']);
                    }

                    if(isset($data['author.user_nicename']) && !empty($data['author.user_nicename'])) {
                        $newsAuthor->setLastname($data['author.user_nicename']);
                    }

                    $news = $this->newsPostRepository->findByUid($record_id);
                    $this->newsAuthorRepository->add($newsAuthor);
                    $this->persistenceManager->persistAll();
                    if ($news) {
                        $authorRelation = ['uid_local' => $record_id , 'uid_foreign' => $newsAuthor->getUid()];
                        $this->contentRepository->assignAuthorToNews($authorRelation);
                    }

                }
            }
        }
    }

    /**
     * Manage Tags for News and Blogs
     * @param int $recordId
     * @param array $tagList
     * @param int $storageId
     */
    function manageTagsForNews(int $recordId, array $tagList, int $storageId) {
        $tagItemList = [];
        if ($tagList) {
            
            foreach ($tagList as $key => $value) {
                $slug = str_replace(" ", "-", strtolower($value));
                $tagItem = $this->categoryRepository->checkIsTagExist($slug);
                if(empty($tagItem)) {
                    $tagItem = GeneralUtility::makeInstance(NewsTag::class);
                    $tagItem->setTitle($value);
                    $tagItem->setSlug($slug);
                    $tagItem->setPid($storageId);
                    $this->newsTagRepository->add($tagItem);
                    $this->persistenceManager->persistAll();
                    $tagId = $tagItem->getUid();
                    $this->categoryRepository->mapTagItems($recordId, $tagId);
                } else {
                    $this->categoryRepository->mapTagItems($recordId, $tagItem);
                    array_push($tagItemList, $tagItem);
                }
            }
        }
        return $tagItemList;
    }

    /**
     * Manage Tags for News and Blogs
     * @param int $recordId
     * @param array $tagList
     * @param int $storageId
     */
    function manageTagsForBlogs(int $recordId, array $tagList, int $storageId) {
        $tagItemList = [];
        if ($tagList) {
            
            foreach ($tagList as $key => $value) {
                $slug = str_replace(" ", "-", strtolower($value));
                $tagItem = $this->contentRepository->checkIsTagExist($slug);
                if(empty($tagItem)) {
                    $tagItem = GeneralUtility::makeInstance(BlogTag::class);
                    $tagItem->setTitle($value);
                    $tagItem->setPid($storageId);
                    $this->blogTagRepository->add($tagItem);
                    $this->persistenceManager->persistAll();
                    $tagId = $tagItem->getUid();
                    $this->contentRepository->mapTagItems($recordId, $tagId);
                } else {
                    $this->contentRepository->mapTagItems($recordId, $tagItem);
                    array_push($tagItemList, $tagItem);
                }
            }
        }
        return $tagItemList;
    }

    /**
     * Manage featured Images
     * @param int $recordId
     * @param string $image
     * @param string $table
     * @param string $field
     * @param int $storageId
     * @param int $beUserId
     */
     function manageFeaturedImages($recordId, $image, $table, $field, $storageId, $beUserId) {
        $urlDecode = parse_url($image);
        $paths = $urlDecode['path'];
        $path = trim(str_replace('/wp-content','',$paths));
        $url = $image;
        $fileName = basename($url);
        $mimetype = 'image/'.pathinfo($fileName, PATHINFO_EXTENSION);
        if($table == 'pages') {
            $folder = '/fileadmin/blog';
            $folderName = 'blog';
        } else {
            $folder = '/fileadmin/news';
            $folderName = 'news';
        }
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $dstFolder = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . $folder;
        
        if (!file_exists($dstFolder)) {
            GeneralUtility::mkdir_deep($dstFolder);
        }

        $out = file_get_contents($url);
        file_put_contents($dstFolder . '/' . $fileName, $out);
        // Get TYPO3 file storage
        $fileStorage = $resourceFactory->getDefaultStorage();
        $folder = $fileStorage->getFolder($folderName);
        $fileObject = $fileStorage->getFileInFolder($fileName, $folder);
        $featureImageId = $fileObject->getUid();

        $imageData = ['pid' => 0,
                    'cruser_id' =>  $beUserId,
                    'deleted' => '0',
                    'uid_local' => $featureImageId,
                    'uid_foreign' => $recordId,
                    'tablenames' => $table,
                    'fieldname' => $field,
                    'table_local' => 'sys_file'
        ];

        $this->contentRepository->refSystemFile($recordId, $imageData);
    }
}