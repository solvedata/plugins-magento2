<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Customer;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Customer\RegisterSuccess;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class RegisterObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param RegisterSuccess $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        RegisterSuccess $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
