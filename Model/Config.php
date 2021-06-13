<?php

declare(strict_types=1);

namespace SolveData\Events\Model;

use DateInterval;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    const XML_PATH_IS_ENABLED                       = 'solvedata_events/general/enabled';
    const XML_PATH_ENABLED_EVENTS                   = 'solvedata_events/general/enabled_events';
    const XML_PATH_ENABLED_ANONYMOUS_CART_EVENTS    = 'solvedata_events/general/enabled_anonymous_cart_events';
    const XML_PATH_ENABLED_CONVERT_HISTORICAL_CARTS = 'solvedata_events/general/enabled_convert_historical_carts';
    const XML_PATH_DEBUG                            = 'solvedata_events/general/debug';
    const XML_PATH_CRON_BATCH_SIZE                  = 'solvedata_events/general/cron_batch_size';
    const XML_PATH_TRANSACTION_BATCH_SIZE           = 'solvedata_events/general/transaction_batch_size';
    const XML_PATH_SENTRY_DSN                       = 'solvedata_events/general/sentry_dsn';
    const XML_PATH_EVENT_RETENTION_PERIOD           = 'solvedata_events/general/event_retention_period';
    const XML_PATH_API_URL                          = 'solvedata_events/api/url';
    const XML_PATH_API_KEY                          = 'solvedata_events/api/key';
    const XML_PATH_PASSWORD                         = 'solvedata_events/api/password';
    const XML_PATH_MAX_ATTEMPT_COUNT                = 'solvedata_events/api/max_attempt_count';
    const XML_PATH_ATTEMPT_INTERVAL                 = 'solvedata_events/api/attempt_interval';
    const XML_PATH_SDK_IS_ENABLED                   = 'solvedata_events/sdk/enabled';
    const XML_PATH_SDK_INIT_CODE                    = 'solvedata_events/sdk/init_code';
    const XML_PATH_WEBHOOK_FORWARDING_IS_ENABLED    = 'solvedata_events/webhook_forwarding/enabled';
    const XML_PATH_WEBHOOK_FORWARDING_URL           = 'solvedata_events/webhook_forwarding/url';
    const XML_PATH_WEBHOOK_DISABLE_GRAPHQL          = 'solvedata_events/webhook_forwarding/disable_graphql';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Module is enabled
     *
     * @param integer|null $store
     *
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_IS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check event is enabled
     *
     * @param $name
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     */
    public function isEnabledEvent(string $name): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return in_array(
            $name,
            $this->getEnabledEvents($this->storeManager->getStore()->getId())
        );
    }

    /**
     * Get enabled events
     *
     * @param integer|null $store
     *
     * @return array
     */
    public function getEnabledEvents($store = null): array
    {
        $enabledEvents = $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return !empty($enabledEvents) ? explode(',', $enabledEvents) : [];
    }

    /**
     * Are anonymous cart events enabled?
     *
     * @param integer|null $store
     *
     * @return bool
     */
    public function isAnonymousCartsEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED_ANONYMOUS_CART_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Convert carts from historical orders?
     *
     * @param integer|null $store
     *
     * @return bool
     */
    public function convertHistoricalCarts($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED_CONVERT_HISTORICAL_CARTS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get debug flag
     *
     * @param integer|null $store
     *
     * @return bool
     */
    public function getDebug($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DEBUG,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get cron batch size
     *
     * @param integer|null $store
     *
     * @return int
     */
    public function getCronBatchSize($store = null): int
    {
        try {
            return (int)$this->scopeConfig->getValue(
                self::XML_PATH_CRON_BATCH_SIZE,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        } catch (\Throwable $t) {
            return 100;
        }
    }

    /**
     * Get transaction batch size
     *
     * @param integer|null $store
     *
     * @return int
     */
    public function getTransactionBatchSize($store = null): int
    {
        try {
            return (int)$this->scopeConfig->getValue(
                self::XML_PATH_TRANSACTION_BATCH_SIZE,
                ScopeInterface::SCOPE_STORE,
                $store
            );
        } catch (\Throwable $t) {
            return 10;
        }
    }

    public function getSentryDsn($store = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SENTRY_DSN,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }

    public function getEventRetention($store = null): ?DateInterval
    {
        $period = $this->scopeConfig->getValue(
            self::XML_PATH_EVENT_RETENTION_PERIOD,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        if (empty($period)) {
            return null;
        }

        try {
            return DateInterval::createFromDateString($period);
        } catch (\Throwable $t) {
            return null;
        }
    }

    /**
     * Get API url
     *
     * @param null $store
     *
     * @return string
     */
    public function getAPIUrl($store = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }

    /**
     * Get API key
     *
     * @param null $store
     *
     * @return string
     */
    public function getAPIKey($store = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }

    /**
     * Get API password
     *
     * @param null $store
     *
     * @return string
     */
    public function getPassword($store = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }

    /**
     * Get max attempt count
     *
     * @param null $store
     *
     * @return int
     */
    public function getMaxAttemptCount($store = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_ATTEMPT_COUNT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get attempt interval in minutes
     *
     * @param null $store
     *
     * @return int
     */
    public function getAttemptInterval($store = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_ATTEMPT_INTERVAL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Web SDK is enabled
     *
     * @param null $store
     *
     * @return bool
     */
    public function isEnabledSDK($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_SDK_IS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Web SDK initialization code
     *
     * @param null $store
     *
     * @return string
     */
    public function getSDKInitCode($store = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SDK_INIT_CODE,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }

    public function isWebhookForwardingEnabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_WEBHOOK_FORWARDING_IS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    public function getWebhookForwardingUrl($store = null): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_WEBHOOK_FORWARDING_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?? '';
    }

    public function isGraphQLDisabled($store = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_WEBHOOK_DISABLE_GRAPHQL,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
