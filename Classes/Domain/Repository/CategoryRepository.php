<?php
namespace  NITSAN\NsWpMigration\Domain\Repository;

use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
class CategoryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @param string $category
     * @param int $storageId
     */
    public function checkIsExist($category, $storageId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
        $category = $queryBuilder
            ->select('uid')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($category)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storageId, \PDO::PARAM_INT))
                )
            ->executeQuery()
            ->fetchOne();
        return $category;
    }

    /**
     * @param array $item
     * @param int $recordId
     * @param string $recordType
     * @return int
     */
    public function insertCategory($item, $recordId, $recordType): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
        $queryBuilder
            ->insert('sys_category')
            ->values($item)
            ->executeStatement();

        $lastCatUid = $queryBuilder->getConnection()->lastInsertId();
        $queryCatBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('sys_category_record_mm');
        $catInsertData = ['uid_local' => $lastCatUid,
            'uid_foreign' => $recordId,
            'tablenames' => $recordType,
            'fieldname' => 'categories'
        ];

        return $queryCatBuilder
            ->insert('sys_category_record_mm')
            ->values($catInsertData)
            ->executeStatement();
    }

    /**
     * Refrance the categories in the database
     * @return int
     */
    public function mapcategories($categoriesID,$recordId, $recordType ): int
    {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('sys_category_record_mm');
        $existingRecord = $queryBuilder
        ->select('*')
        ->from('sys_category_record_mm')
        ->where(
            $queryBuilder->expr()->eq('uid_local', $categoriesID),
            $queryBuilder->expr()->eq('uid_foreign', $recordId)
        )
        ->executeQuery()
        ->fetch();
        
        if ($existingRecord) {
            return (int)$existingRecord['uid_local'];
        }

        $catInsertData = ['uid_local' => $categoriesID,
            'uid_foreign' => $recordId,
            'tablenames' => $recordType,
            'fieldname' => 'categories'
        ];
        
        $queryCatBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('sys_category_record_mm');
        
        return $queryCatBuilder
            ->insert('sys_category_record_mm')
            ->values($catInsertData)
            ->executeStatement();
    }

    /**
     * @param int $newsId
     * @param int $counts
     * @return int
     */
    public function updateNewsCategoriesCounts($newsId, $counts): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_news_domain_model_news');
        return $queryBuilder
            ->update('tx_news_domain_model_news')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT))
            )
            ->set('categories', $counts)
            ->executeStatement();
    }

    /**
     * @param int $blogId
     * @param int $counts
     * @return int
     */
    public function updateBlogCategoriesCounts($blogId, $counts): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->update('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($blogId, \PDO::PARAM_INT))
            )
            ->set('categories', $counts)
            ->executeStatement();
    }

    /**
     * @param int $newsId
     * @param int $counts
     * @return int
     */
    public function updateNewsTagsCounts($newsId, $counts): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_news_domain_model_news');
        return $queryBuilder
            ->update('tx_news_domain_model_news')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT))
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
        ->getQueryBuilderForTable('tx_news_domain_model_tag');
        return $queryBuilder
            ->select('uid')
            ->from('tx_news_domain_model_tag')
            ->where(
                $queryBuilder->expr()->eq('slug', $queryBuilder->createNamedParameter($slug)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storageId, \PDO::PARAM_INT))
                )
            ->executeQuery()
            ->fetchOne();
    }
    
    /**
     * @param int $newsId
     * @param int $tagId
     * @return int
     */
    public function mapTagItems($newsId, $tagId): int
    {
        // Extract UID if $tagId is an object
        if (is_object($tagId) && method_exists($tagId, 'getUid')) {
            $tagId = $tagId->getUid();
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_news_domain_model_news_tag_mm');
        $existingRecord = $queryBuilder
        ->select('*')
        ->from('tx_news_domain_model_news_tag_mm')
        ->where(
            $queryBuilder->expr()->eq('uid_local', $newsId),
            $queryBuilder->expr()->eq('uid_foreign', $tagId)
        )
        ->executeQuery()
        ->fetch();
        
        if ($existingRecord) {
            return $existingRecord['uid_local'];
        }

        $tagItems = ['uid_local' => $newsId,
            'uid_foreign' => $tagId
        ];

        $queryCatBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_news_domain_model_news_tag_mm');
    
        return $queryCatBuilder
            ->insert('tx_news_domain_model_news_tag_mm')
            ->values($tagItems)
            ->executeStatement();
    }
}
