<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Logger;

class Import extends Action
{
    const ENTITY_CUSTOMERS = 'customers';
    const ENTITY_ORDERS    = 'orders';

    const PAGE_SIZE = 1000;

    private $customerCollectionFactory;
    private $orderCollectionFactory;
    private $eventRepository;
    private $logger;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        CustomerCollectionFactory $customerCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        EventRepository $eventRepository,
        Logger $logger
    ) {
        parent::__construct($context);

        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        $entity = $data['entity'] ?? null;
        if (empty($entity) || !in_array($entity, [static::ENTITY_CUSTOMERS, static::ENTITY_ORDERS])) {
            throw new \Exception(sprintf('Invalid resource entity %s', $entity));
        }

        $from = null;
        if (array_key_exists('id_from', $data)) {
            if (!is_numeric($data['id_from'])) {
                throw new \Exception(sprintf('Invalid id from %s', $data['id_from']));
            }

            $from = (int)$data['id_from'];
        }

        $to = null;
        if (array_key_exists('id_to', $data)) {
            if (!is_numeric($data['id_to'])) {
                throw new \Exception(sprintf('Invalid id to %s', $data['id_to']));
            }

            $to = (int)$data['id_to'];
        }

        $this->logger->debug('Attempting to import entities', [
            'entity' => $entity,
            'from'   => $from,
            'to'     => $to,
        ]);

        $collection = $this->getCollection($entity, $from, $to);
        $pages = $collection->getLastPageNumber();

        $page = 1;
        $affectedRows = 0;
        while ($page <= $pages) {
            $collection->setCurPage($page++);
            $collection->clear();
            $affectedRows += $this->placeItems($entity, $collection->getItems());
        }

        $this->logger->debug('Succesfully import entities', [
            'entity'        => $entity,
            'from'          => $from,
            'to'            => $to,
            'affected_rows' => $affectedRows,
        ]);

        $this->messageManager->addSuccessMessage(sprintf(
            'Successfully queued %d events',
            $affectedRows
        ));

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('solvedata_events/event/index');

        return $resultRedirect;
    }

    private function getCollection(string $entity, ?int $from, ?int $to)
    {
        $collectionFactory = null;

        if ($entity === static::ENTITY_CUSTOMERS) {
            $collectionFactory = $this->customerCollectionFactory;
        } else if ($entity === static::ENTITY_ORDERS) {
            $collectionFactory = $this->orderCollectionFactory;
        } else {
            throw new \Exception(sprintf('Invalid resource entity %s', $entity));
        }

        /** @var AbstractDb $collection */
        $collection = $collectionFactory->create();
        $select = $collection->getSelect();
        if (!is_null($from)) {
            $select->where('entity_id >= ?', $from);
        }
        if (!is_null($to)) {
            $select->where('entity_id <= ?', $to);
        }
        $collection->setOrder('entity_id', SortOrder::SORT_DESC);
        $collection->setPageSize(static::PAGE_SIZE);

        return $collection;
    }

    private function placeItems(string $entity, array $items): int
    {
        $event = $this->eventRepository->create();

        if ($entity === static::ENTITY_CUSTOMERS) {
            return $event->placeCustomers($items);
        } else if ($entity === static::ENTITY_ORDERS) {
            return $event->placeOrders($items);
        } else {
            throw new \Exception(sprintf('Invalid resource entity %s', $entity));
        }
    }
}
