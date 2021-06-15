<?php

declare(strict_types=1);

namespace SolveData\Events\Model;

use DateTime;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use SolveData\Events\Helper\Event as EventHelper;
use SolveData\Events\Helper\Customer as CustomerHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event\RegisterHandler\Converter;
use SolveData\Events\Model\Event\Transport;
use SolveData\Events\Model\Event\WebhookForwarder\WebhookForwarder;
use SolveData\Events\Model\ResourceModel\Event as ResourceModel;

class Event extends AbstractModel
{
    const STATUS_NEW        = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_COMPLETED  = 2;
    const STATUS_FAILED     = 3;
    const STATUS_RETRY      = 4;
    const STATUS_EXCEPTION  = 5;

    const STATUSES = [
        self::STATUS_NEW        => 'New',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETED  => 'Completed',
        self::STATUS_FAILED     => 'Failed',
        self::STATUS_RETRY      => 'Retry',
        self::STATUS_EXCEPTION  => 'Exception',
    ];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var EventHelper
     */
    protected $eventHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderExtensionFactory
     */
    protected $orderExtensionFactory;

    /**
     * @var Transport
     */
    protected $transport;

    protected $webhookForwarder;

    /**
     * @param Config $config
     * @param Context $context
     * @param Converter $converter
     * @param CustomerHelper $customerHelper
     * @param EventHelper $eventHelper
     * @param Logger $logger
     * @param OrderExtensionFactory $orderExtensionFactory
     * @param Registry $registry
     * @param Transport $transport
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Config $config,
        Context $context,
        Converter $converter,
        CustomerHelper $customerHelper,
        EventHelper $eventHelper,
        Logger $logger,
        OrderExtensionFactory $orderExtensionFactory,
        Registry $registry,
        Transport $transport,
        WebhookForwarder $webhookForwarder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->config = $config;
        $this->converter = $converter;
        $this->customerHelper = $customerHelper;
        $this->eventHelper = $eventHelper;
        $this->logger = $logger;
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->transport = $transport;
        $this->webhookForwarder = $webhookForwarder;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get events data to send
     *
     * @return array
     */
    protected function getEventsToSend()
    {
        return $this->getResource()->getEventsToSend();
    }

    /**
     * Purge events older than the retention period
     *
     * @return int Number of events purged from history
     */
    protected function purgeEventsOlderThan(DateTime $olderThan): int
    {
        return $this->getResource()->purgeEventsOlderThan($olderThan);
    }

    /**
     * Lock events to processing
     *
     * @param array $eventIds
     *
     * @return Event
     */
    protected function lockEventsToProcessing(array $eventIds): Event
    {
        $this->getResource()->lockEventsToProcessing($eventIds);

        return $this;
    }

    /**
     * Update events statuses
     *
     * @param array $events
     * @param array $requestResults
     *
     * @return int The number of affected rows
     */
    protected function updateEvents(array $events, array $requestResults): int
    {
        return $this->getResource()->updateEvents($events, $requestResults);
    }

    /**
     * Create events
     *
     * @param array $events
     *
     * @return int The number of affected rows
     */
    protected function createEvents(array $events): int
    {
        return $this->getResource()->createEvents($events);
    }

    /**
     * Place customer collection to events
     *
     * @param array $customers
     *
     * @return int The number of affected rows
     */
    public function placeCustomers(array $customers): int
    {
        $events = [];
        foreach ($customers as $customer) {
            try {
                $this->customerHelper->prepareCustomerGender($customer);
                $data = [
                    'customer' => $customer,
                    'area'     => $this->eventHelper->getAreaPayloadData($customer->getStoreId()),
                ];
                $events[] = [
                    'name'                  => 'customer_register_success',
                    'status'                => Event::STATUS_NEW,
                    'payload'               => json_encode($this->converter->convert($data)),
                    'affected_entity_id'    => $customer->getEntityId(),
                    'store_id'              => $customer->getStoreId(),
                ];
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        return !empty($events) ? $this->createEvents($events) : 0;
    }

    /**
     * Place order collection to events
     *
     * @param array $orders
     *
     * @return int The number of affected rows.
     */
    public function placeOrders(array $orders): int
    {
        $events = [];
        foreach ($orders as $order) {
            /** @var OrderExtensionInterface $orderExtension */
            $orderExtension = $order->getExtensionAttributes();
            if (empty($orderExtension)) {
                $orderExtension = $this->orderExtensionFactory->create();
            }
            $orderExtension->setIsImportToSolveData(true);

            // Load addresses if addresses is null
            $order->getAddresses();

            try {
                $data = [
                    'order'                => $order,
                    'orderAllVisibleItems' => $order->getAllVisibleItems(),
                    'area'                 => $this->eventHelper->getAreaPayloadData($order->getStoreId()),
                ];
                $events[] = [
                    'name'                  => 'sales_order_save_after',
                    'status'                => Event::STATUS_NEW,
                    'payload'               => json_encode($this->converter->convert($data)),
                    'affected_entity_id'    => $order->getEntityId(),
                    'affected_increment_id' => $order->getIncrementId(),
                    'store_id'              => $order->getStoreId(),
                ];
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        return !empty($events) ? $this->createEvents($events) : 0;
    }

    /**
     * Place order collection to events
     *
     * @param array $orders
     *
     * @return int The number of affected rows.
     */
    public function placeCustomEvent(string $eventType, array $payload): int
    {
        $events = [];
        try {
            $store = $this->eventHelper->getStore();
            $payload['area'] = $this->eventHelper->getAreaPayloadData();

            $events[] = [
                'name'                  => "solvedata_$eventType",
                'status'                => Event::STATUS_NEW,
                'payload'               => json_encode($payload),
                'affected_entity_id'    => $store->getId(),
                'affected_increment_id' => null,
                'store_id'              => $store->getId()
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return !empty($events) ? $this->createEvents($events) : 0;
    } 

    /**
     * Send events
     *
     * @return bool
     */
    public function sendEvents(): bool
    {
        $cronId = md5(uniqid(strval(rand()), true));
        $resource = $this->getResource();

        # Grab a selection of events we need to send
        $eventsToProcess = $this->getEventsToSend();
        if (empty($eventsToProcess)) {
            return false;
        }

        $transactionBatchSize = $this->config->getTransactionBatchSize();

        while (!empty($eventsToProcess)) {
            $events = array_slice($eventsToProcess, 0, $transactionBatchSize);            
            $eventIds = array_column($events, ResourceModel::ENTITY_ID);

            try {
                $resource->beginTransaction();
                $this->logger->debug('Starting processing event', ['event_entity_ids' => $eventIds, 'cron_id' => $cronId]);

                $this->lockEventsToProcessing($eventIds);

                $webhookResult = $this->webhookForwarder->process($events);
                $requestResults = $this->transport->send($events);

                // Append the webhook forwarder's result onto the end of each event's results before saving.
                foreach ($requestResults as &$result) {
                    $result[] = $webhookResult;
                }
                $this->updateEvents($events, $requestResults);

                $this->logger->debug('Finished processing event', ['event_entity_ids' => $eventIds, 'cron_id' => $cronId]);
                $resource->commit();
            } catch (\Throwable $t) {
                $resource->rollBack();

                $errorResult = [['exception' => "$t"]];
                $requestResults = array_fill_keys($eventIds, $errorResult);
                $this->updateEvents($events, $requestResults);

                $this->logger->critical('Error processing event', [
                    'exception' => $t,
                    'event_entity_ids' => $eventIds,
                    'cron_id' => $cronId
                ]);
            }

            $eventsToProcess = array_slice($eventsToProcess, $transactionBatchSize);
        }

        return true;
    }

    /**
     * Send events
     *
     * @return bool
     */
    public function purgeEvents(): bool
    {
        $resource = $this->getResource();
        $retentionPeriod = $this->config->getEventRetention();

        if (empty($retentionPeriod)) {
            return true;
        }

        $now = new DateTime();
        $purgeOlderThan = $now->sub($retentionPeriod);

        try {
            $resource->beginTransaction();

            $purgedEventCount = $this->purgeEventsOlderThan($purgeOlderThan);

            $resource->commit();

            if ($purgedEventCount > 0) {
                $this->logger->debug(sprintf(
                    'Purged %d events older than %s',
                    $purgedEventCount,
                    $purgeOlderThan->format(DateTime::ISO8601)
                ));
            }

            return true;
        } catch (\Throwable $e) {
            $resource->rollBack();

            $this->logger->debug(sprintf(
                'Error purging events older than %s',
                $purgeOlderThan->format(DateTime::ISO8601)
            ));
            $this->logger->critical($e);

            return false;
        }
    }
}
