<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use Magento\Sales\Api\Data\OrderInterface;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class RegisterCustomer extends MutationAbstract
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

        if (!empty($order['customer_firstname'])) {
            $input['firstName'] = $order['customer_firstname'];
        }

        if (!empty($order['customer_lastname'])) {
            $input['lastName'] = $order['customer_lastname'];
        }

        if (!empty($order['customer_firstname']) && !empty($order['customer_lastname'])) {
            $input['fullName'] = $order['customer_firstname'] . ' ' . $order['customer_lastname'];
        }

        return ['input' => $input];
    }
}
