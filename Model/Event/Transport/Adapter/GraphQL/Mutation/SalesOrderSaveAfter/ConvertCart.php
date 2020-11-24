<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use Magento\Sales\Api\Data\OrderInterface;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class ConvertCart extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation convertCart($id: String!, $orderId: String, $provider: String!) {
    convertCart(id: $id, orderId: $orderId, provider: $provider) {
        profile_id
    }
}
GRAPHQL;

    /**
     * Mutation is allowed
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        $payload = $this->getEvent()['payload'];
        if (empty($payload['order'][OrderInterface::EXTENSION_ATTRIBUTES_KEY]['is_object_new'])
            || !empty($payload['order'][OrderInterface::CUSTOMER_IS_GUEST])
            || empty($payload['order'][OrderInterface::REMOTE_IP])
        ) {
            return false;
        }

        return parent::isAllowed();
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
        $payload = $this->getEvent()['payload'];

        return [
            'id'       => $payload['order'][OrderInterface::QUOTE_ID],
            'orderId' => $payload['order'][OrderInterface::INCREMENT_ID],
            'provider' => $this->payloadConverter->getOrderProvider($payload['area']),
        ];
    }
}