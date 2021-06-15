<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Controller;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Controller\StartCheckout;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class StartCheckoutObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param OrderCancel $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        StartCheckout $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}