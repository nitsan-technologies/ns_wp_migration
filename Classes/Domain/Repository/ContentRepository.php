<?php

namespace  NITSAN\NsWpMigration\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;


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
}
