<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class CreateOrUpdateOrder extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation create_or_update_order($input: CreateOrUpdateOrderInput!, $options: CreateOrUpdateOrderOptions!) {
    create_or_update_order(input: $input, options: $options) {
        profile_id
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

        $input = $this->payloadConverter->convertOrderData(
            $payload['order'],
            $payload['orderAllVisibleItems'],
            $payload['area']
        );

        // Use the timestamp of when the event was enqueued as the event's "occurred at" time.
        // Note that this the time when the event was created not when the order was created.
        $occurredAt = $this->payloadConverter->getFormattedDatetime($event['created_at']);
        $options = [
            'occurred_at' => $occurredAt
        ];

        return [
            'input' => $input,
            'options' => $options
        ];
    }
}
