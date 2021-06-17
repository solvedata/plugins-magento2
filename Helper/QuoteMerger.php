<?php

declare(strict_types=1);

namespace SolveData\Events\Helper;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\Processor as ItemProcessor;

/**
 * Solve's customisation of Magento\Quote\Model\Quote's quote merging that uses
 * the $source cart's quantity for a given item rather than summing $source and
 * $dest item quantities to avoid duplicating items when a cart is reclaimed.
 * If the item is in the $source but not $dest, then the item will be copied to
 * $dest. If the item is in $dest but not $source, then it is untouched.
 *
 * See https://github.com/magento/magento2/blob/2.3.5/app/code/Magento/Quote/Model/Quote.php#L2381
 */
class QuoteMerger
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
                    // Customisation: Set the merge quantity to be $source
                    // cart's quantity. We assume that the $source cart is the
                    // one that's the most recently updated so we should use
                    // that quantity. User would not want to buy double the
                    // number of the items (source quantity + destination
                    // quantity) nor would they want the source quantity to be
                    // overridem by the destination quantity if source quantity
                    // was set more recently.
                    $mergedQty = $item->getQty();
                    // End of Customisation

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
