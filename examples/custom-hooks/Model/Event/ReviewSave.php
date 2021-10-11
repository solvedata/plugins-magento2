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
        // The parent's function can be overridden to decide whether or not to handle the Magento event.
        return parent::isAllowed($observer);
    }

    /**
     * Prepare data to be inserted into Solve Event Queue for a background cronjob to process.
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

        // The affectedEntityId is a required field which is used to debug failing events via the Admin's Event Queue page.
        $this->setAffectedEntityId((int)$review->getEntityId());

        // The event's payload contains the relevant information from the Magento event.
        // The payload array will be serialized as JSON and stored in the Event Queue table (`solvedata_events`).
        $this->setPayload([
            'review' => $review,
            'area'   => $this->eventHelper->getAreaPayloadData($review->getStoreId())
        ]);

        return $this;
    }
}
