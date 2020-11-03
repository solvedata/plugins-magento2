<?php

declare(strict_types=1);

namespace SolveData\Events\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use SolveData\Events\Model\Logger;

class Event
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $stores = [];

    /**
     * @var array
     */
    protected $websites = [];

    /**
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * Get store
     *
     * @param null|string|int $storeId
     *
     * @return StoreInterface
     *
     * @throws NoSuchEntityException
     */
    public function getStore($storeId = null): StoreInterface
    {
        if (empty($this->stores[$storeId])) {
            $this->stores[$storeId] = $this->storeManager->getStore($storeId);
        }

        return $this->stores[$storeId];
    }

    /**
     * Get store
     *
     * @param null|string|int $websiteId
     *
     * @return WebsiteInterface
     *
     * @throws LocalizedException
     */
    public function getWebsite($websiteId = null): WebsiteInterface
    {
        if (empty($this->websites[$websiteId])) {
            $this->websites[$websiteId] = $this->storeManager->getWebsite($websiteId);
        }

        return $this->websites[$websiteId];
    }

    /**
     * Get area data for payload
     *
     * @param null|string|int $storeId
     *
     * @return array
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getAreaPayloadData($storeId = null): array
    {
        $store = $this->getStore($storeId);
        $website = $this->getWebsite($store->getWebsiteId());

        return [
            'store'   => $store,
            'website' => $website,
        ];
    }
}
