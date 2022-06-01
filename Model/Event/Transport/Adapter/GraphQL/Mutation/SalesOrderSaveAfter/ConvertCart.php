<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use Magento\Sales\Api\Data\OrderInterface;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;
use SolveData\Events\Model\Logger;

class ConvertCart extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation convertCart($id: String!, $orderId: String, $provider: String!, $options: ConvertCartOptions) {
    convertCart(id: $id, orderId: $orderId, provider: $provider, options: $options) {
        profile_id
    }
}
GRAPHQL;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     * @param PayloadConverter $payloadConverter
     * @param array $data
     */
    public function __construct(
        Config $config,
        PayloadConverter $payloadConverter,
        Logger $logger,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($payloadConverter, $logger, $data);
    }

    /**
     * Mutation is allowed
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        $payload = $this->getEvent()['payload'];
        $order = $payload['order'];

        $cartIdAbsent = empty($order[OrderInterface::QUOTE_ID]);
        if ($cartIdAbsent) {
            return false;
        }

        // The `is_object_new` extension attribute is set by the plugin on events where the order has just been placed
        //      and hasn't been saved into the database yet.
        $historicOrder = empty($order[OrderInterface::EXTENSION_ATTRIBUTES_KEY]['is_object_new']);
        if ($historicOrder && $this->config->convertHistoricalCarts() === false) {
            return false;
        }

        // The absence of an order's `remote_ip` field is inferred to mean that the order was created by
        //      an automated process rather than a customer and therefore no associated cart would have been created.
        // Note (Liam): I'm unsure whether this is necessary.
        $automatedOrder = empty($order[OrderInterface::REMOTE_IP]);
        if ($automatedOrder) {
            return false;
        }

        // Only attempt to convert carts for guest users if the plugin has been configured to create
        //      carts for anonymous customers.
        $guestOrder = !empty($order[OrderInterface::CUSTOMER_IS_GUEST]);
        if ($guestOrder && $this->config->isAnonymousCartsEnabled() === false) {
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
        $order = $payload['order'];

        $occurredAt = !empty($order[OrderInterface::CREATED_AT]) ?
            $this->payloadConverter->getFormattedDatetime($order[OrderInterface::CREATED_AT])
            : null;

        return [
            'id'       => $order[OrderInterface::QUOTE_ID],
            'orderId'  => $order[OrderInterface::INCREMENT_ID],
            'provider' => $this->payloadConverter->getOrderProvider($payload['area']),
            'options'  => [
                'occurred_at' => $occurredAt
            ]
        ];
    }
}
