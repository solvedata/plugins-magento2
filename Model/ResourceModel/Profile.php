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
        // Disregard the website ID when searching for a profile's ID.
        // This is because the same profile can exist across multiple stores and
        //  storing the website ID is unnecessary.

        $connection = $this->getConnection();
        $select = $connection
            ->select()
            ->from($this->getMainTable())
            ->where('email = ?', $email)
            ->limit(1, 0); // Use any arbitrary profile ID for the email address

        return $connection->fetchRow($select);
    }

    /**
     * Get profile sid by email
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return string|null
     *
     * @throws LocalizedException
     */
    public function getSIdByEmail(string $email, int $websiteId): ?string
    {
        $profile = $this->getByEmail($email, $websiteId);
        if (empty($profile)) {
            return null;
        }

        return $profile['sid'];
    }
}
