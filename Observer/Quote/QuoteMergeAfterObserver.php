<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Quote;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Quote\QuoteMerge;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class QuoteMergeAfterObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param QuoteMerge $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        QuoteMerge $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
