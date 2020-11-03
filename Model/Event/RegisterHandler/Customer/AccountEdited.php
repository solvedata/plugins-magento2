<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Customer;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Event\Observer;
use SolveData\Events\Helper\Customer as CustomerHelper;
use SolveData\Events\Helper\Event as EventHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event\RegisterHandler\Converter;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;
use SolveData\Events\Model\Logger;

class AccountEdited extends EventAbstract
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    public function __construct(
        Config $config,
        Converter $converter,
        CustomerHelper $customerHelper,
        CustomerRepository $customerRepository,
        EventHelper $eventHelper,
        EventRepository $eventRepository,
        Logger $logger,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;

        parent::__construct(
            $config,
            $converter,
            $customerHelper,
            $eventHelper,
            $eventRepository,
            $logger,
            $data
        );
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
        /** @var string $customerEmail */
        $customerEmail = $observer->getEvent()->getEmail();
        $customer = $this->customerRepository->get($customerEmail);
        $this->customerHelper->prepareCustomerGender($customer);

        $this->setAffectedEntityId((int)$customer->getId())
            ->setPayload([
                'customer' => $customer,
                'area'     => $this->eventHelper->getAreaPayloadData($customer->getStoreId()),
            ]);

        return $this;
    }
}
