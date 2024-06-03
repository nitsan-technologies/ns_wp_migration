<?php

declare(strict_types=1);

/*
 * This file is part of the "Wp Migration" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 * (c) 2023 T3: Navdeepsinh Jethwa <sanjay@nitsan.in>, NITSAN Technologies Pvt Ltd
 */

namespace NITSAN\NsWpMigration\Domain\Repository;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Class LogManageRepository.
 */
class LogManageRepository extends Repository
{
    public function initializeObject(): void
    {
        $this->defaultOrderings = [
            'uid' => QueryInterface::ORDER_ASCENDING,
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllLogs(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_nswpmigration_domain_model_logmanage');
        return $queryBuilder
            ->select('*')
            ->from('tx_nswpmigration_domain_model_logmanage')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
            )
            ->orderBy('uid', 'DESC')
            ->executeQuery()->fetchAllAssociative();
    }
}
