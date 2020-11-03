<?php

declare(strict_types=1);

namespace SolveData\Events\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Model\Order;

class OrderSaveBeforeObserver implements ObserverInterface
{
    /**
     * @var OrderExtensionFactory
     */
    protected $orderExtensionFactory;

    public function __construct(
        OrderExtensionFactory $orderExtensionFactory
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
    }

    /**
     * Set isObjectNew flat to extension attributes of order
     *
     * @param Observer $observer
     *
     * @return OrderSaveBeforeObserver
     */
    public function execute(Observer $observer): OrderSaveBeforeObserver
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        /** @var OrderExtensionInterface $orderExtension */
        $orderExtension = $order->getExtensionAttributes();
        if (empty($orderExtension)) {
            $orderExtension = $this->orderExtensionFactory->create();
        }

        $orderExtension->setIsObjectNew($order->isObjectNew());

        return $this;
    }
}
