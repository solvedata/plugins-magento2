<?php

namespace SolveData\Events\Plugin\Quote;

use Magento\Quote\Model\Quote;

class QuoteInterceptor
{
    public function __construct(\SolveData\Events\Model\Logger $logger)
    {
        $this->logger = $logger;
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

    public function beforeMerge(Quote $dest, Quote $source)
    {
        $this->logger->debug('QuoteInterceptor->beforeMerge', [
            'dest' => $dest,
            'source' => $source,
            'destId' => $dest->getId(),
            'reclaimedFrom' => $source->getExtensionAttributes()->getReclaimedFrom()
        ]);
        
        if (
            $dest->getId()
            && $source->getExtensionAttributes()->getReclaimedFrom() === $dest->getId()
        ) {
            $dest->removeAllItems();
        }

        // Do not modify the arguments to the acutal call to Quote->merge()
        return null;
    }
}
