<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Customer;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Customer\AddressSave;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class AddressSaveObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param AddressSave $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        AddressSave $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
