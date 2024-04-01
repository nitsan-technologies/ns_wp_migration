<?php
namespace NITSAN\NsWpMigration\Controller;

use DOMDocument;
use HTMLPurifier;
use HTMLPurifier_Config;
use GeorgRinger\News\Domain\Model\News;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use T3G\AgencyPack\Blog\Domain\Model\Post;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Domain\Model\Author;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use NITSAN\NsWpMigration\Domain\Model\LogManage;
use GeorgRinger\News\Domain\Model\Tag as NewsTag;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use T3G\AgencyPack\Blog\Domain\Model\Tag as BlogTag;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use GeorgRinger\News\Domain\Repository\TagRepository;
use Mediadreams\MdNewsAuthor\Domain\Model\NewsAuthor;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use GeorgRinger\News\Domain\Repository\NewsRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use T3G\AgencyPack\Blog\Domain\Repository\PostRepository;
use T3G\AgencyPack\Blog\Domain\Repository\AuthorRepository;
use NITSAN\NsWpMigration\Domain\Repository\ContentRepository;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use NITSAN\NsWpMigration\Domain\Repository\CategoryRepository;
use NITSAN\NsWpMigration\Domain\Repository\LogManageRepository;
use Mediadreams\MdNewsAuthor\Domain\Repository\NewsAuthorRepository;
use T3G\AgencyPack\Blog\Domain\Repository\TagRepository as blogTagRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

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
    protected $blogPost = null;
    protected $pageRepository = null;
    protected $blogPostRepository = null;
    protected $newsPostRepository = null;
    protected $categoryRepository = null;
    protected $contentRepository = null;
    protected $authorRepository = null;
    protected $newsAuthorRepository = null;
    protected $logManageRepository = null;
    protected $backendUserRepository = null;
    protected $newsTagRepository = null;
    protected $blogTagRepository = null;
    protected $uribuilder = null;
    protected ExtensionConfiguration $extensionConfiguration;
    public function __construct(
        Post $blogPost,
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
        blogTagRepository $blogTagRepository,
        UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        ExtensionConfiguration $extensionConfiguration,
    ) {
        $this->blogPost = $blogPost;
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
        $this->uribuilder = $uriBuilder;
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * action formsSettings
     *
     * @return ResponseInterface
     */
    public function importAction(): ResponseInterface
    {
        $assign = [
            'action' => 'import',
            'constant' => $this->constants,
            'version' => 11
        ];

        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
            $this->view->assignMultiple($assign);
            return $this->htmlResponse();
        } else {
            $assign['version'] = 12;
            $view = $this->initializeModuleTemplate($this->request);
            $view->assignMultiple($assign);
            return $view->renderResponse();
        }
        
    }

    /**
     * Import action for the store migration data
     * @return ResponseInterface
     */
    public function importFormAction(): ResponseInterface
    {
        $requestData = $this->request->getArguments();
        // log url Action
        $loguri = $this->uriBuilder
			->reset()
			->uriFor('logManager', [], 'Post', 'NsWpMigration', 'importModule');
		$loguri = $this->addBaseUriIfNecessary($loguri);
        // Import url Action
        $importAction = $this->uriBuilder
			->reset()
			->uriFor('import', [], 'Post', 'NsWpMigration', 'importModule');
		$importAction = $this->addBaseUriIfNecessary($importAction);
        
        $response = 0;

        if (!$requestData['storageId']) {
            $massage = LocalizationUtility::translate('storageId.require', 'ns_wp_migration');
            if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
                $this->addFlashMessage($massage, 'Error', FlashMessage::ERROR);
                return $this->redirect('import');
            } else {
                $this->addFlashMessage($massage, 'Error', ContextualFeedbackSeverity::ERROR);
                return new RedirectResponse($importAction);
            }
        }

        if ($this->pageRepository->getPage($requestData['storageId'])) {
            if((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
                $fileArray = $requestData['dataFile'];
            } else {
                $fileArray = $_FILES['dataFile'];
            }
            $response = $this->importCsvData(
                $fileArray,
                $requestData['postType'],
                (int)$requestData['storageId']);
        } else{
            $massage = LocalizationUtility::translate('error.pageId', 'ns_wp_migration');
            if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
                $this->addFlashMessage($massage, 'Error', FlashMessage::ERROR);
                return $this->redirect('import');
            } else {
                $this->addFlashMessage($massage, 'Error', ContextualFeedbackSeverity::ERROR);
                return new RedirectResponse($importAction);
            }
        }

        if($response === 0) {
            if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
                $response = $this->redirect('import');
            } else {
                $response = new RedirectResponse($importAction);
            }
        } else {
            $massage = LocalizationUtility::translate('import.success', 'ns_wp_migration');
            if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
                $this->addFlashMessage($massage, 'Success', FlashMessage::OK);
                $response = $this->redirect('logManager');
            } else {
                $this->addFlashMessage($massage, 'Success', ContextualFeedbackSeverity::OK);
                $response = new RedirectResponse($loguri);
            }
        }
        return $response;
    }

    /**
     * Get the csv files and
     * @param array $file
     * @param string $dockType
     * @param int $storageId
     * @return int
     */
    public function importCsvData(array $file, string $dockType, int $storageId): int
    {
        if ($this->checkValideFile($file)) {

            $handle = fopen($file['tmp_name'], 'r');
            $columns = fgetcsv($handle, 10000, ",");
            $record = 1;
            $data = [];
            while (($row = fgetcsv($handle, 10000, ",")) !== false) {
                $data[$record] = array_combine($columns, $row);
                $record++;
            }

            if(is_array($data) && isset($data[1], $data[1]['post_title'], $data[1]['post_type'])) {
                $this->storeData($data, $dockType, $storageId);
                return 1;
            } else {
                $massage = LocalizationUtility::translate('error.invalidfileData', 'ns_wp_migration');
                if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
                    $this->addFlashMessage($massage, 'Error', FlashMessage::ERROR);
                } else {
                    $this->addFlashMessage($massage, 'Error', ContextualFeedbackSeverity::ERROR);
                }
                return 0;
            }
        } else {
            $massage = LocalizationUtility::translate('error.invalidFile', 'ns_wp_migration');
            if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
                $this->addFlashMessage($massage, 'Error', FlashMessage::ERROR);
            } else {
                $this->addFlashMessage($massage, 'Error', ContextualFeedbackSeverity::ERROR);
            }
            return 0;
        }
    }

    /**
     * Storing post Types information
     * @param array $data
     * @param string $dockType
     * @param int $storageId
     * @return void
     */
    private function storeData(array $data, string $dockType, int $storageId): void
    {
        if ($dockType === 'news') {
            $this->createNewsArticle($data, $storageId);
        } else {
            $this->createPagesAndBlog($data, $storageId, $dockType);
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
        $response = [];
        if ($data) {
            $numberOfRecords = count($data);
            $success = 0;
            $fails = 0;
            $updatedRecords = 0;
            $beUserId = $GLOBALS['BE_USER']->user['uid'];
            $beUser = $this->backendUserRepository->findByUid($beUserId);
            $logManager = GeneralUtility::makeInstance(LogManage::class);
            foreach ($data as $newItems) {
                if($newItems['post_type'] === 'post') {
                    if($newItems['post_title']) {
                        $newsArticle = GeneralUtility::makeInstance(News::class);
                        if($this->contentRepository->findNewsBySlug($newItems['post_name'])) {
                            $newsId = $this->contentRepository->findNewsBySlug($newItems['post_name']);
                            $newsArticle = $this->newsPostRepository->findByUid($newsId);
                        }
                        
                        $newsArticle->setTitle($newItems['post_title']);
                        $newsArticle->setPid($storageId);
                        $newsArticle->setType(0);
                        $newsArticle->setDescription($newItems['post_excerpt']);
                        if(isset($newItems['post_content'])) {
                            $htmlContent = $this->processPostContentHtml($newItems);
                            $newsArticle->setBodytext($htmlContent);
                        }
                        $newsArticle->setPathSegment($newItems['post_name']);
                        $newsArticle->setTeaser($newItems['post_excerpt']);
                        $postDate = explode(" ", $newItems['post_date']);
                        if(isset($postDate[0])){
                            $date = \DateTime::createFromFormat('d/m/y', $postDate[0]);
                            if($date) {
                                $formattedDate = $date->format('Y-m-d');
                            } else {
                                $formattedDate = date($postDate[0]);
                            }
                            $datetime = new \DateTime($formattedDate);
                            $newsArticle->setDatetime($datetime);
                        }
                        $newsArticle->setPathSegment($newItems['post_name']);
                        if(isset($newItems['post_status']) && $newItems['post_status'] === 'publish') {
                            $newsArticle->setHidden(0);
                        } else {
                            $newsArticle->setHidden(1);
                        }
                        
                        if(isset($newItems['post_status']) && $newItems['post_status'] != 'trash') {
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
                                $categories = $this->insertCategories(
                                    $categories,
                                    $storageId,
                                    $recordId,
                                    'tx_news_domain_model_news',
                                    1);
                            }

                            if(isset($newItems['tax_post_tag.name']) && !empty($newItems['tax_post_tag.name'])) {
                                $tagsList = GeneralUtility::trimExplode(',', $newItems['tax_post_tag.name']);
                                $this->categoryRepository->updateNewsTagsCounts($recordId, count($tagsList));
                                $this->manageTagsForNews($recordId, $tagsList, $storageId);
                            }

                            if(isset($newItems['image.url']) && !empty($newItems['image.url'])) {
                                $this->manageFeaturedImages(
                                    $recordId,
                                    $newItems['image.url'],
                                    'tx_news_domain_model_news',
                                    'fal_media',
                                    $beUserId);
                            }

                            if(isset($newItems['author.user_email']) && !empty($newItems['author.user_email'])) {
                                $this->manageAuthorInformation('news', $recordId, $newItems, $storageId);
                            }
                        }

                    } else {
                        $fails++;
                    }
                }
            }

            $logManager->setPid($storageId);
            $logManager->setNumberOfRecords($numberOfRecords);
            $logManager->setTotalSuccess($success);
            $logManager->setTotalFails($fails);
            $logManager->setTotalUpdate($updatedRecords);
            $dateTime = new \DateTime(date('Y-m-d'));
            $logManager->setCreatedDate($dateTime);
            $logManager->setAddedBy($beUser);
            try {
                $this->logManageRepository->add($logManager);
                $this->persistenceManager->persistAll();
                $massage = LocalizationUtility::translate('import.success', 'ns_wp_migration');
                $response['message'] = $massage;
                $response['result'] = true;
            } catch (\Throwable $th) {
                $massage = LocalizationUtility::translate('import.fail', 'ns_wp_migration');
                $response['message'] = $massage;
                $response['result'] = false;
                $this->logger->error($th->getMessage(),$response);
            }
            
        }
        return $response;
    }

    /**
     * Create pages for sites
     * @param array $data
     * @param int $storageId
     * @param string $dockType
     * @return array
     */
    public function createPagesAndBlog(array $data, int $storageId, string $dockType): array
    {
        $response = [];
        $numberOfRecords = count($data);
        $success = 0;
        $fails = 0;
        $updatedRecords = 0;
        $beUserId = $GLOBALS['BE_USER']->user['uid'];
        $beUser = $this->backendUserRepository->findByUid($beUserId);
        $logManager = GeneralUtility::makeInstance(LogManage::class);
        $logManager->setPid($storageId);
        $logManager->setNumberOfRecords($numberOfRecords);
        foreach ($data as $pageItem) {
            // Validate Pages Items First
            if($pageItem['post_title']) {
                // Creating Pages
                $slugString = preg_replace('/[^A-Za-z0-9 ]/', '', $pageItem['post_title']);
                $slug = strtolower(str_replace(' ', '-', $slugString));
                $pageData = [
                    'title' => $pageItem['post_title'],
                    'hidden' => 0,
                    'tstamp' => time(),
                    'crdate' => strtotime($pageItem['post_date']),
                    'pid' => $storageId,
                    'slug' => '/'.$slug,
                    'sys_language_uid' => 0,
                    'doktype' => 1,
                    'description' => $pageItem['post_excerpt']
                ];

                if($dockType === 'blog') {
                    $pageData['doktype'] = 137;
                    $postDate = explode(" ", $pageItem['post_date']);
                    if(isset($postDate[0])){
                        $date = \DateTime::createFromFormat('d/m/y', $postDate[0]);
                        if($date) {
                            $formattedDate = $date->format('Y-m-d');
                        } else {
                            $formattedDate = date($postDate[0]);
                        }
                        
                        $pageData['publish_date'] = strtotime($formattedDate);
                        $pageData['crdate_month'] = date('m', strtotime($formattedDate));
                        $pageData['crdate_year'] = date('Y', strtotime($formattedDate));
                    }
                }

                if ($pageItem['post_status'] === 'draft') {
                    $pageData['hidden'] = 1;
                }

                if (isset($pageItem['post_status']) && $pageItem['post_status'] != 'trash') {
                    $existingRecordId = $this->contentRepository->findPageBySlug('/'.$slug, $storageId);
                    if($existingRecordId) {
                        $recordId = $this->contentRepository->updatePageRecord($pageData, $existingRecordId);
                        $updatedRecords++;
                    } else {
                        $recordId = $this->contentRepository->createPageRecord($pageData);
                        $this->logger->error($recordId, $pageData);
                        $success++;
                    }
                    
                    if($dockType === 'blog' && isset($pageItem['author.user_email'])) {
                        $this->manageAuthorInformation($dockType, $recordId, $pageItem, $storageId);
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
                        if($dockType === 'blog') {
                            $categories = $this->insertCategories($categories, $storageId, $recordId, 'pages', 100);
                        } else {
                            $categories = $this->insertCategories($categories, $storageId, $recordId, 'pages', 1);
                        }
                    }

                    if($dockType === 'blog') {
                        if(isset($pageItem['tax_post_tag.name']) && !empty($pageItem['tax_post_tag.name'])) {
                            $tagsList = GeneralUtility::trimExplode(',', $pageItem['tax_post_tag.name']);
                            $this->contentRepository->updateBlogsTagsCounts($recordId, count($tagsList));
                            $this->manageTagsForBlogs($recordId, $tagsList, $storageId);
                        }

                        if(isset($pageItem['image.url']) && !empty($pageItem['image.url'])) {
                            $this->manageFeaturedImages(
                                $recordId,
                                $pageItem['image.url'],
                                'pages',
                                'featured_image',
                                $beUserId);
                        }
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
    public function processPostContentHtml(array $data): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('AutoFormat.RemoveEmpty', true); // remove empty tag pairs
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true); // remove empty, even if it contains an &nbsp;
        $config->set('AutoFormat.AutoParagraph', false); // remove empty tag pairs
        $config->getHTMLDefinition(true)->addAttribute('img', 'data-htmlarea-file-uid', 'Number');
        $config->getHTMLDefinition(true)->addAttribute('img', 'data-htmlarea-file-table', 'CDATA');
        $config->getHTMLDefinition(true)->addAttribute('img', 'data-title-override', 'CDATA');
        $config->getHTMLDefinition(true)->addAttribute('img', 'data-alt-override', 'CDATA');
        $purifier = new HTMLPurifier($config);
        $htmlString = $purifier->purify(trim($data['post_content']));
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        
        // Create a DOMDocument object
        $dom = new DOMDocument();
        try {
            $dom->loadHTML($htmlString);
            // Get all image tags in the HTML
            $imageTags = $dom->getElementsByTagName('img');

            // Loop through each image tag
            foreach ($imageTags as $img) {
                // Get the value of the src attribute
                $src = $img->getAttribute('src');
                if($src) {
                    $fileName = basename($src);
                    $extConfiguration = $this->extensionConfiguration->get('ns_wp_migration');
                    $folder = $extConfiguration['storagePath'];
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
                    $properties = $fileObject->getProperties();

                    // Get the accessible URL of the file
                    $accessibleUrl = $fileObject->getPublicUrl();
                    $img->setAttribute('src', $accessibleUrl);
                    $img->setAttribute('data-htmlarea-file-uid', $properties['uid']);
                    $img->setAttribute('data-htmlarea-file-table', 'sys_file');
                    $img->setAttribute('width', 930);
                    $img->setAttribute('height', 523);
                    $img->setAttribute('title', $properties['name']);
                    $img->setAttribute('alt', $properties['name']);
                    $img->setAttribute('data-title-override', 'true');
                    $img->setAttribute('data-alt-override', 'true');
                }
            }
            
            // Get the modified HTML content
            $htmlString = $dom->saveHTML();
            $htmlString = $purifier->purify($htmlString);
            return $htmlString;
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage(),$data);
            return $htmlString;
        }
            

    }

    /**
     * action Log Manager
     *
     * @return ResponseInterface
     */
    public function logManagerAction() : ResponseInterface
    {
        $data = $this->logManageRepository->getAllLogs();
        $assign = [
            'action' => 'logManager',
            'constant' => $this->constants,
            'loglist' => $data,
            'version' => 11
        ];
        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
            $this->view->assignMultiple($assign);
            return $this->htmlResponse();
        } else {
            $assign['version'] = 12;
            $view = $this->initializeModuleTemplate($this->request);
            $view->assignMultiple($assign);
            return $view->renderResponse();
        }
    }

    /**
     * Insert Categories
     * @param array $categories
     * @param int $storageId
     * @param  int  $recordId
     * @param string $tableName
     * @param int $categoryType
     * @return array
     */
    private function insertCategories(
        array $categories,
        int $storageId,
        int $recordId,
        string $tableName,
        int $categoryType): array
    {
        $categoriesLists = [];
        if ($categories) {
            $categoriesID = '';
            foreach ($categories as $value) {
                $categoriesID = $this->categoryRepository->checkIsExist($value, $storageId);
                $categoriesLists[] = $categoriesID;
                if(empty($categoriesID)) {
                    $slug = str_replace(" ", "-", strtolower($value));
                    $item = ['pid' => $storageId,
                            'hidden' => '0',
                            'title' => $value,
                            'record_type' => $categoryType ? $categoryType: '1',
                            'slug' => $slug
                            ];
                    
                    $newCategory = $this->categoryRepository->insertCategory($item, $recordId, $tableName);
                    $categoriesLists[] = $newCategory;
                } else {
                    $this->categoryRepository->mapcategories($categoriesID, $recordId, $tableName);
                    $categoriesLists[] = $categoriesID;
                }
            }
        }
        return $categoriesLists;
    }

    /**
     * Create author for post types
     * @param string $record_type
     * @param int $record_id
     * @param array $data
     * @param int $storageId
     * @return bool
     */
    private function manageAuthorInformation(
        string $record_type,
        int $record_id,
        array $data,
        int $storageId): bool {
        if($record_type === 'blog') {
            if (isset($data['author.user_email']) && !empty($data['author.user_email'])) {
                $author = $this->contentRepository->findAuthorByEmail($data['author.user_email'], $storageId);
                if($author) {
                    $mapingData = ['uid_local' => $record_id, 'uid_foreign'=> $author];
                    $this->contentRepository->assignAuthorToBlogs($mapingData);
                } else {
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
                $this->contentRepository->updateBlogAuthor($record_id);
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
        return true;
    }

    /**
     * Manage Tags for News and Blogs
     * @param int $recordId
     * @param array $tagList
     * @param int $storageId
     * @return array
     */
    private function manageTagsForNews(int $recordId, array $tagList, int $storageId): array {
        $tagItemList = [];
        $tagItem = [];
        if ($tagList) {
            foreach ($tagList as $value) {
                $slug = str_replace(" ", "-", strtolower($value));
                $tagItem = $this->categoryRepository->checkIsTagExist($slug, $storageId);
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
                    $tagItemList[] = $tagItem;
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
     * @return array
     */
    private function manageTagsForBlogs(int $recordId, array $tagList, int $storageId): array {
        $tagItemList = [];
        if ($tagList) {
            foreach ($tagList as $value) {
                $tagName = trim($value);
                $tagItem = $this->contentRepository->checkIsTagExist($tagName, $storageId);
                if(empty($tagItem)) {
                    $tagItem = GeneralUtility::makeInstance(BlogTag::class);
                    $tagItem->setTitle($tagName);
                    $tagItem->setPid($storageId);
                    $this->blogTagRepository->add($tagItem);
                    $this->persistenceManager->persistAll();
                    $tagId = $tagItem->getUid();
                    $this->contentRepository->mapTagItems($recordId, $tagId);
                } else {
                    $this->contentRepository->mapTagItems($recordId, $tagItem);
                    $tagItemList[] = $tagItem;
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
     * @param int $beUserId
     * @return int
     */
    private function manageFeaturedImages(int $recordId, string $image, string $table, string $field, int $beUserId): int
    {
        $url = $image;
        $fileName = basename($url);
        $extConfiguration = $this->extensionConfiguration->get('ns_wp_migration');
        $folder = $extConfiguration['newsStoragePath'];
        $folderName = 'news';

        if($table === 'pages') {
            $folder = $extConfiguration['blogStoragePath'];
            $folderName = 'blog';
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
        $this->contentRepository->changeTypeforImage($featureImageId);

        $imageData = ['pid' => 0,
                    'deleted' => '0',
                    'uid_local' => $featureImageId,
                    'uid_foreign' => $recordId,
                    'tablenames' => $table,
                    'fieldname' => $field];

        if ((GeneralUtility::makeInstance(Typo3Version::class))->getMajorVersion() < 12) {
            $imageData['cruser_id'] = $beUserId;
            $imageData['table_local'] = 'sys_file';
        }
        return $this->contentRepository->refSystemFile($recordId, $imageData);
    }

    /**
     * Generates the action menu
     *
     * @param ServerRequestInterface $request
     * @return ModuleTemplate
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        return $this->moduleTemplateFactory->create($request);
    }

    /**
     * Get the sample file for downloadings
     */
    protected function downloadSampleAction() {
        $file = ExtensionManagementUtility::extPath('ns_wp_migration') . 'Resources/Public/sample.csv';
        if (file_exists($file)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="sample.csv"');
            header('Content-Length: ' . filesize(GeneralUtility::getFileAbsFileName($file)));
            // Read the file and output its contents
            readfile(GeneralUtility::getFileAbsFileName($file));
            exit;
        }
    }

}
