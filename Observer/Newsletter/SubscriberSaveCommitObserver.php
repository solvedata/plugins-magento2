<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Newsletter;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Event\RegisterHandler\Newsletter\SubscriberSaveCommit;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;

class SubscriberSaveCommitObserver extends ObserverAbstract
{
    /**
     * @param Config $config
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param SubscriberSaveCommit $handler
     */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        SubscriberSaveCommit $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
