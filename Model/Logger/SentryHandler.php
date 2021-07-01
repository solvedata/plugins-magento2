<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Sentry\State\Scope;


// Adapted from https://github.com/getsentry/sentry-php/blob/3.0.0-beta1/src/Monolog/Handler.php
final class SentryHandler extends AbstractProcessingHandler
{
    /**
     * @var HubInterface
     */
    private $hub;

    /**
     * Constructor.
     *
     * @param HubInterface $hub    The hub to which errors are reported
     * @param int|string   $level  The minimum logging level at which this
     *                             handler will be triggered
     * @param bool         $bubble Whether the messages that are handled can
     *                             bubble up the stack or not
     */
    public function __construct(HubInterface $hub, $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->hub = $hub;

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $payload = [
            'level' => self::getSeverityFromLevel($record['level']),
            'message' => $record['message'],
            'logger' => 'monolog.' . $record['channel'],
        ];

        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Throwable) {
            $payload['exception'] = $record['context']['exception'];

            // Deduplicate the exception by removing it from the record context
            unset($record['context']['exception']);
        }

        $this->hub->withScope(function (Scope $scope) use ($record, $payload): void {
            $scope->setExtra('monolog.channel', $record['channel']);
            $scope->setExtra('monolog.level', $record['level_name']);

            if (isset($record['context'])) {
                $scope->setExtras($record['context']);
            }

            $this->hub->captureEvent($payload);
        });
    }

    /**
     * Translates the Monolog level into the Sentry severity.
     *
     * @param int $level The Monolog log level
     */
    private static function getSeverityFromLevel(int $level): Severity
    {
        switch ($level) {
            case Logger::DEBUG:
                return Severity::debug();
            case Logger::WARNING:
                return Severity::warning();
            case Logger::ERROR:
                return Severity::error();
            case Logger::CRITICAL:
            case Logger::ALERT:
            case Logger::EMERGENCY:
                return Severity::fatal();
            case Logger::INFO:
            case Logger::NOTICE:
            default:
                return Severity::info();
        }
    }
}
