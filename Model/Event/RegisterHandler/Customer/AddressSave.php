<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Customer;

use Magento\Customer\Model\Address;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

class AddressSave extends EventAbstract
{
    /**
     * Get observer data
     *
     * @param Observer $observer
     *
     * @return EventAbstract
     *
     * @throws LocalizedException
     */
    public function prepareData(Observer $observer): EventAbstract
    {
        /** @var Address $customerAddress */
        $customerAddress = $observer->getEvent()->getCustomerAddress();
        $customer = $customerAddress->getCustomer();
        $this->customerHelper->prepareCustomerGender($customer);

        $this->setAffectedEntityId((int)$customerAddress->getEntityId())
            ->setPayload([
                'customer' => $customer,
                'address'  => $customerAddress->getDataModel(),
                'area'     => $this->eventHelper->getAreaPayloadData($customer->getStoreId()),
            ]);

        return $this;
    }
}
