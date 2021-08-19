<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter;

use Magento\Framework\HTTP\Adapter\Curl as CurlAdapter;
use SolveData\Events\Api\Event\Transport\AdapterInterface;

abstract class CurlAbstract extends CurlAdapter implements AdapterInterface
{
    /**
     * Send events by bulk
     *
     * @param array $events
     *
     * @return int[]
     *
     * @throws \Exception
     */
    abstract public function sendBulk(array $events): array;

    /**
     * Send event
     *
     * @param array $event
     *
     * @return int|false
     */
    abstract public function send(array $event);

    /**
     * Send request
     *
     * @param array $event
     *
     * @return int|null
     */
    abstract protected function request(array $event);
}
