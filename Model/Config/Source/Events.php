<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use SolveData\Events\Model\Config\EventMutationConfig;

class Events implements OptionSourceInterface
{
    /**
     * @var EventMutationConfig
     */
    protected $eventMutationConfig;

    /**
     * @param EventMutationConfig $eventMutationConfig
     */
    public function __construct(
        EventMutationConfig $eventMutationConfig
    ) {
        $this->eventMutationConfig = $eventMutationConfig;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray(): array
    {
        $result = [];
        $events = array_keys($this->eventConfigProvider->getMutations());

        foreach ($events as $event) {
            $result[] = [
                'value' => $event,
                'label' => $event,
            ];
        }

        return $result;
    }
}
