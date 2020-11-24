<?php

declare(strict_types=1);

namespace SolveData\Events\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Profile extends AbstractDb
{
    const ENTITY_ID = 'id';

    const TABLE_NAME = 'solvedata_profile';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::ENTITY_ID);
    }

    /**
     * Get profile by email
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return array|false
     *
     * @throws LocalizedException
     */
    public function getByEmail(string $email, int $websiteId)
    {
        $connection = $this->getConnection();
        $select = $connection
            ->select()
            ->from($this->getMainTable())
            ->where('email = ?', $email)
            ->where('website_id = ?', $websiteId);

        return $connection->fetchRow($select);
    }

    /**
     * Get profile id by email
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return string|null
     *
     * @throws LocalizedException
     */
    public function getProfileIdByEmail(string $email, int $websiteId): ?string
    {
        $profile = $this->getByEmail($email, $websiteId);
        if (empty($profile)) {
            return null;
        }

        // Note that the `profile_id` is saved in the `sid` column.
        // This is because sid was the old name for profile IDs and we
        //  decided not to add a schema migration just to rename this column.
        return $profile['sid'];
    }
}
