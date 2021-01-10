<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation;

use Magento\Sales\Api\Data\OrderInterface;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;

class OrderCancelAfter extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation create_or_update_order($input: CreateOrUpdateOrderInput!) {
    create_or_update_order(input: $input) {
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
        $payload = $this->getEvent()['payload'];

        return [
            'input'    => $this->payloadConverter->convertOrderData(
                $payload['order'],
                $payload['orderAllVisibleItems'],
                $payload['area']
            ),
        ];
    }
}
