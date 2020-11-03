<?php

declare(strict_types=1);

namespace SolveData\Events\Model;

use Magento\Framework\Model\AbstractModel;
use SolveData\Events\Model\ResourceModel\Profile as ResourceModel;

/**
 * Class Profile
 * @package SolveData\Events\Model
 *
 * @method string getEmail()
 * @method Profile setEmail(string $value)
 * @method string getSid()
 * @method Profile setSid(string $value)
 * @method string getWebsiteId()
 * @method Profile setWebsiteId(int $value)
 */
class Profile extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Check profile is exist
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return bool
     */
    public function isExistByEmail(string $email, int $websiteId): bool
    {
        $profile = $this->getResource()->getByEmail($email, $websiteId);

        return !empty($profile);
    }

    /**
     * Get profile sid by email
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return string
     */
    public function getSIdByEmail(string $email, int $websiteId): ?string
    {
        return $this->getResource()->getSidByEmail($email, $websiteId);
    }
}
