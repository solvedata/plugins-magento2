<?php

namespace SolveData\Events\Model\Adminhtml\Source\Event;

use Magento\Framework\Data\OptionSourceInterface;
use SolveData\Events\Model\Event;

class Status implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $options = [];

        foreach (Event::STATUSES as $key => $name) {
            $options[] = [
                'value' => $key,
                'label' => $name,
            ];
        }

        return $options;
    }
}
