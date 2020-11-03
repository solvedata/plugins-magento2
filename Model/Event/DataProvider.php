<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;
use SolveData\Events\Model\Adminhtml\Source\Event\Store as EventStoreSource;
use SolveData\Events\Model\Event;
use SolveData\Events\Model\ResourceModel\Event\Collection;
use SolveData\Events\Model\ResourceModel\Event\CollectionFactory;

/**
 * Class DataProvider
 */
class DataProvider extends ModifierPoolDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var EventStoreSource
     */
    protected $eventStoreSource;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $eventCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param EventStoreSource $eventStoreSource
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $eventCollectionFactory,
        DataPersistorInterface $dataPersistor,
        EventStoreSource $eventStoreSource,
        array $meta = [],
        array $data = [],
        PoolInterface $pool = null
    ) {
        $this->collection = $eventCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->eventStoreSource = $eventStoreSource;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data,
            $pool
        );
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $storesArray = array_column(
            $this->eventStoreSource->toOptionArray(),
            'label',
            'value'
        );

        $events = $this->collection->getItems();
        /** @var $event Event */
        foreach ($events as $event) {
            $event->setStatus(Event::STATUSES[$event->getStatus()]);
            $event->setStoreId($storesArray[$event->getStoreId()]);
            $this->loadedData[$event->getId()] = $event->getData();
        }

        $data = $this->dataPersistor->get('event');
        if (!empty($data)) {
            $event = $this->collection->getNewEmptyItem();
            $event->setData($data);
            $this->loadedData[$event->getId()] = $event->getData();
            $this->dataPersistor->clear('event');
        }

        return $this->loadedData;
    }
}
