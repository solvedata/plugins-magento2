<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class CreateOrUpdatePayment extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation create_or_update_payment($input: PaymentInput!) {
    create_or_update_payment(input: $input) {
        id
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
        $payment = $this->payloadConverter->getOrderPaymentData($payload['order']);

        if (is_null($payment) || empty($payment[OrderPaymentInterface::AMOUNT_PAID])) {
            return false;
        }

        $amount_paid = $payment[OrderPaymentInterface::AMOUNT_PAID];
        if (floatval($amount_paid) === 0.0) {
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
            'input' => $this->payloadConverter->convertPaymentData(
                $payload['order'],
                $payload['area']
            ),
        ];
    }
}
