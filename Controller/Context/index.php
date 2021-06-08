<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Context;

use SolveData\Events\Model\Logger;

class Index extends \Magento\Framework\App\Action\Action
{
    private $checkoutSession;
    private $jsonHelper;
    private $storeManager;
    private $websiteRepository;
    private $quoteIdToMaskedQuoteId;
    private $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\WebsiteRepository $websiteRepository
     * @param \Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param Logger
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
        $this->websiteRepository = $websiteRepository;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $quoteId = $this->checkoutSession->getQuote()->getId();
        $quoteIdMasked = $this->getQuoteMaskId($quoteId);

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
        $website = $this->storeManager->getWebsite();
        return $website->getCode();
    }

    private function getQuoteMaskId($quoteIdString)
    {
        try {
            $quoteId = (int) $quoteIdString;
            if (empty($quoteId)) return null;

            // Note this returns "" if the mask id doesn't exist (e.g. when
            // user is signed in). Grr Magento
            return $this->quoteIdToMaskedQuoteId->execute($quoteId);
        } catch (\Throwable $t) {
            $this->logger->error($t);
            return null;
        }
    }
}
