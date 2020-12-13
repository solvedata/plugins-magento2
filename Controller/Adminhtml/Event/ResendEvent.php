<?php

declare(strict_types=1);

namespace SolveData\Events\Controller\Adminhtml\Event;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use SolveData\Events\Model\Logger;
use SolveData\Events\Model\Event;
use SolveData\Events\Model\ResourceModel\Event\Collection;
use SolveData\Events\Model\ResourceModel\Event as EventModel;

class ResendEvent extends Action
{
    protected $collection;
    protected $eventModel;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        Collection $collection,
        EventModel $eventModel
    ) {
        parent::__construct($context);

        $this->collection = $collection;
        $this->eventModel = $eventModel;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $selectedIds = [];
        if (array_key_exists('selected', $params)) {
            $selectedIds = $params['selected'];
        }

        if (array_key_exists('excluded', $params)) {
            if ($params['excluded'] === 'false') {
                $this->messageManager->addErrorMessage('Resending all events is currently not supported');

                return $this->createRedirection();
            } else {
                $selectedIds = array_diff($selectedIds, $params['excluded']);
            }
        }

        $events = $this->collection->addFieldToFilter('id', ['in' => $selectedIds]);

        $newEvents = [];
        foreach ($events as $event) {
            $newEvents[] = [
                'name'                  => $event['name'],
                'status'                => Event::STATUS_NEW,
                'payload'               => $event['payload'],
                'affected_entity_id'    => $event['affected_entity_id'],
                'affected_increment_id' => $event['affected_increment_id'] ?? null,
                'store_id'              => $event['store_id'],
            ];
        }

        $createdEventsCount = 0;
        if (!empty($newEvents)) {
            $createdEventsCount = $this->eventModel->createEvents($newEvents);
        }

        $this->messageManager->addSuccessMessage(
            sprintf('You have added to queue %d event(s) to resend to Solve Data.', $createdEventsCount)
        );

        return $this->createRedirection();
    }

    private function createRedirection()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('solvedata_events/event/index');
        return $resultRedirect;
    }
}
