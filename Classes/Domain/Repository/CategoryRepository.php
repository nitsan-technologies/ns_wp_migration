<?php
namespace  NITSAN\NsWpMigration\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
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
     * @param array $category
     * @param int $recordId
     * @param string $recordType
     * @return int $category
     */
    public function insertCategory($item, $recordId, $recordType): int
    {   

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
        $affectedRows = $queryBuilder
            ->insert('sys_category')
            ->values($item)
            ->execute();

        $lastCatUid = $queryBuilder->getConnection()->lastInsertId();
        $queryCatBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category_record_mm');
        $catInsertData = ['uid_local' => $lastCatUid,
            'uid_foreign' => $recordId,
            'tablenames' => $recordType,
            'fieldname' => 'categories'
        ];

        $affectedRows = $queryCatBuilder
            ->insert('sys_category_record_mm')
            ->values($catInsertData)
            ->execute();

        return $affectedRows;
    }

    /**
     * Refrance the categories in the database
     */
    public function mapcategories($categoriesID,$recordId, $recordType ) {
        $catInsertData = ['uid_local' => $categoriesID,
            'uid_foreign' => $recordId,
            'tablenames' => $recordType,
            'fieldname' => 'categories'
        ];
        
        $queryCatBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category_record_mm');
        
        $affectedRows = $queryCatBuilder
            ->insert('sys_category_record_mm')
            ->values($catInsertData)
            ->execute();

        return $affectedRows;
    }

    /**
     * @param int $newsId
     * @param int $counts
     */
    public function updateNewsCategoriesCounts($newsId, $counts) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_news_domain_model_news');
        $affectedRows = $queryBuilder
            ->update('tx_news_domain_model_news')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($newsId, \PDO::PARAM_INT))
            )
            ->set('categories', $counts)
            ->execute();
        return $affectedRows;
    }

    /**
     * @param int $newsId
     * @param int $counts
     */
    public function updateBlogCategoriesCounts($blogId, $counts) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $affectedRows = $queryBuilder
            ->update('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($blogId, \PDO::PARAM_INT))
            )
            ->set('categories', $counts)
            ->execute();
        return $affectedRows;
    }
}
