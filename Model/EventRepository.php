<?php

declare(strict_types=1);

namespace SolveData\Events\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Api\EventRepositoryInterface;
use SolveData\Events\Model\EventFactory;
use SolveData\Events\Model\ResourceModel\Event as EventResourceModel;

class EventRepository implements EventRepositoryInterface
{
    /**
     * @var EventFactory
     */
    protected $eventFactory;

    /**
     * @var EventResourceModel
     */
    protected $eventResourceModel;

    public function __construct(
        EventFactory $eventFactory,
        EventResourceModel $eventResourceModel
    ) {
        $this->eventFactory = $eventFactory;
        $this->eventResourceModel = $eventResourceModel;
    }

    /**
     * Return Event object
     *
     * @return Event
     */
    public function create(): Event
    {
        return $this->eventFactory->create();
    }

    /**
     * Loads a specified event.
     *
     * @param int $entityId
     *
     * @return Event
     *
     * @throws NoSuchEntityException
     */
    public function get(int $entityId): Event
    {
        $entity = $this->eventFactory->create();
        $this->eventResourceModel->load($entity, $entityId);
        if (empty($entity->getEntityId())) {
            throw new NoSuchEntityException(sprintf('Unable to find event with ID "%s"', $entityId));
        }

        return $entity;
    }

    /**
     * Save event
     *
     * @param Event $entity
     *
     * @return EventRepositoryInterface
     *
     * @throws \Exception
     * @throws AlreadyExistsException
     */
    public function save(Event $entity): EventRepositoryInterface
    {
        $this->eventResourceModel->save($entity);

        return $this;
    }

    /**
     * Delete event
     *
     * @param Event $entity
     *
     * @return EventRepositoryInterface
     *
     * @throws \Exception
     */
    public function delete(Event $entity): EventRepositoryInterface
    {
        $this->eventResourceModel->delete($entity);

        return $this;
    }
}
