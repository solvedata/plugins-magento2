<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use SolveData\Events\Helper\Customer as CustomerHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Customer\Login;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class LoginObserver extends ObserverAbstract
{
    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @param Config $config
     * @param CustomerHelper $customerHelper
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param Login $handler
     */
    public function __construct(
        Config $config,
        CustomerHelper $customerHelper,
        EventRepository $eventRepository,
        Logger $logger,
        Login $handler
    ) {
        $this->customerHelper = $customerHelper;

        parent::__construct($config, $eventRepository, $logger, $handler);
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     *
     * @return ObserverAbstract
     */
    public function execute(Observer $observer): ObserverAbstract
    {
        parent::execute($observer);
        $this->customerHelper->setProfileIdentifyFlag();

        return $this;
    }
}
