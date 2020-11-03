<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Customer;

use Magento\Framework\Event\Observer;
use SolveData\Events\Helper\Customer as CustomerHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Customer\Logout;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class LogoutObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param Logout $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        Logout $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
