<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Newsletter;

use Magento\Framework\Event\Observer;
use Magento\Newsletter\Model\Subscriber;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

class SubscriberSaveCommit extends EventAbstract
{
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
        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getSubscriber();

        $this->setAffectedEntityId((int)$subscriber->getId())
            ->setPayload(['subscriber' => $subscriber]);

        return $this;
    }
}
