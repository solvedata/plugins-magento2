<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Config\SolveDataEvents;

use Magento\Framework\Config\ConverterInterface;

class Converter implements ConverterInterface
{
    /**
     * To convert templates from config file to array.
     *
     * @param \DOMDocument $source
     *
     * @return array
     */
    public function convert($source): array
    {
        $solveDataEvents = $source->getElementsByTagName('solvedata_events');

        $output = [];
        foreach ($solveDataEvents as $solveDataEvent) {
            foreach ($solveDataEvent->childNodes as $eventNode) {
                if ($eventNode->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                $eventName = $eventNode->getAttribute('name');
                $output[$eventName] = $this->getMutationsDataByEventNode($eventNode);
            }
        }

        return ['solvedata_events' => $output];
    }

    /**
     * Get adapters data by event node
     *
     * @param $eventNode
     *
     * @return array
     */
    protected function getMutationsDataByEventNode($eventNode): array
    {
        $output = [];
        foreach ($eventNode->childNodes as $mutationNode) {
            if ($mutationNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $order = $mutationNode->getAttribute('order');
            if (!empty($order) && !is_numeric($order)) {
                continue;
            }
            $output[] = [
                'order' => !empty($order) ? (int)$order : 0,
                'class' => $mutationNode->getAttribute('class'),
            ];
        }

        return $output;
    }
}
