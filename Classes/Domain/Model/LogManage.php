<?php
namespace NITSAN\NsWpMigration\Domain\Model;

/***
 *
 * This file is part of the "[NITSAN] Ns Wp Migration" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 T3: Milan <sanjay@nitsan.in>, NITSAN Technologies Pvt Ltd
 *
 ***/

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;

/**
 * LogManage
 */
class LogManage extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * numberOfRecords
     *
     * @var int
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $numberOfRecords = 0;

    /**
     * totalSuccess
     *
     * @var int
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $totalSuccess = 0;

    /**
     * totalFails
     *
     * @var int
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $totalFails = 0;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Beuser\Domain\Model\BackendUser>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $addedBy = null;
    protected $totalUpdate = 0;
    protected $recordsLog = '';

    public function __construct()
    {
        $this->addedBy = new ObjectStorage();
    }
    /**
     * createdDate
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected \DateTime $createdDate;

    /**
     * Returns the numberOfRecords
     *
     * @return int $numberOfRecords
     */
    public function getNumberOfRecords()
    {
        return $this->numberOfRecords;
    }

    /**
     * Sets the numberOfRecords
     *
     * @param int $numberOfRecords
     * @return void
     */
    public function setNumberOfRecords($numberOfRecords)
    {
        $this->numberOfRecords = $numberOfRecords;
    }

    /**
     * Returns the totalSuccess
     *
     * @return int $totalSuccess
     */
    public function getTotalSuccess()
    {
        return $this->totalSuccess;
    }

    /**
     * Sets the totalSuccess
     *
     * @param int $totalSuccess
     * @return void
     */
    public function setTotalSuccess($totalSuccess)
    {
        $this->totalSuccess = $totalSuccess;
    }

    /**
     * Returns the totalFails
     *
     * @return int $totalFails
     */
    public function getTotalFails()
    {
        return $this->totalFails;
    }

    /**
     * Sets the totalFails
     *
     * @param int $totalFails
     * @return void
     */
    public function setTotalFails($totalFails)
    {
        $this->totalFails = $totalFails;
    }

    /**
     * Returns the totalUpdate
     *
     * @return int $totalUpdate
     */
    public function getTotalUpdate()
    {
        return $this->totalUpdate;
    }

    /**
     * Sets the totalUpdate
     *
     * @param int $totalUpdate
     * @return void
     */
    public function setTotalUpdate($totalUpdate)
    {
        $this->totalUpdate = $totalUpdate;
    }

    /**
     * Returns the addedBy
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Beuser\Domain\Model\BackendUser> addedBy
     */
    public function getAddedBy()
    {
        return $this->addedBy;
    }

    /**
     * Sets the addedBy
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Beuser\Domain\Model\BackendUser> $addedBy
     * @return void
     */
    public function setAddedBy(BackendUser $addedBy)
    {
        $this->addedBy = $addedBy;
    }

    /**
     * Returns the createdDate
     */
    public function getCreatedDate(): \DateTime
    {
        return $this->createdDate;
    }

    /**
     * Sets the createdDate
     *
     * @param \Datetime $createdDate
     */
    public function setCreatedDate(\DateTime $createdDate): self
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    /**
     * Returns the recordsLog
     *
     * @return string $recordsLog
     */
    public function getRecordsLog()
    {
        return $this->recordsLog;
    }

    /**
     * Sets the recordsLog
     *
     * @param string $recordsLog
     * @return void
     */
    public function setRecordsLog($recordsLog)
    {
        $this->recordsLog = $recordsLog;
    }
}
