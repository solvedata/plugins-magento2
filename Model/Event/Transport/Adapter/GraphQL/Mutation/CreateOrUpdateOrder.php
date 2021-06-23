<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation;

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
        $order = $payload['order'];

        $input = $this->payloadConverter->convertOrderData(
            $order,
            $payload['orderAllVisibleItems'],
            $payload['area']
        );

        $isRealtimeEvent = empty($order['extension_attributes']['is_import_to_solve_data']);
        if ($isRealtimeEvent) {
            // Use the timestamp of when the event was enqueued as the event's "occurred at" time.
            // Note that this the time when the event was created not when the order was created.
            $occurredAt = $event['created_at'];
        } else {
            // The order is being imported. Use the order's "created at" field to approximate the "occurred at" time.
            $occurredAt = $order['created_at'];
        }
                
        $options = [
            'occurred_at' => $this->payloadConverter->getFormattedDatetime($occurredAt)
        ];

        return [
            'input' => $input,
            'options' => $options
        ];
    }
}
