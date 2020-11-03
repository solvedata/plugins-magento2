<?php

namespace SolveData\Events\Api\Event\Transport;

interface AdapterInterface
{
    /**
     * Send events by bulk
     *
     * @param array $events
     *
     * @return int[]
     */
    public function sendBulk(array $events): array;

    /**
     * Send event
     *
     * @param array $event
     *
     * @return int
     */
    public function send(array $event);
}
