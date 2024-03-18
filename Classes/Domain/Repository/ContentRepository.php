<?php
namespace  NITSAN\NsWpMigration\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;


/***
 *
 * This file is part of the "[NITSAN] wp-migration" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2023 T3: Navdeep <sanjay@nitsan.in>, NITSAN Technologies Pvt Ltd
 *
 ***/

/**
 * The repository for NsWpMigration
 */
class ContentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @param array $content
     * @return int
     */
    public function insertContnetElements($contentElement): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->insert('tt_content')->values($contentElement)->executeStatement();
        $id = $queryBuilder->getConnection()->lastInsertId();
        return (int)$id;
    }

    /**
     * @param array $pageItems
     * @return int
     */
    public function createPageRecord($pageItems): int
    {
        $isAdmin = $GLOBALS['BE_USER']->user['admin'] ?? 0;
        $randomString = StringUtility::getUniqueId('NEW');
        $newPageData = [
            'pages' => [
                $randomString => $pageItems,
            ],
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($newPageData, []);
        $dataHandler->admin = $isAdmin;
        $dataHandler->process_datamap();
        if ($dataHandler->errorLog) {
            \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($dataHandler->errorLog,__FILE__.''.__LINE__);die;
        }
        $dataHandler->clear_cacheCmd('pages');
        return $dataHandler->substNEWwithIDs[$randomString];
    }


    public function updatePageRecord($data, $recordId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder
            ->delete('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($recordId, \PDO::PARAM_INT))
            )
            ->executeStatement();
        return $this->createPageRecord($data);
    }

    /**
     * @param string $email
     * @param int $pid
     */
    public function findAuthorByEmail($email, $pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_domain_model_author');
        $author = $queryBuilder
            ->select('uid')
            ->from('tx_blog_domain_model_author')
            ->where(
                $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($email)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
                )
            ->executeQuery()
            ->fetchOne();
        return $author;
    }

    /**
     * @param string $email
     * @param int $pid
     */
    public function findAuthorByNewsEmail($email, $pid) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_mdnewsauthor_domain_model_newsauthor');
        return $queryBuilder
            ->select('uid')
            ->from('tx_mdnewsauthor_domain_model_newsauthor')
            ->where(
                $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($email)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
                )
            ->executeQuery()
            ->fetchOne();
    }
    
    public function findNewsBySlug($slug) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_news_domain_model_news');
        return $queryBuilder
            ->select('uid')
            ->from('tx_news_domain_model_news')
            ->where(
                $queryBuilder->expr()->eq('path_segment', $queryBuilder->createNamedParameter($slug))
                )
            ->executeQuery()
            ->fetchOne();
    }

    public function findPageBySlug($slug, $storageId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('slug', $queryBuilder->createNamedParameter($slug)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storageId, \PDO::PARAM_INT))
                )
            ->executeQuery()
            ->fetchOne();
    }

    public function assignAuthorToNews($data) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_mdnewsauthor_news_newsauthor_mm');
        $existingRecord = $queryBuilder
        ->select('*')
        ->from('tx_mdnewsauthor_news_newsauthor_mm')
        ->where(
            $queryBuilder->expr()->eq('uid_local', $data['uid_local']),
            $queryBuilder->expr()->eq('uid_foreign', $data['uid_foreign'])
        )
        ->execute()
        ->fetch();
        
        if ($existingRecord) {
            return 0;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_mdnewsauthor_news_newsauthor_mm');
        $queryBuilder->insert('tx_mdnewsauthor_news_newsauthor_mm')->values($data)->executeStatement();
        return true;
    }

    public function assignAuthorToBlogs($data) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_post_author_mm');
        $existingRecord = $queryBuilder
        ->select('*')
        ->from('tx_blog_post_author_mm')
        ->where(
            $queryBuilder->expr()->eq('uid_local', $data['uid_local']),
            $queryBuilder->expr()->eq('uid_foreign', $data['uid_foreign'])
        )
        ->execute()
        ->fetch();
        
        if ($existingRecord) {
            return 0;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_post_author_mm');
        $queryBuilder->insert('tx_blog_post_author_mm')->values($data)->executeStatement();
        return true;
    }

    /**
     * @param int $blogId
     * @param int $counts
     */
    public function updateBlogsTagsCounts($blogId, $counts) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->update('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($blogId, \PDO::PARAM_INT))
            )
            ->set('tags', $counts)
            ->execute();
    }

    /**
     * @param string $slug
     * @param int $storageId
     */
    public function checkIsTagExist(string $slug, int $storageId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_domain_model_tag');
        return $queryBuilder
            ->select('uid')
            ->from('tx_blog_domain_model_tag')
            ->where(
                $queryBuilder->expr()->eq('slug', $queryBuilder->createNamedParameter($slug))
                )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @param int $tagId
     */
    public function mapTagItems($blogId, $tagId) {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_post_author_mm');
        $existingRecord = $queryBuilder
        ->select('*')
        ->from('tx_blog_post_author_mm')
        ->where(
            $queryBuilder->expr()->eq('uid_local', $blogId),
            $queryBuilder->expr()->eq('uid_foreign', $tagId)
        )
        ->execute()
        ->fetch();
        
        if ($existingRecord) {
            return 0;
        }

        $tagItems = ['uid_local' => $blogId,
            'uid_foreign' => $tagId
        ];
        
        $queryTagBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_tag_pages_mm');
    
        return $queryTagBuilder
            ->insert('tx_blog_tag_pages_mm')
            ->values($tagItems)
            ->execute();
    }

    /**
     * set feature image in system file
     */
    public function setFeatureImage($imageData) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder
            ->insert('sys_file')
            ->values($imageData)
            ->execute();
        return $queryBuilder->getConnection()->lastInsertId();
    }

    /**
     * Refrenance feature image in system file
     */
    public function refSystemFile($uid, $imageData) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('sys_file_reference');

        $queryBuilder
        ->delete('sys_file_reference')
        ->where(
            $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        )
        ->executeStatement();

        $queryBuilder
            ->insert('sys_file_reference')
            ->values($imageData)
            ->execute();

        return $queryBuilder->getConnection()->lastInsertId();
    }
}