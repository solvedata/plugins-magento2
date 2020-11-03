<?php

namespace SolveData\Events\Api;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Model\Event;

interface EventRepositoryInterface
{
    /**
     * Return Event object
     *
     * @return Event
     */
    public function create(): Event;

    /**
     * Loads a specified event.
     *
     * @param int $entityId
     *
     * @return Event
     *
     * @throws NoSuchEntityException
     */
    public function get(int $entityId): Event;

    /**
     * Save event
     *
     * @param Event $entity
     *
     * @return EventRepositoryInterface
     *
     * @throws AlreadyExistsException
     */
    public function save(Event $entity): EventRepositoryInterface;

    /**
     * Delete event
     *
     * @param Event $entity
     *
     * @return EventRepositoryInterface
     */
    public function delete(Event $entity): EventRepositoryInterface;
}
