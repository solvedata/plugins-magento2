<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CheckoutCartSaveAfter;

use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class CreateOrUpdateCustomer extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation createOrUpdateProfile($input: ProfileInput!) {
    createOrUpdateProfile(input: $input) {
        id,
        emails
    }
}
GRAPHQL;

    public function isAllowed(): bool
    {
        return !empty($this->getEmail());
    }

    /**
     * Get variables for GraphQL request
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getVariables(): array
    {
        $input = [
            'email' => $this->getEmail()
        ];

        return ['input' => $input];
    }

    private function getEmail(): ?string
    {
        $event = $this->getEvent();
        $payload = $event['payload'];

        $quote = $payload['quote'];
        if (array_key_exists('customer_email', $quote)) {
            $email = $quote['customer_email'];
            return empty($email) ? null : $email;
        } else {
            return null;
        }
    }
}
