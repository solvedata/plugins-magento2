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
    const REGISTRY_PREFIX = 'solvedata_profile';
    const PROFILE_QUERY = <<<'GRAPHQL'
query($email: Email) {
    profile(email: $email) {
        id
    }
}
GRAPHQL;

    /**
     * @var ProfileRepository
     */
    protected $profileRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @param ProfileRepository $profileRepository
     * @param Registry $registry
     */
    public function __construct(
        Config $config,
        Logger $logger,
        ProfileRepository $profileRepository,
        Registry $registry
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->profileRepository = $profileRepository;
        $this->registry = $registry;
    }

    /**
     * Get registry name
     *
     * @param $name
     *
     * @return string
     */
    protected function getRegistryKey($name): string
    {
        return sprintf('%s_%s', self::REGISTRY_PREFIX, $name);
    }

    /**
     * Get profile registry value
     *
     * @param string $name
     *
     * @return mixed|null
     */
    protected function getRegistry(string $name)
    {
        return $this->registry->registry($this->getRegistryKey($name));
    }

    /**
     * Set registry value
     *
     * @param string $name
     * @param $value
     *
     * @return Profile
     */
    protected function setRegistry(string $name, $value): Profile
    {
        $this->registry->register(
            $this->getRegistryKey($name),
            $value
        );

        return $this;
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
        if ($this->config->isInOfflineSyncMode()) {
            return $this->lookupProfileInSolve($email);
        }

        $sid = $this->getRegistry(sprintf('%s_%s', $email, $websiteId));
        if (!empty($sid)) {
            return $sid;
        }
        $profile = $this->profileRepository->create();
        $sid = $profile->getSIdByEmail($email, $websiteId);
        if (empty($sid)) {
            throw new \Exception(sprintf(
                'SolveData Profile sid is empty for "%s" email in "%d" website',
                $email,
                $websiteId
            ));
        }

        return $sid;
    }

    protected function lookupProfileInSolve(string $email): string
    {
        $response = $this->profileGqlQuery($email);
        $body = json_decode($response['response']['body'], true);

        // TODO handle non-existant profile
        $profileId = $body['data']['profile']['id'];

        $this->logger->debug('Successfully retrieved profile_id for customer by querying the Solve Stack', ["email" => $email, "profile_id" => $profileId]);
        return $profileId;
    }

    protected function profileGqlQuery(string $email): array
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
                    'query'     => self::PROFILE_QUERY,
                    'variables' => json_encode(["email" => $email]),
                ]),
            ],
        ];

        try {
            $this->logger->debug('Start of query for retrieving profile_id for email', ["email" => $email]);
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
        if ($this->config->isInOfflineSyncMode()) {
            // no-op
            return $this;
        }

        if (!empty($this->getRegistry(sprintf('%s_%s', $email, $websiteId)))) {
            return $this;
        }
        $profile = $this->profileRepository->create();
        if ($profile->isExistByEmail($email, $websiteId)) {
            return $this;
        }

        $profile->setEmail($email)
            ->setSid($sid)
            ->setWebsiteId($websiteId);
        $this->profileRepository->save($profile);
        $this->setRegistry(sprintf('%s_%s', $email, $websiteId), $sid);

        return $this;
    }
}
