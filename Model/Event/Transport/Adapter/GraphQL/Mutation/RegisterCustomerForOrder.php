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

        return [
            'input' => $this->payloadConverter->convertOrderProfileData(
                $payload['order'],
                $payload['area']
            )
        ];
    }
}
