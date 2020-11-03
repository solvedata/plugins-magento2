<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Config\Source;

use Magento\Framework\Config\DataInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Events implements OptionSourceInterface
{
    /**
     * @var DataInterface
     */
    protected $dataConfig;

    /**
     * @param DataInterface $dataConfig
     */
    public function __construct(
        DataInterface $dataConfig
    ) {
        $this->dataConfig = $dataConfig;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        $result = [];
        $events = array_keys($this->dataConfig->get('solvedata_events'));

        foreach ($events as $event) {
            $result[] = [
                'value' => $event,
                'label' => $event,
            ];
        }

        return $result;
    }
}
