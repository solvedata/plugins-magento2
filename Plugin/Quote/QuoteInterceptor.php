<?php

namespace SolveData\Events\Plugin\Quote;

use SolveData\Events\Helper\QuoteMerger;
use Magento\Quote\Model\Quote;

class QuoteInterceptor
{
    private $logger;
    private $quoteMerger;

    public function __construct(\SolveData\Events\Model\Logger $logger, QuoteMerger $quoteMerger)
    {
        $this->logger = $logger;
        $this->quoteMerger = $quoteMerger;
    }

    /**
     * Fix for issue with Invalid customer id
     * See: https://github.com/magento/magento2/issues/23908
     *
     * @param Quote $subject
     * @param callable $proceed
     *
     * @return bool
     */
    public function aroundGetCustomerIsGuest(Quote $subject, callable $proceed)
    {
        $customer = $subject->getCustomer();
        if ($customer) {
            return is_null($customer->getId());
        }

        return $proceed();
    }

    // Don't call $ignored_proceed because we override the merge behaviour
    // defined in the merge function
    // app/code/Magento/Quote/Model/Quote.php:2405 (and also any other
    // interceptors that would be called after us)
    //
    // We need to use this customised merge because in the scenario where:
    // - user is logged in
    // - user adds item X to cart
    // - user adds two of item Y to cart
    // - user leaves site for a while
    // - user gets abandoned cart email
    // - user clicks reclaim link. site is opened with no session, and
    //   customer-linked contents are copied to the current (anonymous) cart
    // - user signs in
    // - anonymous cart is merged into customer-linked cart.
    //
    // The default merging behaviour is to sum the items of the all the two
    // carts. However this means that the user will end up with [2X, 4Y] rather
    // than [1X, 2Y]. The customised QuoteMerger copies the items from the
    // anonymous cart to the customer-linked cart, and where the items already
    // exist in the customer-linked cart, use the quantities from the anonymous
    // cart, because those are most up to date.
    public function aroundMerge(Quote $dest, callable $ignored_proceed, Quote $source)
    {
        $this->quoteMerger->merge($dest, $source);
        return $dest;
    }
}
