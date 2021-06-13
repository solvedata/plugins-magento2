<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Quote;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

class QuoteMerge extends EventAbstract
{
    /**
     * Event is allowed
     *
     * @param Observer $observer
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     */
    protected function isAllowed(Observer $observer): bool
    {
        if (!parent::isAllowed($observer)) {
            return false;
        }

        $event = $observer->getEvent();
        
        /** @var Quote $quote */
        $quote = $event->getQuote();
        return !empty($quote->getId());
    }

    /**
     * Get observer data
     *
     * @param Observer $observer
     *
     * @return EventAbstract
     *
     * @throws \Exception
     */
    public function prepareData(Observer $observer): EventAbstract
    {
        $event = $observer->getEvent();
        
        /** @var Quote $quote */
        $quote = $event->getQuote();

        /** @var Quote $source */
        $source = $event->getSource();
        
        // Add final price to payload
        foreach ($quote->getAllVisibleItems() as $item) {
            $item->setData('final_price', $item->getProduct()->getFinalPrice());
        }

        // Add final price to payload
        foreach ($source->getAllVisibleItems() as $item) {
            $item->setData('final_price', $item->getProduct()->getFinalPrice());
        }

        if (!empty($quote->getEntityId())) {
            $this->setAffectedEntityId((int)$quote->getEntityId());
        }

        $this->setPayload([
            'quote'                      => $quote,
            'quoteAllVisibleItems'       => $quote->getAllVisibleItems(),
            'source'                     => $source,
            'sourceQuoteAllVisibleItems' => $source->getAllVisibleItems(),
            'area'                       => $this->eventHelper->getAreaPayloadData($quote->getStoreId()),
        ]);

        return $this;
    }
}
