<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation;

use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class QueueEvent extends MutationAbstract
{
    const SOLVEDATA_EVENT_NAME_PREFIX = 'solvedata_';

    const QUERY = <<<'GRAPHQL'
mutation queue_event($input: EventInput!) {
    queue_event(input: $input) {
        type,
        id,
        event_time
    }
}
GRAPHQL;

    /**
     * Get variables for GraphQL request
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getVariables(): array
    {
        $event = $this->getEvent();
        $payload = $event['payload'];

        $eventType = self::eventType($event['name']);
        $eventTime = $this->payloadConverter->getFormattedDatetime($event['created_at']);
        $eventPayload = json_encode($payload);

        $eventStore = null;
        if (!empty($payload['area'])) {
            $eventStore = $this->payloadConverter->getOrderProvider($payload['area']);
        }

        return [
            'input' => [
                'type' => $eventType,
                'event_time' => $eventTime,
                'payload' => $eventPayload,
                'store' => $eventStore
            ]
        ];
    }

    public static function isSolveDataEvent(string $eventName): bool
    {
        try {
            // Returns true iff the event name begins with `QueueEvent::SOLVEDATA_EVENT_NAME_PREFIX`
            return substr($eventName, 0, strlen(QueueEvent::SOLVEDATA_EVENT_NAME_PREFIX)) === QueueEvent::SOLVEDATA_EVENT_NAME_PREFIX;
        } catch (\Throwable $t) {
            return false;
        }
    }

    private static function eventType(string $eventName): string
    {
        return substr($eventName, strlen(QueueEvent::SOLVEDATA_EVENT_NAME_PREFIX));
    }
}
