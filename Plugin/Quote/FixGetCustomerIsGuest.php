<?php

namespace SolveData\Events\Plugin\Quote;

use Magento\Quote\Model\Quote;

class FixGetCustomerIsGuest
{
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
}
