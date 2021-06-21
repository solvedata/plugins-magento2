<?php

namespace SolveData\Events\Plugin\Quote;

use SolveData\Events\Helper\QuoteMerger;
use SolveData\Events\Model\Config;
use Magento\Quote\Model\Quote;

class QuoteInterceptor
{
    private $logger;
    private $quoteMerger;
    private $config;

    public function __construct(
        \SolveData\Events\Model\Logger $logger,
        QuoteMerger $quoteMerger,
        Config $config
    )
    {
        $this->logger = $logger;
        $this->quoteMerger = $quoteMerger;
        $this->config = $config;
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

    // Don't call $proceed if we override the merge behaviour
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
    public function aroundMerge(Quote $dest, callable $proceed, Quote $source)
    {
        if ($this->shouldUseCustomMerge()) {
            $this->logger->debug('Overriding quote->merge behavior');
            return $this->quoteMerger->merge($dest, $source);
        } else {
            $this->logger->debug('Not overriding quote->merge behavior');
            return $proceed($source);
        }
    }

    /**
     * Is a user logging in, triggering an anonymous cart to merge into a
     * customer-linked cart. We don't want to override the merge behavior
     * breaking unexpected places in the code. Limit the overriding to just
     * the scenario we want to change (and can test). See `aroundMerge` for
     * more information
     */
    private function shouldUseCustomMerge(): bool
    {
        try {
            if (!$this->config->isCustomCartMergeEnabled()) return false;

            // Fetch 8 stack frames (3 more than needed) unless call stack in
            // Magento varies between versions
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);

            foreach ($stack as $call) {
                // Should match on roughly the 5th call in the stack trace
                if ($call['function'] == 'loadCustomerQuote' &&
                    $call['class'] == 'Magento\\Checkout\\Model\\Session')
                    return true;
            }
            return false;
        } catch (\Throwable $t) {
            $this->logger->error('shouldUseCustomMerge check failed', $t);
        }
    }
}
