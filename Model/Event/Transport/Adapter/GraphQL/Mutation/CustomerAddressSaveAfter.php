<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation;

class CustomerAddressSaveAfter extends MutationAbstract
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

        $input = $this->payloadConverter->convertProfileData(
            $payload['customer'],
            $payload['area']
        );
        
        $input['addresses'][] = $this->payloadConverter->convertAddressData($payload['address']);

        return ['input' => $input];
    }
}
