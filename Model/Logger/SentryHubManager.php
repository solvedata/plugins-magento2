<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Logger;

use Jean85\PrettyVersions;
use Nyholm\Psr7\Factory\Psr17Factory;
use Sentry\Breadcrumb;
use Sentry\Client;
use Sentry\ClientBuilder;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\Transport\DefaultTransportFactory;
use Sentry\Transport\TransportInterface;
use SolveData\Events\Model\Config;

class SentryHubManager
{
    private $config;
    
    private $last_dsn;
    private $sentry_hub;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;

        $this->last_dsn = null;
        $this->sentry_hub = null;
    }

    public function getHub(): ?HubInterface
    {
        try {
            $dsn = $this->config->getSentryDsn();
            if (empty($dsn)) {
                return null;
            }
    
            $hasValidDsn = !empty($dsn) && filter_var($dsn, FILTER_VALIDATE_URL);
            $hasDsnChanged = $this->last_dsn !== $dsn;
    
            if ($hasValidDsn && (empty($this->last_dsn) || $hasDsnChanged)) {
                // Set max_value_length, otherwise Sentry defaults to 1kib
                // which is not even enough for the stacktrace.
                $client = ClientBuilder::create(['dsn' => $dsn, 'max_value_length' => 8 * 1024])
                    ->setTransportFactory($this->createTransportFactory())
                    ->getClient();
    
                $this->sentry_hub = new Hub($client);
                $this->last_dsn = $dsn;
            }
    
            return $this->sentry_hub;
        } catch (\Throwable $t) {
            // Fail silently if there is an unexpected error creating the Sentry client/hub.
            // This is to avoid a theoretical situation where the error handling code recursively throws errors.
            return null;
        }
    }

    private function createTransportFactory(): DefaultTransportFactory
    {
        $psr17Factory = new Psr17Factory();
        $httpClient = null;
        $logger = null;

        $httpClientFactory = new HttpClientFactory(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $httpClient,
            Client::SDK_IDENTIFIER,
            PrettyVersions::getVersion('sentry/sentry')->getPrettyVersion()
        );

        return new DefaultTransportFactory(
            $psr17Factory,
            $psr17Factory,
            $httpClientFactory,
            $logger
        );
    }
}
