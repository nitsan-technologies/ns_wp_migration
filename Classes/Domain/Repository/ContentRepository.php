<?php

namespace  NITSAN\NsWpMigration\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use Doctrine\DBAL\Result;


/***
 *
 * This file is part of the "[NITSAN] wp-migration" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2023 T3: Navdeepsinh Jethwa <sanjay@nitsan.in>, NITSAN Technologies Pvt Ltd
 *
 ***/

/**
 * The repository for NsWpMigration
 */
class ContentRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @param array $contentElement
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
     * @return mixed
     */
    public function createPageRecord($pageItems): mixed
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
            return $dataHandler->errorLog;
        }
        $dataHandler->clear_cacheCmd('pages');
        return $dataHandler->substNEWwithIDs[$randomString];
    }

    /**
     * Remove and create a new pages
     * @return int
     */
    public function updatePageRecord($data, $recordId): int {
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
     * @return int
     */
    public function findAuthorByEmail($email, $pid): int
    {
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
        return (int)$author;
    }

    /**
     * @param string $email
     * @param int $pid
     * @return string
     */
    public function findAuthorByNewsEmail($email, $pid): string
    {
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
    /**
     * @return string
     */
    public function findNewsBySlug($slug): string
    {
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

    /**
     * @return string
     */
    public function findPageBySlug($slug, $storageId): string
    {
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

    /**
     * @return bool
     */
    public function assignAuthorToNews($data): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_mdnewsauthor_news_newsauthor_mm');
        $existingRecord = $queryBuilder
        ->select('*')
        ->from('tx_mdnewsauthor_news_newsauthor_mm')
        ->where(
            $queryBuilder->expr()->eq('uid_local', $data['uid_local']),
            $queryBuilder->expr()->eq('uid_foreign', $data['uid_foreign'])
        )
        ->executeQuery()
        ->fetch();
        
        if ($existingRecord) {
            return false;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_mdnewsauthor_news_newsauthor_mm');
        $queryBuilder->insert('tx_mdnewsauthor_news_newsauthor_mm')->values($data)->executeStatement();
        return true;
    }

    /**
     * @return bool
     */
    public function assignAuthorToBlogs($data): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_post_author_mm');
        $existingRecord = $queryBuilder
            ->select('*')
            ->from('tx_blog_post_author_mm')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local', $queryBuilder->createNamedParameter($data['uid_local'], \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq(
                    'uid_foreign', $queryBuilder->createNamedParameter($data['uid_foreign'], \PDO::PARAM_INT))
        )
        ->executeQuery()
        ->fetch();
        
        if ($existingRecord) {
            return false;
        }
        
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_blog_post_author_mm');
        $queryBuilder->insert('tx_blog_post_author_mm')->values($data)->executeStatement();
        return true;
    }

    /**
     * @param int $blogId
     * @param int $counts
     * @return int
     */
    public function updateBlogsTagsCounts($blogId, $counts): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->update('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($blogId, \PDO::PARAM_INT))
            )
            ->set('tags', $counts)
            ->executeStatement();
    }

    /**
     * @param string $slug
     * @param int $storageId
     * @return string
     */
    public function checkIsTagExist(string $slug, int $storageId): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_domain_model_tag');
        return $queryBuilder
            ->select('uid')
            ->from('tx_blog_domain_model_tag')
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($slug)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storageId, \PDO::PARAM_INT))
                )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @param int $tagId
     * @return int
     */
    public function mapTagItems($blogId, $tagId): int
    {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_blog_tag_pages_mm');
        $existingRecord = $queryBuilder
        ->select('*')
        ->from('tx_blog_tag_pages_mm')
        ->where(
            $queryBuilder->expr()->eq('uid_local', $blogId),
            $queryBuilder->expr()->eq('uid_foreign', $tagId)
        )
        ->executeQuery()
        ->fetch();
        
        if ($existingRecord) {
            return $existingRecord;
        }

        $tagItems = ['uid_local' => $blogId,
                'uid_foreign' => $tagId
            ];
        $queryTagBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_blog_tag_pages_mm');
        return $queryTagBuilder
                ->insert('tx_blog_tag_pages_mm')
                ->values($tagItems)
                ->executeStatement();
    }

    /**
     * set feature image in system file\
     * @return int
     */
    public function setFeatureImage($imageData): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder
            ->insert('sys_file')
            ->values($imageData)
            ->executeStatement();
        return (int)$queryBuilder->getConnection()->lastInsertId();
    }

    /**
     * Refrenance feature image in system file
     * @return int
     */
    public function refSystemFile($uid, $imageData): int
    {
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
            ->executeStatement();

        return (int)$queryBuilder->getConnection()->lastInsertId();
    }

    /**
     * @return int
     */
    public function changeTypeforImage($uid): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('sys_file');
        return $queryBuilder
            ->update('sys_file')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->set('type', '2')
            ->executeStatement();
    }

    /**
     * @return int
     */
    public function updateBlogAuthor($blogId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->update('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($blogId, \PDO::PARAM_INT))
            )
            ->set('author', '1')
            ->executeStatement();
    }
}
