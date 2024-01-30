<?php
namespace NITSAN\NsWpMigration\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use GeorgRinger\News\Domain\Model\News;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use GeorgRinger\News\Domain\Repository\NewsRepository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Html\HtmlParser;
use NITSAN\NsWpMigration\Domain\Repository\CategoryRepository;
use NITSAN\NsWpMigration\Domain\Repository\ContentRepository;
use T3G\AgencyPack\Blog\Domain\Model\Author;
use T3G\AgencyPack\Blog\Domain\Repository\AuthorRepository;
use Mediadreams\MdNewsAuthor\Domain\Model\NewsAuthor;
use Mediadreams\MdNewsAuthor\Domain\Repository\NewsAuthorRepository;
use NITSAN\NsWpMigration\Domain\Repository\LogManageRepository;
use NITSAN\NsWpMigration\Domain\Model\LogManage;

use DOMDocument;

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
        LogManageRepository $logManageRepository
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
        try {
            if ($dockType == 'news') { 
                $response = $this->createNewsArticle($data, $storageId);
                return $response;
            } else {
                $response = $this->createPagesAndBlog($data, $storageId, $dockType);
                return $response;
            }

        } catch (\Throwable $th) {

            $response['message'] = $th->getMessage();
            $response['result'] = false;
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
            foreach ($data as $newItems) {
                if($newItems['post_title']) {
                    // validate news items first
                    $dataItems = $newItems['post_content'];
                    $newsArticle = GeneralUtility::makeInstance(News::class);
                    if($this->contentRepository->findNewsBySlug($newItems['post_name'])) {
                        $newsId = $this->contentRepository->findNewsBySlug($newItems['post_name']);
                        $newsArticle = $this->newsPostRepository->findByUid($newsId);
                    }
                    $newsArticle->setTitle($newItems['post_title']);
                    $newsArticle->setPid($storageId);
                    $newsArticle->setDescription($newItems['post_excerpt']);
                    $newsArticle->setBodytext($dataItems);
                    $newsArticle->setPathSegment($newItems['post_name']);
                    $newsArticle->setDatetime(strtotime($newItems['post_date'])); // issue regarding to truncate data
                    $newsArticle->setPathSegment($newItems['post_name']);
                    if($this->contentRepository->findNewsBySlug($newItems['post_name'])) { 
                        $this->newsPostRepository->add($newsArticle);
                    } else {
                        $this->newsPostRepository->update($newsArticle);
                    }
                    $this->persistenceManager->persistAll();
                    $recordId = $newsArticle->getUid();
                    if($newItems['tax_category.name']) {
                        $categories = GeneralUtility::trimExplode(',', $newItems['tax_category.name']);
                        $this->categoryRepository->updateNewsCategoriesCounts($recordId, count($categories));
                        $categories = $this->insertCategories($categories, $storageId, $recordId, 'tx_news_domain_model_news', 1);
                    }

                    if(isset($newItems['author.user_email'])) {
                        $this->manageAuthorInformation('news', $recordId, $newItems);
                    }
                    $success++;
                } else {
                    // write log for missing value of title
                    $fails++;
                }
                    
            }
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
        $success = 0;
        $fails = 0;
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
                    'doktype' => 1,
                ];

                if($dockType == 'blog') {
                    $pageData['doktype'] = 137;
                    $pageData['publish_date'] = strtotime($pageItem['post_date']);
                    $pageData['crdate_month'] = date('m', strtotime($pageItem['post_date']));
                    $pageData['crdate_year'] = date('Y', strtotime($pageItem['post_date']));
                }

                if($this->contentRepository->findPageBySlug('/'.$pageItem['post_name'])) {
                    $recordId = $this->contentRepository->findPageBySlug('/'.$pageItem['post_name']);
                    $this->contentRepository->updatePageRecord($pageData, $recordId);
                } else {
                    $recordId = $this->contentRepository->createPageRecord($pageData);
                }

                // post content crete
                if (isset($pageItem['post_content'])) {
                    $contentElements = ['pid' => $recordId,
                                        'hidden' => 0,
                                        'tstamp' => time(),
                                        'crdate' => time(),
                                        'CType' => 'text',
                                        'bodytext' => $pageItem['post_content'],
                                        'colPos' => 0,
                                        'sectionIndex' => 1];
                    $this->contentRepository->insertContnetElements($contentElements);
                }

                // Category add and Map
                if($pageItem['tax_category.name']) {
                    $categories = GeneralUtility::trimExplode(',', $pageItem['tax_category.name']);
                    $this->categoryRepository->updateBlogCategoriesCounts($recordId, count($categories));
                    if($dockType == 'blog') {
                        $categories = $this->insertCategories($categories, $storageId, $recordId, 'pages', 100);
                    } else {
                        $categories = $this->insertCategories($categories, $storageId, $recordId, 'pages', 1);
                    }
                }

                if($dockType == 'blog') { 
                    if(isset($pageItem['author.user_email'])) {
                        $this->manageAuthorInformation($dockType, $recordId, $pageItem);
                    }
                }
                $success++;
            } else {
                // write log for missing value of title
                $fails++;
            }
        }
        
        $massage = LocalizationUtility::translate('import.success', 'ns_wp_migration');
        $response['message'] = $massage;
        $response['result'] = true;
        return $response;
    }

    /**
     * Process data and return post types array
     * @param array $data
     * @param int $uid
     * @return 
     */
    function processData(array $data, int $uid): array
    {
        $contetnElements = array();

        $parseObj = GeneralUtility::makeInstance(HtmlParser::class);
        $tags = 'p, h1, h2, h3, h4, h5, h6, div, figure, section';
        $contentItems = $parseObj->splitIntoBlock($tags, $data['post_content']);
        foreach ($contentItems as $key => $value) {
            
        }

        return $contetnElements;
    }

    /**
     * Write logs for missing information about post types
     * @param array $data
     * @return bool
     */
    function writeLogs(array $data): bool
    {
        try {
            return true;
        } catch (\Throwable $th) {
            return false;
        }
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
    function manageAuthorInformation($record_type, $record_id, $data) {
        if($record_type == 'blog') {
            if (isset($data['author.user_email']) && !empty($data['author.user_email'])) {
                $author = $this->contentRepository->findAuthorByEmail($data['author.user_email']);
                if($author) {
                    $authorInfo = $this->authorRepository->findByUid($author);
                    $blogs = $this->blogPostRepository->findByUid($record_id);
                    if ($blogs) {
                        $authorInfo->setPosts($blogs);
                        $this->authorRepository->update($author);
                        $this->persistenceManager->persistAll();
                    }
                } else {
                    // Fresh author 
                    $authorInfo = GeneralUtility::makeInstance(Author::class);
                    if(isset($data['author.display_name'])) {
                        $authorInfo->setName($data['author.display_name']);
                        $authorInfo->setSlug(str_replace(" ", "-", strtolower($data['author.display_name'])));
                    }
                    if(isset($data['author.user_email'])) {
                        $authorInfo->setEmail($data['author.user_email']);
                    }
                    if(isset($data['author.user_nicename'])) {
                        $authorInfo->setName($data['author.user_nicename']);
                    }
                    $blogs = $this->blogPostRepository->findByUid($record_id);
                    if ($blogs) {
                        $authorInfo->setPosts($blogs);
                    }
                    $this->authorRepository->add($authorInfo);
                    $this->persistenceManager->persistAll();
                }  
                
            }
        } else {
            if(isset($data['author.user_email']) && !empty($data['author.user_email'])) {
                $newsAuthor = $this->contentRepository->findAuthorByNewsEmail($data['author.user_email']);
                if($newsAuthor) {
                    $newsAuthorInfo = $this->newsAuthorRepository->findByUid($newsAuthor);
                    $blogs = $this->blogPostRepository->findByUid($record_id);
                    if ($blogs) {
                        $newsAuthorInfo->setPosts($blogs);
                        $this->authorRepository->update($newsAuthorInfo);
                        $this->persistenceManager->persistAll();
                    }
                } else {
                    $newsAuthor = GeneralUtility::makeInstance(NewsAuthor::class);
                    if(isset($data['author.display_name'])) {
                        $newsAuthor->setFirstname($data['author.display_name']);
                        $newsAuthor->setSlug(str_replace(" ", "-", strtolower($data['author.display_name'])));
                    }
                    if(isset($data['author.user_email'])) {
                        $newsAuthor->setEmail($data['author.user_email']);
                    }
                    if(isset($data['author.user_nicename'])) {
                        $newsAuthor->setLastname($data['author.user_nicename']);
                    }
                    $blogs = $this->blogPostRepository->findByUid($record_id);
                    if ($blogs) {
                        $newsAuthor->setNews($blogs);
                    }
                    $this->authorRepository->add($newsAuthor);
                    $this->persistenceManager->persistAll();
                }
            }
        }
    }
}