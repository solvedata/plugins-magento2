<?php

declare(strict_types=1);

namespace SolveData\Events\Block\WebSDK\Event;

use Magento\Backend\Block\Template;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Helper\Customer as CustomerHelper;
use SolveData\Events\Model\Config;

class Destroy extends Template
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param CustomerHelper $customerHelper
     * @param CustomerSession $customerSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        CustomerHelper $customerHelper,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->config = $config;
        $this->customerHelper = $customerHelper;
        $this->customerSession = $customerSession;

        parent::__construct($context, $data);
    }

    /**
     * Web SDK is enabled and customer is logged in
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     */
    public function isAllowed()
    {
        return $this->config->isEnabledSDK($this->_storeManager->getStore()->getId())
            && !$this->customerSession->isLoggedIn();
    }
}
