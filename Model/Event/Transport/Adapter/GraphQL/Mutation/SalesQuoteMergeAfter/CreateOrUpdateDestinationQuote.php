<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesQuoteMergeAfter;

use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class CreateOrUpdateDestinationQuote extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation createOrUpdateCart($input: CartInput!, $options: CreateOrUpdateCartOptions!) {
    createOrUpdateCart(input: $input, options: $options) {
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

        $input = $this->payloadConverter->convertCartData(
            $payload['quote'],
            $payload['quoteAllVisibleItems'],
            $payload['area'],
            ['merged_from' => $payload['source']]
        );

        // Use the timestamp of when the event was enqueued as the event's "occurred at" time.
        // Note that this the time when the event was created not when the cart was created.
        $occurredAt = $this->payloadConverter->getFormattedDatetime($event['created_at']);

        $input['lastInteractionAt'] = $occurredAt;
        $options = [
            'occurred_at' => $occurredAt
        ];

        return [
            'input' => $input,
            'options' => $options
        ];
    }
}
