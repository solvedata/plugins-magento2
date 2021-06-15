<?php

declare(strict_types=1);

namespace SolveData\Events\Helper;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\Processor as ItemProcessor;

class AbandonedCartMerger
{
    private $eventManager;
    private $itemProcessor;

    public function __construct(
        EventManager $eventManager,
        ItemProcessor $itemProcessor
    ) {
        $this->eventManager = $eventManager;
        $this->itemProcessor = $itemProcessor;
    }

    /**
     * Merge an abanadoned cart into the destination quote.
     *
     * The result is the union of the two carts.
     *
     * @param Quote $quote
     * @return $this
     */
    public function merge(Quote $dest, Quote $source)
    {
        $this->eventManager->dispatch(
            'sales_quote_merge_before',
            ['quote' => $dest, 'source' => $source]
        );

        foreach ($source->getAllVisibleItems() as $item) {
            $found = false;
            foreach ($dest->getAllItems() as $quoteItem) {
                if ($quoteItem->compare($item)) {
                    // Customisation: takes the maximum of the two cart's quantities instead of their sum.
                    $mergedQty = max($quoteItem->getQty(), $item->getQty());

                    $quoteItem->setQty($mergedQty);
                    $this->itemProcessor->merge($item, $quoteItem);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $newItem = clone $item;
                $dest->addItem($newItem);
                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $child) {
                        $newChild = clone $child;
                        $newChild->setParentItem($newItem);
                        $dest->addItem($newChild);
                    }
                }
            }
        }

        /**
         * Init shipping and billing address if quote is new
         */
        if (!$dest->getId()) {
            $dest->getShippingAddress();
            $dest->getBillingAddress();
        }

        if ($source->getCouponCode()) {
            $dest->setCouponCode($source->getCouponCode());
        }

        $this->eventManager->dispatch(
            'sales_quote_merge_after',
            ['quote' => $dest, 'source' => $source]
        );

        return $dest;
    }
}
