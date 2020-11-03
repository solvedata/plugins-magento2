<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation;

use Magento\Framework\DataObject;
use SolveData\Events\Api\Event\Transport\Adapter\GraphQL\MutationInterface;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;

abstract class MutationAbstract extends DataObject implements MutationInterface
{
    const QUERY = null;

    /**
     * @var PayloadConverter
     */
    protected $payloadConverter;

    /**
     * @param PayloadConverter $payloadConverter
     * @param array $data
     */
    public function __construct(
        PayloadConverter $payloadConverter,
        array $data = []
    ) {
        $this->payloadConverter = $payloadConverter;
        parent::__construct($data);
    }

    /**
     * Set event data
     *
     * @param array $event
     *
     * @return MutationAbstract
     */
    public function setEvent(array $event): MutationAbstract
    {
        $event['payload'] = json_decode($event['payload'], true);

        return parent::setEvent($event);
    }

    /**
     * Mutation is allowed
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        return true;
    }

    /**
     * Get query for GraphQL request
     *
     * @return string
     */
    public function getQuery(): string
    {
        return static::QUERY;
    }

    /**
     * Get variables for GraphQL request
     *
     * @return array
     *
     * @throws \Exception
     */
    abstract public function getVariables(): array;
}
