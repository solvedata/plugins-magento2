<?php

namespace SolveData\Events\Api;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Model\Profile;

interface ProfileRepositoryInterface
{
    /**
     * Return Profile object
     *
     * @return Profile
     */
    public function create(): Profile;

    /**
     * Loads a specified profile
     *
     * @param int $entityId
     *
     * @return Profile
     *
     * @throws NoSuchEntityException
     */
    public function get(int $entityId): Profile;

    /**
     * Save profile
     *
     * @param Profile $entity
     *
     * @return ProfileRepositoryInterface
     *
     * @throws AlreadyExistsException
     */
    public function save(Profile $entity): ProfileRepositoryInterface;

    /**
     * Delete profile
     *
     * @param Profile $entity
     *
     * @return ProfileRepositoryInterface
     */
    public function delete(Profile $entity): ProfileRepositoryInterface;
}
