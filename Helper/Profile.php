<?php

declare(strict_types=1);

namespace SolveData\Events\Helper;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\HTTP\Adapter\Curl as CurlAdapter;
use Magento\Framework\Registry;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Logger;
use SolveData\Events\Model\ProfileRepository;

class Profile extends CurlAdapter
{
    const QUERY = <<<'GRAPHQL'
query($email: Email) {
    profile(email: $email) {
        id
    }
}
GRAPHQL;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        Logger $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Get profile sid by email
     *
     * @param string $email
     * @param int|null $websiteId
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getSidByEmail(string $email, int $websiteId): string
    {
        $response = $this->request($email);
        $body = json_decode($response['response']['body'], true);

        // TODO handle non-existant profile
        return $body['data']['profile']['id'];
    }

    protected function request(string $email): array
    {        
        $requestData = [
            'url'     => $this->config->getAPIUrl(null),
            'options' => [
                CURLOPT_HTTPHEADER => [
                    sprintf(
                        'Authorization: Basic %s',
                        base64_encode(sprintf('%s:%s',
                            $this->config->getAPIKey(null),
                            $this->config->getPassword(null)
                        ))
                    ),
                ],
                CURLOPT_POSTFIELDS => http_build_query([
                    'query'     => self::QUERY,
                    'variables' => json_encode(["email" => $email]),
                ]),
            ],
        ];

        try {
            $this->logger->debug(sprintf('Start request for email %s', $email));
            $this->logger->debug(sprintf('Send request: %s', json_encode($requestData)));
            $this->write(
                \Zend_Http_Client::POST,
                $requestData['url'],
                '1.1',
                $requestData['options'][CURLOPT_HTTPHEADER],
                $requestData['options'][CURLOPT_POSTFIELDS]
            );
            $response = $this->read();
            $requestResult = [
                'request' => [
                    'url'        => $requestData['url'],
                    'parameters' => urldecode($requestData['options'][CURLOPT_POSTFIELDS])
                ],
                'response' => [
                    'body' => \Zend_Http_Response::extractBody($response),
                    'code' => \Zend_Http_Response::extractCode($response),
                ],
            ];
            $this->logger->debug(sprintf('Result of request: %s', json_encode($requestResult)));
            $this->close();

            $this->logger->debug(sprintf(
                'Request result: %s',
                json_encode($requestResult)
            ));

            return $requestResult;
        } catch (\Throwable $t) {
            $this->logger->critical($t);
            throw $t;
        }
    }

    /**
     * Save profile sid from Solve service
     *
     * @param string $email
     * @param string $sid
     * @param int $websiteId
     *
     * @return Profile
     *
     * @throws AlreadyExistsException
     */
    public function saveSidByEmail(string $email, string $sid, int $websiteId): Profile
    {
        // no-op
        return $this;
    }
}
