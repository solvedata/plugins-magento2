<?php

namespace SolveData\Events\Model\ResourceModel\Profile;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SolveData\Events\Model\Profile as Model;
use SolveData\Events\Model\ResourceModel\Profile as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
