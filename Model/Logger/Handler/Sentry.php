<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Logger\Handler;

use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Sentry\Breadcrumb;
use Sentry\State\HubInterface;
use Sentry\Monolog\Handler as SentryHandler;
use SolveData\Events\Model\Logger\SentryHubManager;

class Sentry extends AbstractHandler
{
    private $sentryHubManager;

    public function __construct(
        SentryHubManager $sentryHubManager
    ) {
        $this->sentryHubManager = $sentryHubManager;
        parent::__construct(Logger::DEBUG);
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        $hub = $this->sentryHubManager->getHub();
        return !is_null($hub);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        $hub = $this->sentryHubManager->getHub();
        if (is_null($hub)) {
            return false;
        }

        $level = $record['level'];
        if ($level < Logger::WARNING) {
            $this->logBreadcrumb($hub, $record);
        } else {
            $this->logError($hub, $record);
        }

        return false;
    }

    private function logError(HubInterface $hub, array $record): void
    {
        $handler = new SentryHandler($hub, Logger::WARNING);
        $handler->handle($record);
    }

    private function logBreadcrumb(HubInterface $hub, array $record): void
    {
        $message = $record['message'];
        unset($record['message']);

        $breadcrumb = new Breadcrumb(
            Breadcrumb::LEVEL_INFO,
            Breadcrumb::TYPE_DEFAULT,
            "log",
            $message,
            $record
        );
        $hub->addBreadcrumb($breadcrumb);
    }
}
