<?php
declare(strict_types = 1);

/*
 * This file is part of the "[NITSAN] Ns Wp Migration" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace NITSAN\NsWpMigration\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

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
}