<?php

declare(strict_types=1);

namespace SolveData\CustomHooks\Model\GraphQL;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Review\Model\Review;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;
use SolveData\Events\Model\Logger;

class CreateReviewMutation extends MutationAbstract
{
    // For more details see https://docs.solvedata.app/latest/api/events
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
            // Look up the Solve Profile ID for the given customer's email address.
            $customer = $this->customerRepository->getById($review['customer_id']);
            $profileId = $this->payloadConverter->getProfileId($customer->getEmail(), $area);
        }

        $eventInput = [
            'type'       => 'custom_product_reviewed',
            'event_time' => $this->payloadConverter->getFormattedDatetime($review['created_at']),
            'store'      => $this->payloadConverter->getOrderProvider($area),
            'payload'    => [
                'profile_id' => $profileId,
                'status'     => $this->convertReviewStatus($review),
                'title'      => $review['title'] ?? null
            ]
        ];

        // The array corresponds to GraphQL request defined at the top of the class.
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
