<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Sales;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Sales\OrderSave;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class OrderSaveAfterObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param OrderSave $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        OrderSave $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
