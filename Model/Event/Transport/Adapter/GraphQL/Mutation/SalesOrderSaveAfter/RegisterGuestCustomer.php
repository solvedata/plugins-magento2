<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use Magento\Sales\Api\Data\OrderInterface;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class RegisterGuestCustomer extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation createOrUpdateProfile($input: ProfileInput!) {
    createOrUpdateProfile(input: $input) {
        sid,
        emails
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
        if (empty($payload['order'][OrderInterface::CUSTOMER_IS_GUEST])) {
            return false;
        }
        if (empty($payload['order'][OrderInterface::EXTENSION_ATTRIBUTES_KEY]['is_object_new'])
            && empty($payload['order'][OrderInterface::EXTENSION_ATTRIBUTES_KEY]['is_import_to_solve_data'])
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
