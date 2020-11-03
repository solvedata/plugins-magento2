<?php

declare(strict_types=1);

namespace SolveData\Events\Block\WebSDK;

use Magento\Backend\Block\Template;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Model\Config;

abstract class MetaAbstract extends Template
{
    const META_NAME = '';

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
     * Get meta name
     *
     * @return string
     */
    public function getMetaName()
    {
        return static::META_NAME;
    }

    /**
     * Get meta content
     *
     * @return string
     */
    abstract public function getMetaContent(): string;
}
