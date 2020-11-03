<?php

declare(strict_types=1);

namespace SolveData\Events\Block\WebSDK;

use Magento\Backend\Block\Template;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Model\Config;

class InitCode extends Template
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;

        parent::__construct($context, $data);
    }

    /**
     * Web SDK is enabled
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     */
    public function isEnabled()
    {
        return $this->config->isEnabledSDK($this->_storeManager->getStore()->getId());
    }

    /**
     * Get Web SDK initialization code
     *
     * @return string
     *
     * @throws NoSuchEntityException
     */
    public function getInitCode(): string
    {
        return $this->config->getSDKInitCode($this->_storeManager->getStore()->getId());
    }
}
