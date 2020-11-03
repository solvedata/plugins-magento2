<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Checkout;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;
use SolveData\Events\Model\Event\RegisterHandler\Checkout\CartSave;

class CartSaveObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param CartSave $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        CartSave $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
