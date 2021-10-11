<?php

declare(strict_types=1);

namespace SolveData\CustomHooks\Model\Config;

use SolveData\Events\Model\Config\EventMutationConfig;

/**
 * Merge in configuration for handling the `review_save_after` event on the other side of Solve event queue.
 */
class EventMutationConfigPlugin
{
    private $customConfiguration = [
        // The configuration is an array of event names (see SolveData\Events\Model\Event)
        // to a list of GraphQL mutation(s) which will process the event into Solve's GraphQL API.
        'review_save_after' => [
            \SolveData\CustomHooks\Model\GraphQL\CreateReviewMutation::class
        ]
    ];

    function aroundGetMutationsForEvents(EventMutationConfig $subject, callable $proceed): array
    {
        return array_merge($proceed(), $this->customConfiguration);
    }
}
