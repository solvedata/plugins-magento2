<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\WebhookForwarder;

class WebhookForwarderMapper
{
    public function map(array $events): array
    {
        $mappedEvents = array_map(function ($event) {
            $event['payload'] = json_decode($event['payload'], true);

            unset($event['payload']['customer']['password_hash']);
            unset($event['payload']['customer']['rp_token']);

            return $event;
        }, $events);

        return [
            'events' => $mappedEvents
        ];
    }
}
