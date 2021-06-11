<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class CreateOrUpdateReturn extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation create_or_update_return($input: ReturnInput!, $options: CreateOrUpdateReturnOptions!) {
    create_or_update_return(input: $input, options: $options) {
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

        if (is_null($payment) || empty($payment[OrderPaymentInterface::AMOUNT_REFUNDED])) {
            return false;
        }

        $amount_refunded = $payment[OrderPaymentInterface::AMOUNT_REFUNDED];
        if (floatval($amount_refunded) === 0.0) {
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

        // Use the timestamp of when the event was enqueued as the event's "occurred at" time.
        // Note that this the time when the event was created not when the order was created.
        $occurredAt = $this->payloadConverter->getFormattedDatetime($event['created_at']);
        $options = [
            'occurred_at' => $occurredAt
        ];

        return [
            'input' => $this->payloadConverter->convertReturnData(
                $payload['order'],
                $payload['area']
            ),
            'options' => $options
        ];
    }
}
