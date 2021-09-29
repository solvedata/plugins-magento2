<?php

declare(strict_types=1);

namespace SolveData\CustomHooks\Model\Event;

use Magento\Framework\Event\Observer;
use Magento\Review\Model\Review;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

class ReviewSave extends EventAbstract
{
    /**
     * Event is allowed
     *
     * @param Observer $observer
     *
     * @return bool
     */
    protected function isAllowed(Observer $observer): bool
    {
        // This function can be customized to ...
        return parent::isAllowed($observer);
    }

    /**
     * Get observer data
     *
     * @param Observer $observer
     *
     * @return EventAbstract
     *
     * @throws \Exception
     */
    public function prepareData(Observer $observer): EventAbstract
    {
        $event = $observer->getEvent();

        /** @var Review $review */
        $review = $event->getDataObject();

        $this->setAffectedEntityId((int)$review->getEntityId())
            ->setPayload([
                'review' => $review,
                'area'   => $this->eventHelper->getAreaPayloadData($review->getStoreId())
            ]);

        return $this;
    }
}
