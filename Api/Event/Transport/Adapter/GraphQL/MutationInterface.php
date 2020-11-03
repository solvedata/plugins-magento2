<?php

namespace SolveData\Events\Api\Event\Transport\Adapter\GraphQL;

interface MutationInterface
{
    /**
     * Mutation is allowed
     *
     * @return bool
     */
    public function isAllowed(): bool;

    /**
     * Get query for GraphQL request
     *
     * @return string
     */
    public function getQuery(): string;

    /**
     * Get variables for GraphQL request
     *
     * @return array
     */
    public function getVariables(): array;
}
