<?php

declare(strict_types=1);

namespace SolveData\Events\CustomerData;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

class Cart implements SectionSourceInterface
{
    private $checkoutSession;
    private $storeManager;

    /**
     * @param CheckoutSession $checkoutSession
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function getSectionData()
    {
        $quote = $this->checkoutSession->getQuote();
        $website = $this->storeManager->getWebside();
        return [
            'quoteId' => $quote->getId(),
            'websiteCode' => $website->getCode()
        ];
    }
}
