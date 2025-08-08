<?php

namespace NITSAN\NsWpMigration\Domain\Model;



use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * LogManage
 */
class LogManage extends AbstractEntity
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
     * @var ObjectStorage<BackendUser>
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
    public function getNumberOfRecords(): int
    {
        return $this->numberOfRecords;
    }

    /**
     * Sets the numberOfRecords
     *
     * @param int $numberOfRecords
     * @return void
     */
    public function setNumberOfRecords($numberOfRecords): void
    {
        $this->numberOfRecords = $numberOfRecords;
    }

    /**
     * Returns the totalSuccess
     *
     * @return int $totalSuccess
     */
    public function getTotalSuccess(): int
    {
        return $this->totalSuccess;
    }

    /**
     * Sets the totalSuccess
     *
     * @param int $totalSuccess
     * @return void
     */
    public function setTotalSuccess($totalSuccess): void
    {
        $this->totalSuccess = $totalSuccess;
    }

    /**
     * Returns the totalFails
     *
     * @return int $totalFails
     */
    public function getTotalFails(): int
    {
        return $this->totalFails;
    }

    /**
     * Sets the totalFails
     *
     * @param int $totalFails
     * @return void
     */
    public function setTotalFails($totalFails): void
    {
        $this->totalFails = $totalFails;
    }

    /**
     * Returns the totalUpdate
     *
     * @return int $totalUpdate
     */
    public function getTotalUpdate(): int
    {
        return $this->totalUpdate;
    }

    /**
     * Sets the totalUpdate
     *
     * @param int $totalUpdate
     * @return void
     */
    public function setTotalUpdate($totalUpdate): void
    {
        $this->totalUpdate = $totalUpdate;
    }

    /**
     * Returns the addedBy
     *
     * @return ObjectStorage<BackendUser> addedBy
     */
    public function getAddedBy(): ObjectStorage
    {
        return $this->addedBy;
    }

    /**
     * Sets the addedBy
     *
     * @param ObjectStorage<BackendUser> $addedBy
     * @return void
     */
    public function setAddedBy(BackendUser $addedBy): void
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
    public function getRecordsLog(): string
    {
        return $this->recordsLog;
    }

    /**
     * Sets the recordsLog
     *
     * @param string $recordsLog
     * @return void
     */
    public function setRecordsLog($recordsLog): void
    {
        $this->recordsLog = $recordsLog;
    }
}
