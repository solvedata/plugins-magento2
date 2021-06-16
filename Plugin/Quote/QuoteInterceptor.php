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
    // TODO more comments as to why
    public function aroundMerge(Quote $dest, callable $ignored_proceed, Quote $source)
    {
        $this->quoteMerger->merge($dest, $source);
        return $dest;
    }
}
