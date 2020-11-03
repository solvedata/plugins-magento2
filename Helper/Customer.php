<?php

declare(strict_types=1);

namespace SolveData\Events\Helper;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerExtensionFactory;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use SolveData\Events\Model\Session as SolveDataSession;

class Customer
{
    /**
     * @var CustomerExtensionFactory
     */
    protected $customerExtensionFactory;

    /**
     * @var CustomerMetadataInterface
     */
    protected $customerMetadata;

    /**
     * @var SolveDataSession
     */
    protected $solveDataSession;

    /**
     * @param CustomerExtensionFactory $customerExtensionFactory
     * @param CustomerMetadataInterface $customerMetadata
     * @param SolveDataSession $solveDataSession
     */
    public function __construct(
        CustomerExtensionFactory $customerExtensionFactory,
        CustomerMetadataInterface $customerMetadata,
        SolveDataSession $solveDataSession
    ) {
        $this->customerExtensionFactory = $customerExtensionFactory;
        $this->customerMetadata = $customerMetadata;
        $this->solveDataSession = $solveDataSession;
    }

    /**
     * Get profile identify flag
     *
     * @param bool $unset
     *
     * @return bool
     */
    public function getProfileIdentifyFlag($unset = false): bool
    {
        $value = (bool)$this->solveDataSession->getProfileIdentify();
        if ($unset) {
            $this->solveDataSession->unsProfileIdentify();
        }

        return $value;
    }

    /**
     * Set profile identify flag
     *
     * @return $this
     */
    public function setProfileIdentifyFlag(): Customer
    {
        $this->solveDataSession->setProfileIdentify(true);

        return $this;
    }

    /**
     * Prepare customer gender
     *
     * @param CustomerInterface|CustomerModel $customer
     *
     * @return Customer
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareCustomerGender($customer): Customer
    {
        $gender = $customer->getGender();
        if (empty($gender)) {
            return $this;
        }
        $genderMetadataOptions = $this->customerMetadata
            ->getAttributeMetadata('gender')
            ->getOptions();

        if (empty($genderMetadataOptions[$gender])) {
            return $this;
        }

        if ($customer instanceof CustomerInterface) {
            /** @var CustomerExtensionInterface $customerExtension */
            $customerExtension = $customer->getExtensionAttributes();
            if (empty($customerExtension)) {
                $customerExtension = $this->customerExtensionFactory->create();
            }
            $customerExtension->setGender($genderMetadataOptions[$gender]->getLabel());

            return $this;
        }
        if ($customer instanceof CustomerModel) {
            $customer->setGender($genderMetadataOptions[$gender]->getLabel());
        }

        return $this;
    }
}
