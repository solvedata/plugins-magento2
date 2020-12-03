<?php

namespace SolveData\Events\Model\Adminhtml\Source\Event;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use SolveData\Events\Model\Event;

class Store implements OptionSourceInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GroupInterface[]
     */
    protected $groups;

    /**
     * @var WebsiteInterface[]
     */
    protected $websites;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Get store group by id
     *
     * @param $id
     *
     * @return GroupInterface
     */
    protected function getStoreGroupById($id)
    {
        if (empty($websites[$id])) {
            $websites[$id] = $this->storeManager->getGroup($id);
        }

        return $websites[$id];
    }

    /**
     * Get website by id
     *
     * @param $id
     *
     * @return WebsiteInterface
     *
     * @throws LocalizedException
     */
    protected function getWebsiteById($id)
    {
        if (empty($websites[$id])) {
            $websites[$id] = $this->storeManager->getWebsite($id);
        }

        return $websites[$id];
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     *
     * @throws LocalizedException
     */
    public function toOptionArray()
    {
        $options = [];

        $withDefaultStore = true;
        $stores = $this->storeManager->getStores($withDefaultStore);

        foreach ($stores as $store) {
            $options[] = [
                'value' => $store->getId(),
                'label' => sprintf(
                    '%s - %s - %s',
                    $this->getWebsiteById($store->getWebsiteId())->getName(),
                    $this->getStoreGroupById($store->getStoreGroupId())->getName(),
                    $store->getName()
                ),
            ];
        }

        return $options;
    }
}
