<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;
    protected $jsonHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->jsonHelper = $jsonHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $context = [
            "quoteId" => $quote->getId()
        ];

        $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($context)
        );
    }
}
