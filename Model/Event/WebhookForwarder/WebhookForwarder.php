<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\WebhookForwarder;

use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Module\ModuleList;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Logger;
use SolveData\Events\Model\ResourceModel\Event;

class WebhookForwarder extends Curl
{
    private $config;
    private $logger;
    private $mapper;
    private $moduleList;

    public function __construct(
        Config $config,
        Logger $logger,
        WebhookForwarderMapper $mapper,
        ModuleList $moduleList
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->mapper = $mapper;
        $this->moduleList = $moduleList;
    }

    public function process(array $events): void
    {
        if (!$this->config->isWebhookForwardingEnabled()) {
            return;
        }

        $webhookPayload = $this->mapper->map($events);
        $requestId = $this->requestId($events);
        $this->post($requestId, $webhookPayload);
    }

    private function post(string $requestId, array $payload): array
    {
        try {
            $url = $this->config->getWebhookForwardingUrl();

            $this->write(
                \Zend_Http_Client::POST,
                $url,
                '1.1',
                [
                    'Content-Type: application/json',
                    sprintf('x-request-id: %s', $requestId),
                    sprintf('x-client-version: solvedata/plugins-magento2 %s', $this->getExtensionVersion()),
                ],
                json_encode($payload)
            );
            $response = $this->read();

            // 'body' => \Zend_Http_Response::extractBody($response),
            // 'code' => \Zend_Http_Response::extractCode($response),
            
            $this->close();
        } catch (\Throwable $t) {
            $this->logger->error($t);
        }

        return [];
    }

    private function requestId(array $events): string
    {
        $eventIds = array_column($events, Event::ENTITY_ID);

        $minEventId = min($eventIds);
        $maxEventId = max($eventIds);

        // Request IDs need to be more than 20 characters long.
        // uniqid("", true) returns at least a 23 character long string.
        return uniqid("$minEventId,$maxEventId,", true);
    }

    /**
     * Returns the current Magento plugin's version.
     *
     * @return string
     */
    private function getExtensionVersion(): string
    {
        try {
            return $this->moduleList->getOne('SolveData_Events')['setup_version'];
        } catch (\Throwable $t) {
            $this->logger->error($t);
            return 'unknown';
        }
    }
}
