<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Context;

use SolveData\Events\Model\Logger;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;
    protected $jsonHelper;
    protected $storeManager;
    protected $logger;
    protected $websiteRepository;
    protected $quoteIdToMaskedQuoteId;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\WebsiteRepository $websiteRepository,
        \Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        Logger $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->jsonHelper = $jsonHelper;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->websiteRepository = $websiteRepository;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;

        parent::__construct($context);
    }

    public function execute()
    {
        $quoteId = $this->checkoutSession->getQuote()->getId();
        $quoteIdMasked = empty($quoteId) ? null : $this->getQuoteMaskId((int) $quoteId);

        $context = [
            "quoteId" => $quoteId,
            "quoteIdMasked" => $quoteIdMasked,
            "store" => $this->getStore()
        ];

        $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($context)
        );
    }

    private function getStore()
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $website = $this->websiteRepository->getById($websiteId);
        return $website->getCode();
    }

    private function getQuoteMaskId($quoteId)
    {
        return $this->quoteIdToMaskedQuoteId->execute($quoteId);
    }
}
