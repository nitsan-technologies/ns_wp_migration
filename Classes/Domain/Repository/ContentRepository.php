<?php
namespace  NITSAN\NsWpMigration\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->insert('pages')->values($pageItems)->executeStatement();
        $id = $queryBuilder->getConnection()->lastInsertId();
        return (int)$id;
    }

    /**
     * @param string $email
     */
    public function findAuthorByEmail($email) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_blog_domain_model_author');
        $author = $queryBuilder
            ->select('uid')
            ->from('tx_blog_domain_model_author')
            ->where(
                $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($email))
                )
            ->executeQuery()
            ->fetchOne();
        return $author;
    }

    /**
     * @param string $email
     */
    public function findAuthorByNewsEmail($email) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_mdnewsauthor_domain_model_newsauthor');
        $author = $queryBuilder
            ->select('uid')
            ->from('tx_mdnewsauthor_domain_model_newsauthor')
            ->where(
                $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter($email))
                )
            ->executeQuery()
            ->fetchOne();
        return $author;
    }
    
    public function findNewsBySlug($slug) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_news_domain_model_news');
        $news = $queryBuilder
            ->select('uid')
            ->from('tx_news_domain_model_news')
            ->where(
                $queryBuilder->expr()->eq('path_segment', $queryBuilder->createNamedParameter($slug))
                )
            ->executeQuery()
            ->fetchOne();
        return $news;
    }

    public function findPageBySlug($slug) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $pages = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('slug', $queryBuilder->createNamedParameter($slug))
                )
            ->executeQuery()
            ->fetchOne();
        return $pages;
    }

    public function updatePageRecord($data, $recordId) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $affectedRows = $queryBuilder
            ->update('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($recordId, \PDO::PARAM_INT))
            )
            ->set('title', $data['title'])
            ->set('hidden', $data['hidden'])
            ->set('tstamp', $data['tstamp'])
            ->set('crdate', $data['crdate'])
            ->set('pid', $data['pid'])
            ->set('slug', $data['slug'])
            ->set('doktype', $data['doktype'])
            ->set('publish_date', $data['publish_date'])
            ->set('crdate_month', $data['crdate_month'])
            ->set('crdate_year', $data['crdate_year'])
            ->executeStatement();
        return $affectedRows;
    }

    public function assignAuthorToNews($data) {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_mdnewsauthor_news_newsauthor_mm');
        $queryBuilder->insert('tx_mdnewsauthor_news_newsauthor_mm')->values($data)->executeStatement();
        return true;
    }
}