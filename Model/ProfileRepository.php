<?php

declare(strict_types=1);

namespace SolveData\Events\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Api\ProfileRepositoryInterface;
use SolveData\Events\Model\ResourceModel\Profile as ProfileResourceModel;

class ProfileRepository implements ProfileRepositoryInterface
{
    /**
     * @var ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var ProfileResourceModel
     */
    protected $profileResourceModel;

    public function __construct(
        ProfileFactory $profileFactory,
        ProfileResourceModel $profileResourceModel
    ) {
        $this->profileFactory = $profileFactory;
        $this->profileResourceModel = $profileResourceModel;
    }

    /**
     * Return Event object
     *
     * @return Profile
     */
    public function create(): Profile
    {
        return $this->profileFactory->create();
    }

    /**
     * Loads a specified profile.
     *
     * @param int $entityId
     *
     * @return Profile
     *
     * @throws NoSuchEntityException
     */
    public function get(int $entityId): Profile
    {
        $entity = $this->profileFactory->create();
        $this->profileResourceModel->load($entity, $entityId);
        if (empty($entity->getEntityId())) {
            throw new NoSuchEntityException(sprintf('Unable to find profile with ID "%s"', $entityId));
        }

        return $entity;
    }

    /**
     * Save profile
     *
     * @param Profile $entity
     *
     * @return ProfileRepositoryInterface
     *
     * @throws \Exception
     * @throws AlreadyExistsException
     */
    public function save(Profile $entity): ProfileRepositoryInterface
    {
        $this->profileResourceModel->save($entity);

        return $this;
    }

    /**
     * Delete profile
     *
     * @param Profile $entity
     *
     * @return ProfileRepositoryInterface
     *
     * @throws \Exception
     */
    public function delete(Profile $entity): ProfileRepositoryInterface
    {
        $this->profileResourceModel->delete($entity);

        return $this;
    }
}
