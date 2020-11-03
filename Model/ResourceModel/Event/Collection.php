<?php

namespace SolveData\Events\Model\ResourceModel\Event;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SolveData\Events\Model\Event as Model;
use SolveData\Events\Model\ResourceModel\Event as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
