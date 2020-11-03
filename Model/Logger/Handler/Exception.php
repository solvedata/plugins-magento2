<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\Exception as ExceptionHandler;

class Exception extends ExceptionHandler
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/solvedata_events_exception.log';
}
