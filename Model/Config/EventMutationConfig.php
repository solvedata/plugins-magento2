<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Config;

class EventMutationConfig
{
    function getMutationsForEvents(): array
    {
        return [
            'customer_register_success' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CustomerRegisterSuccess::class
            ],
            'customer_account_edited' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CustomerAccountEdited::class
            ],
            'customer_address_save_after' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CustomerAddressSaveAfter::class
            ],
            'customer_login' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CustomerLogin::class
            ],
            'customer_logout' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CustomerLogout::class
            ],
            'checkout_cart_save_after' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CheckoutCartSaveAfter\CreateOrUpdateCustomer::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CheckoutCartSaveAfter\CreateOrUpdateCart::class
            ],
            'newsletter_subscriber_save_commit_after' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\NewsletterSubscriberSaveCommitAfter::class
            ],
            'sales_order_save_after' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\RegisterCustomerForOrder::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CreateOrUpdateOrder::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter\ConvertCart::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter\CreateOrUpdatePayment::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter\CreateOrUpdateReturn::class
            ],
            'sales_order_shipment_save_after' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\RegisterCustomerForOrder::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CreateOrUpdateOrder::class
            ],
            'order_cancel_after' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\RegisterCustomerForOrder::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CreateOrUpdateOrder::class
            ],
            'sales_quote_merge_after' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesQuoteMergeAfter\CreateOrUpdateDestinationQuote::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesQuoteMergeAfter\CreateOrUpdateSourceQuote::class
            ],
            'controller_action_predispatch_checkout_index_index' => [
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CheckoutCartSaveAfter\CreateOrUpdateCustomer::class,
                \SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CheckoutCartSaveAfter\CreateOrUpdateCart::class
            ]
        ];
    }
}
