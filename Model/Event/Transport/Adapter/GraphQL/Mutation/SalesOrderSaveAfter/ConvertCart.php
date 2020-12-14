<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use Magento\Sales\Api\Data\OrderInterface;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;

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
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($payloadConverter, $data);
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

        // The `is_object_new` extension attribute is set by the plugin on all new orders.
        // If the attribute is missing this indicates that the order pre-dates the plugin
        //      and therefore no cart will exist in Solve.
        $historicOrder = empty($order[OrderInterface::EXTENSION_ATTRIBUTES_KEY]['is_object_new']);
        if ($historicOrder) {
            return false;
        }

        // The absence of an order's `remote_ip` field is inferred to mean that the order wasn't created by
        //      a human and therefore there no associated cart would have been created.
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

        return [
            'id'       => $payload['order'][OrderInterface::QUOTE_ID],
            'orderId'  => $payload['order'][OrderInterface::INCREMENT_ID],
            'provider' => $this->payloadConverter->getOrderProvider($payload['area']),
        ];
    }
}