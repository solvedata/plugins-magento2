<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Controller\Adminhtml\Index\AbstractMassAction;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Ui\Component\MassAction\Filter;
use SolveData\Events\Model\EventRepository;

class MassImport extends AbstractMassAction
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @param Context $context
     * @param EventRepository $eventRepository
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(
        Context $context,
        EventRepository $eventRepository,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CustomerRepositoryInterface $customerRepository,
        SubscriberFactory $subscriberFactory
    ) {
        parent::__construct($context, $filter, $collectionFactory);

        $this->customerRepository = $customerRepository;
        $this->eventRepository = $eventRepository;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * Import selected customers to Solve
     *
     * @param AbstractCollection $collection
     *
     * @return Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $event = $this->eventRepository->create();
        $countImportCustomer = $event->placeCustomers($collection->getItems());
        $countNonImportCustomer = $collection->count() - $countImportCustomer;

        if ($countNonImportCustomer && $countImportCustomer) {
            $this->messageManager->addErrorMessage(
                sprintf('%d customer(s) were not added to queue for import to Solve.', $countNonImportCustomer)
            );
        } else if ($countNonImportCustomer) {
            $this->messageManager->addErrorMessage('No customer(s) were added to queue for import to Solve.');
        }

        if ($countImportCustomer) {
            $this->messageManager->addSuccessMessage(
                sprintf('You have added to queue %d customer(s) for import to Solve.', $countImportCustomer)
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('customer/index');

        return $resultRedirect;
    }
}
