<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\RegisterHandler;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Helper\Customer as CustomerHelper;
use SolveData\Events\Helper\Event as EventHelper;
use SolveData\Events\Model\Event;
use SolveData\Events\Model\EventRepository;
use SolveData\Events\Model\Logger;
use SolveData\Events\Model\Config;

/**
 * Class EventAbstract
 * @package SolveData\Events\Model\Event\RegisterHandler
 *
 * @method string getEventName()
 * @method EventAbstract setEventName(string $name)
 * @method int getAffectedEntityId()
 * @method EventAbstract setAffectedEntityId(int $id)
 * @method string getAffectedIncrementId()
 * @method EventAbstract setAffectedIncrementId(string $incrementId)
 * @method EventAbstract getPayload()
 * @method EventAbstract setPayload(string|array $value)
 */
abstract class EventAbstract extends DataObject
{
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
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Config $config
     * @param Converter $converter
     * @param CustomerHelper $customerHelper
     * @param EventHelper $eventHelper
     * @param EventRepository $eventRepository
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Config $config,
        Converter $converter,
        CustomerHelper $customerHelper,
        EventHelper $eventHelper,
        EventRepository $eventRepository,
        Logger $logger,
        array $data = []
    ) {
        $this->config = $config;
        $this->converter = $converter;
        $this->customerHelper = $customerHelper;
        $this->eventHelper = $eventHelper;
        $this->eventRepository = $eventRepository;
        $this->logger = $logger;

        parent::__construct($data);
    }

    /**
     * Get observer data
     *
     * @param Observer $observer
     *
     * @return EventAbstract
     */
    abstract public function prepareData(Observer $observer): EventAbstract;

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
        return $this->config->isEnabledEvent($this->getEventName());
    }

    /**
     * Validate event
     *
     * @return EventAbstract
     */
    protected function validate(): EventAbstract
    {
        if (empty($this->getPayload())) {
            throw new \InvalidArgumentException(sprintf('payload is empty in %s', $this->getEventName()));
        }

        if (empty($this->getAffectedEntityId())) {
            throw new \InvalidArgumentException(sprintf('affected_entity_id is empty in %s', $this->getEventName()));
        }

        return $this;
    }

    /**
     * Before process event method
     *
     * @param Observer $observer
     *
     * @return EventAbstract
     */
    protected function beforeProcess(Observer $observer): EventAbstract
    {
        $this->setEventName($observer->getEvent()->getName());

        return $this;
    }

    /**
     * After process event method
     *
     * @return EventAbstract
     */
    protected function afterProcess(): EventAbstract
    {
        return $this;
    }

    /**
     * Prepared observer data and send to solve data events
     *
     * @return EventAbstract
     *
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    protected function registerEvent(): EventAbstract
    {
        $payload = $this->getPayload();
        $payload = is_array($payload) ? $payload : [$payload];
        $serializedPayload = json_encode($this->converter->convert($payload));

        $data = [
            'name'                  => $this->getEventName(),
            'status'                => Event::STATUS_NEW,
            'payload'               => $serializedPayload,
            'affected_entity_id'    => $this->getAffectedEntityId(),
            'affected_increment_id' => $this->getAffectedIncrementId(),
            'store_id'              => $this->eventHelper->getStore()->getId()
        ];
        $event = $this->eventRepository->create();
        $event->setData($data);

        $this->eventRepository->save($event);
        $this->logger->debug(sprintf(
            'Register event "%s": %s',
            $this->getEventName(),
            json_encode($event->getData())
        ));

        return $this;
    }

    /**
     * Process event
     *
     * @param Observer $observer
     *
     * @return bool
     *
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     */
    public function process(Observer $observer): bool
    {
        $this->logger->debug(sprintf(
            'Start process event "%s"',
            $observer->getEvent()->getName()
        ));
        $this->beforeProcess($observer);
        if (!$this->isAllowed($observer)) {
            $this->logger->debug(sprintf(
                'Process event "%s" is not allowed',
                $observer->getEvent()->getName()
            ));

            return false;
        }

        $this->prepareData($observer)
            ->validate()
            ->registerEvent()
            ->afterProcess();

        $this->logger->debug(sprintf(
            'Finish process event "%s"',
            $observer->getEvent()->getName()
        ));

        // Ensure we unset all mutable data to ensure their is no
        // conflict with subsequent events processed by the same EventAbstract instance.
        $this->unsetData();

        return true;
    }
}