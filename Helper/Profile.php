<?php

declare(strict_types=1);

namespace SolveData\Events\Helper;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Registry;
use SolveData\Events\Model\ProfileRepository;

class Profile
{
    const REGISTRY_PREFIX = 'solvedata_profile';

    /**
     * @var ProfileRepository
     */
    protected $profileRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param ProfileRepository $profileRepository
     * @param Registry $registry
     */
    public function __construct(
        ProfileRepository $profileRepository,
        Registry $registry
    ) {
        $this->profileRepository = $profileRepository;
        $this->registry = $registry;
    }

    /**
     * Get registry name
     *
     * @param $name
     *
     * @return string
     */
    protected function getRegistryKey($name): string
    {
        return sprintf('%s_%s', self::REGISTRY_PREFIX, $name);
    }

    /**
     * Get profile registry value
     *
     * @param string $name
     *
     * @return mixed|null
     */
    protected function getRegistry(string $name)
    {
        return $this->registry->registry($this->getRegistryKey($name));
    }

    /**
     * Set registry value
     *
     * @param string $name
     * @param $value
     *
     * @return Profile
     */
    protected function setRegistry(string $name, $value): Profile
    {
        $this->registry->register(
            $this->getRegistryKey($name),
            $value
        );

        return $this;
    }

    /**
     * Get profile id by email
     *
     * @param string $email
     * @param int|null $websiteId
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getProfileIdByEmail(string $email, int $websiteId): string
    {
        $id = $this->getRegistry(sprintf('%s_%s', $email, $websiteId));
        if (!empty($id)) {
            return $id;
        }
        $profile = $this->profileRepository->create();
        $id = $profile->getProfileIdByEmail($email, $websiteId);
        if (empty($id)) {
            throw new \Exception(sprintf(
                'SolveData Profile id is empty for "%s" email in "%d" website',
                $email,
                $websiteId
            ));
        }

        return $id;
    }

    /**
     * Save profile id from Solve service
     *
     * @param string $email
     * @param string $id
     * @param int $websiteId
     *
     * @return Profile
     *
     * @throws AlreadyExistsException
     */
    public function saveProfileIdByEmail(string $email, string $id, int $websiteId): Profile
    {
        if (!empty($this->getRegistry(sprintf('%s_%s', $email, $websiteId)))) {
            return $this;
        }
        $profile = $this->profileRepository->create();
        if ($profile->isExistByEmail($email, $websiteId)) {
            return $this;
        }

        $profile->setEmail($email)
            ->setSid($id)
            ->setWebsiteId($websiteId);
        $this->profileRepository->save($profile);
        $this->setRegistry(sprintf('%s_%s', $email, $websiteId), $id);

        return $this;
    }
}
