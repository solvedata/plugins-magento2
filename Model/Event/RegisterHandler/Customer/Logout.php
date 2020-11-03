<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Customer;

use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

class Logout extends EventAbstract
{
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
        /** @var Customer $customer */
        $customer = $observer->getEvent()->getCustomer();
        $this->customerHelper->prepareCustomerGender($customer);

        $this->setAffectedEntityId((int)$customer->getEntityId())
            ->setPayload([
                'customer' => $customer,
                'area'     => $this->eventHelper->getAreaPayloadData($customer->getStoreId()),
            ]);

        return $this;
    }
}
