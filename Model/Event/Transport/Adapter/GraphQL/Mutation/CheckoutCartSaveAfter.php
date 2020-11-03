<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation;

class CheckoutCartSaveAfter extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation createOrUpdateCart($input: CartInput!) {
    createOrUpdateCart(input: $input) {
        sid
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
            $payload['area']
        );
        $input['lastInteractionAt'] = $this->payloadConverter->getFormattedDatetime($event['created_at']);

        return ['input' => $input];
    }
}
