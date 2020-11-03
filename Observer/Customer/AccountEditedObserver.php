<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Customer;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Customer\AccountEdited;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class AccountEditedObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param AccountEdited $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        AccountEdited $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
