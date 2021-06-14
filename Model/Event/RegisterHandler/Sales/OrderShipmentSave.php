<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Sales;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Shipment;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;

class OrderShipmentSave extends EventAbstract
{
    /**
     * Before process event method
     *
     * @param Observer $observer
     *
     * @return EventAbstract
     */
    protected function beforeProcess(Observer $observer): EventAbstract
    {
        parent::beforeProcess($observer);

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        try {
            if (empty($order->getState())) {
                // Throw an exception in order to generate a backtrace on the warning
                throw new \Exception('Order state is empty');
            }
        } catch (\Exception $e) {
            $this->logger->warning('Order state is empty', [
                'exception' => $e,
                'entityId' => $order->getEntityId(),
                'eventName' => $this->getEventName()
            ]);
        }

        return $this;
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
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();

        // Load addresses if addresses is null
        $order->getAddresses();

        $this->setAffectedEntityId((int)$shipment->getEntityId())
            ->setAffectedIncrementId($shipment->getIncrementId())
            ->setPayload([
                'shipment'             => $shipment,
                'order'                => $order,
                'orderAllVisibleItems' => $order->getAllVisibleItems(),
                'area'                 => $this->eventHelper->getAreaPayloadData($order->getStoreId()),
            ]);

        return $this;
    }
}
