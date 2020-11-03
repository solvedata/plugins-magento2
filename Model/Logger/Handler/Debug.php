<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Logger\Handler;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use SolveData\Events\Model\Config;

class Debug extends Base
{
    protected $config;

    protected $fileName = '/var/log/solvedata_events_debug.log';

    public function __construct(
        DriverInterface $filesystem,
        Config $config,
        $filePath = null,
        $fileName = null
    ) {
        $this->config = $config;
        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return parent::isHandling($record) && $this->config->getDebug();
    }
}
