<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation;

use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class RegisterCustomerForOrder extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation createOrUpdateProfile($input: ProfileInput!) {
    createOrUpdateProfile(input: $input) {
        id,
        emails
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
        $area = $payload['area'];

        $input = [
            'email' => $order['customer_email'],
            'attributes' => json_encode($this->payloadConverter->prepareAttributesData($area))
        ];

        if (!empty($order['addresses'])) {
            $input['addresses'] = $this->payloadConverter->convertAddressesData($order['addresses']);
        }

        return ['input' => $input];
    }
}
