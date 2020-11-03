<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Cron;

use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event;

class Events
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Event
     */
    protected $event;

    /**
     * @param Config $config
     * @param Event $event
     */
    public function __construct(
        Config $config,
        Event $event
    ) {
        $this->config = $config;
        $this->event = $event;
    }

    /**
     * Event handling
     *
     * @return Events
     */
    public function execute(): Events
    {
        if (!$this->config->isEnabled()) {
            return $this;
        }

        $this->event->sendEvents();
        $this->event->purgeEvents();

        return $this;
    }
}
