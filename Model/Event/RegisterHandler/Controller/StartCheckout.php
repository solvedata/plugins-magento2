<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler\Controller;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use SolveData\Events\Helper\Customer as CustomerHelper;
use SolveData\Events\Helper\Event as EventHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event\RegisterHandler\Converter;
use SolveData\Events\Model\Event\RegisterHandler\EventAbstract;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Logger;

class StartCheckout extends EventAbstract
{
    private $cart;

    public function __construct(
        Config $config,
        Converter $converter,
        CustomerHelper $customerHelper,
        EventHelper $eventHelper,
        EventRepository $eventRepository,
        Logger $logger,
        Cart $cart
    ) {
        $this->cart = $cart;

        parent::__construct($config, $converter, $customerHelper, $eventHelper, $eventRepository, $logger);
    }

    /**
     * Event is allowed
     *
     * @param Observer $observer
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     */
    protected function isAllowed(Observer $observer): bool
    {
        if (!parent::isAllowed($observer)) {
            return false;
        }
        
        $quote = $this->cart->getQuote();
        return !empty($quote) && !empty($quote->getId());
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
        $quote = $this->cart->getQuote();
        $quoteAllVisibleItems = $quote->getAllVisibleItems();

        // Add final price to payload
        foreach ($quote->getAllVisibleItems() as $item) {
            $item->setData('final_price', $item->getProduct()->getFinalPrice());
        }

        $this->setAffectedEntityId((int)$quote->getEntityId())
            ->setPayload([
                'quote'                => $quote,
                'quoteAllVisibleItems' => $quoteAllVisibleItems,
                'quoteReachedCheckout' => true,
                'area'                 => $this->eventHelper->getAreaPayloadData($quote->getStoreId())
            ]);

        return $this;
    }
}
