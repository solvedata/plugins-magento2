<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter;

use Magento\Framework\HTTP\Adapter\Curl;
use SolveData\Events\Model\Config;

class Webhook extends Curl
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function sendBulk(array $events): array
    {
        try {
            $url = $this->config->getWebhookForwardingUrl();
            $requestBody = json_encode([
                'events' => $events
            ]);

            $this->write(
                \Zend_Http_Client::POST,
                $url,
                '1.1',
                ['Content-Type: application/json'],
                $requestBody
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
}
