<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use SolveData\Events\Model\EventRepository;

class MassImport extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * @var EventRepository
     */
    protected $eventRepository;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param EventRepository $eventRepository
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        EventRepository $eventRepository,
        Filter $filter
    ) {
        parent::__construct($context, $filter);

        $this->collectionFactory = $collectionFactory;
        $this->eventRepository = $eventRepository;
    }

    /**
     * Import selected orders to Solve
     *
     * @param AbstractCollection $collection
     *
     * @return Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $event = $this->eventRepository->create();
        $countImportOrder = $event->placeOrders($collection->getItems());
        $countNonImportOrder = $collection->count() - $countImportOrder;

        if ($countNonImportOrder && $countImportOrder) {
            $this->messageManager->addErrorMessage(
                sprintf('%d order(s) were not added to queue for import to Solve.', $countNonImportOrder)
            );
        } else if ($countNonImportOrder) {
            $this->messageManager->addErrorMessage('No order(s) were added to queue for import to Solve.');
        }

        if ($countImportOrder) {
            $this->messageManager->addSuccessMessage(
                sprintf('You have added to queue %d order(s) for import to Solve.', $countImportOrder)
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
    }
}
