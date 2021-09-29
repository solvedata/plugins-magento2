<?php

declare(strict_types=1);

namespace SolveData\CustomHooks\Model\GraphQL;

use Magento\Review\Model\Review;
use Magento\Customer\Api\CustomerRepositoryInterface;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;
use SolveData\Events\Model\Logger;

class CreateReviewEventMutation extends MutationAbstract
{
    // See https://docs.solvedata.app/latest/api/events for more details on raw events.
    const QUERY = <<<'GRAPHQL'
mutation queue_event($eventInput: EventInput!) {
    queue_event(input: $eventInput) {
        id
    }
}
GRAPHQL;

    private $customerRepository;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        PayloadConverter $payloadConverter,
        Logger $logger,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        parent::__construct($payloadConverter, $logger, $data);
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

        $review = $event['payload']['review'];
        $area = $event['payload']['area'];

        $profileId = null;
        if (!empty($review['customer_id'])) {
            $customer = $this->customerRepository->getById($review['customer_id']);
            $profileId = $this->payloadConverter->getProfileId($customer->getEmail(), $area);
        }

        $eventInput = [
            'type'       => 'custom_product_reviewed',
            'event_time' => $this->payloadConverter->getFormattedDatetime($review['created_at']),
            'payload'    => [
                'profile_id' => $profileId,
                'status'     => $this->convertReviewStatus($review),
                'title'      => $review['title'] ?? null
            ]
        ];

        // The keys must correspond to the variables defined in the above GraphQL request.
        return ['eventInput' => $eventInput];
    }

    private function convertReviewStatus(array $review): ?string
    {
        switch ($review['status'] ?? -1)
        {
            case Review::STATUS_APPROVED:
                return 'APPROVED';
            case Review::STATUS_NOT_APPROVED:
                return 'NOT_APPROVED';
            case Review::STATUS_PENDING:
                return 'PENDING';
            default:
                return 'UNKNOWN';
        }
    }
}
