<?php

declare(strict_types=1);

namespace SolveData\CustomHooks\Observer;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Logger;
use SolveData\Events\Observer\ObserverAbstract;
use SolveData\CustomHooks\Model\Event\ReviewSave;

class ReviewSavedAfter extends ObserverAbstract
{
    /**
    * @param Config $config
    * @param EventRepository $eventRepository
    * @param Logger $logger
    * @param ReviewSave $handler
    */
    public function __construct(
        Config $config,
        EventRepository $eventRepository,
        Logger $logger,
        ReviewSave $handler
    ) {
        parent::__construct($config, $eventRepository, $logger, $handler);
    }
}
