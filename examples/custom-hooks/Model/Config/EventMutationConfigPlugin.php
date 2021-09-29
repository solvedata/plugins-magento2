<?php

declare(strict_types=1);

namespace SolveData\CustomHooks\Model\Config;

class EventMutationConfigPlugin
{
    private $customConfiguration = [
        'review_save_after' => [
            \SolveData\CustomHooks\Model\GraphQL\CreateReviewEventMutation::class
        ]
    ];

    function aroundGetMutationsForEvents(\SolveData\Events\Model\Config\EventMutationConfig $subject, callable $proceed): array
    {
        return array_merge($proceed(), $this->customConfiguration);
    }
}
