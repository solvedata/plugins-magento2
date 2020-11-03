<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event;

use SolveData\Events\Model\Event\Transport\Adapter\GraphQL;

class Transport
{
    /**
     * @var GraphQL $adapter
     */
    protected $adapter;

    /**
     * @param GraphQL $adapter
     */
    public function __construct(
        GraphQL $adapter
    ) {
        $this->adapter = $adapter;
    }

    /**
     * Send events
     *
     * @param array $events
     *
     * @return int[]
     *
     * @throws \Exception
     */
    public function send(array $events): array
    {
        return $this->adapter->sendBulk($events);
    }
}
