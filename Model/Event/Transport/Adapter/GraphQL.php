<?php

declare (strict_types = 1);

namespace SolveData\Events\Model\Event\Transport\Adapter;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\DataInterface;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Model\StoreManagerInterface;
use SolveData\Events\Api\Event\Transport\Adapter\GraphQL\MutationInterface;
use SolveData\Events\Helper\Profile as ProfileHelper;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\QueueEvent;
use SolveData\Events\Model\Logger;
use SolveData\Events\Model\ResourceModel\Event as ResourceModel;

class GraphQL extends CurlAbstract
{
    const BATCH_SIZE = 30;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ProfileHelper
     */
    protected $profileHelper;

    /**
     * @var DataInterface $dataConfig
     */
    protected $dataConfig;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param Config $config
     * @param ProfileHelper $profileHelper
     * @param DataInterface $dataConfig
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param ModuleList $moduleList
     */
    public function __construct(
        Config $config,
        ProfileHelper $profileHelper,
        DataInterface $dataConfig,
        Logger $logger,
        StoreManagerInterface $storeManager,
        ModuleList $moduleList
    ) {
        $this->config = $config;
        $this->profileHelper = $profileHelper;
        $this->dataConfig = $dataConfig;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Send events by bulk
     *
     * @param array $events
     *
     * @return int[]
     */
    public function sendBulk(array $events): array
    {
        $results = [];
        foreach ($events as $event) {
            $results[$event[ResourceModel::ENTITY_ID]] = $this->send($event);
        }

        return $results;
    }

    /**
     * Send event
     *
     * @param array $event
     *
     * @return array
     */
    public function send(array $event)
    {
        if ($this->config->isGraphQLDisabled()) {
            return [['type' => 'graphql', 'disabled' => true]];
        }

        return $this->request($event);
    }

    /**
     * Send request
     *
     * @param array $event
     *
     * @return array
     */
    protected function request(array $event): array
    {
        $eventId = $event[ResourceModel::ENTITY_ID];
        $result = [];

        try {
            $this->logger->debug('Starting requests for event', ['event_id' => $eventId]);
            $orderedMutations = $this->getOrderedMutationsByEvent($event);
            foreach ($orderedMutations as $mutationClass) {
                $requestData = $this->prepareEventMutation($event, $mutationClass);
                if (empty($requestData)) {
                    continue;
                }
                $this->logger->debug('Sending GraphQL request', ['request' => $requestData]);
                $this->write(
                    \Zend_Http_Client::POST,
                    $requestData['url'],
                    '1.1',
                    $requestData['options'][CURLOPT_HTTPHEADER],
                    $requestData['options'][CURLOPT_POSTFIELDS]
                );
                $response = $this->read();
                $requestResult = [
                    'type' => 'graphql',
                    'request' => [
                        'url' => $requestData['url'],
                        'parameters' => urldecode($requestData['options'][CURLOPT_POSTFIELDS]),
                    ],
                    'response' => [
                        'body' => \Zend_Http_Response::extractBody($response),
                        'code' => \Zend_Http_Response::extractCode($response),
                    ],
                ];
                $this->logger->debug('Received response for GraphQL request', [
                    'event_id' => $eventId,
                    'result' => $requestResult
                ]);
                $this->close();
                $result[] = $requestResult;
                $this->afterRequest($event, $requestResult['response']['body']);
            }
        } catch (\Throwable $t) {            
            $this->logger->error('Unexpected error while sending GraphQL requests for event', [
                'exception' => $t,
                'event_id' => $eventId
            ]);
            $result[] = ['type' => 'graphql', 'exception' => "$t"];
        }
        $this->logger->debug('Finished sending requests for event', ['event_id' => $eventId]);

        return $result;
    }

    /**
     * Send mass request
     *
     * @param array $events
     *
     * @return int[]
     */
    protected function massRequest(array $events): array
    {
        try {
            $this->logger->debug(sprintf(
                'Start mass request for events: %s',
                json_encode(array_column($events, ResourceModel::ENTITY_ID))
            ));
            $results = [];
            $events = array_combine(array_column($events, ResourceModel::ENTITY_ID), $events);
            $orderedMutations = $this->getOrderedMutationsByEvents($events);
            if (empty($orderedMutations)) {
                return [];
            }
            foreach ($orderedMutations as $eventsMutations) {
                $requestsData = [];
                foreach ($eventsMutations as $eventId => $eventMutations) {
                    $requestsData = array_merge(
                        $requestsData,
                        $this->prepareEventMutations($events[$eventId], $eventMutations)
                    );
                }
                if (empty($requestsData)) {
                    $this->logger->debug('Skip request');
                    continue;
                }
                $chunksRequestData = array_chunk($requestsData, self::BATCH_SIZE);
                foreach ($chunksRequestData as $chunkRequestData) {
                    $this->logger->debug(sprintf('Send multi request: %s', json_encode($chunkRequestData)));
                    $multiRequestResult = $this->multiRequest(
                        array_column($chunkRequestData, 'url', 'id'),
                        array_column($chunkRequestData, 'options', 'id')
                    );
                    $this->logger->debug(sprintf('Result of multi request: %s', json_encode($multiRequestResult)));
                    foreach ($multiRequestResult as $key => $requestResult) {
                        $eventId = explode('_', $key)[0];
                        $results[$eventId][] = $requestResult;
                        $this->afterRequest($events[$eventId], $requestResult['response']['body']);
                    }
                }
            }

        } catch (\Throwable $t) {
            $this->logger->critical($t);
        }
        $this->logger->debug(sprintf(
            'Full mass request result: %s',
            json_encode($results)
        ));
        $this->logger->debug('Finish mass request');

        return $results;
    }

    /**
     * Get ordered mutations by events
     *
     * @param array $events
     *
     * @return array
     */
    protected function getOrderedMutationsByEvents(array $events): array
    {
        $this->logger->debug('Getting ordered mutations for events', [
            'event_ids' => array_column($events, ResourceModel::ENTITY_ID)
        ]);
        $orderedMutations = [];
        foreach ($events as $event) {
            $mutations = $this->dataConfig->get(sprintf('solvedata_events/%s', $event['name']));
            foreach ($mutations as $mutation) {
                $orderedMutations[$mutation['order']][$event['id']][] = $mutation['class'];
            }
        }
        ksort($orderedMutations);
        if (!empty($orderedMutations[0])) {
            array_push($orderedMutations, $orderedMutations[0]);
            unset($orderedMutations[0]);
        }

        return array_values($orderedMutations);
    }

    /**
     * Get ordered mutations by event
     *
     * @param array $event
     *
     * @return mixed
     */
    protected function getOrderedMutationsByEvent(array $event)
    {
        $this->logger->debug('Getting ordered mutations for event', ['event_id' => $event[ResourceModel::ENTITY_ID]]);

        // Special case for custom events (prefixed with `QueueEvent::SOLVEDATA_EVENT_NAME_PREFIX`)
        if (QueueEvent::isSolveDataEvent($event['name'])) {
            // Special case for custom events intended for Solve
            return [QueueEvent::class];
        }

        $orderedMutations = [];
        $mutations = $this->dataConfig->get(sprintf('solvedata_events/%s', $event['name']));
        foreach ($mutations as $mutation) {
            $orderedMutations[$mutation['order']][] = $mutation['class'];
        }
        ksort($orderedMutations);
        if (!empty($orderedMutations[0])) {
            array_push($orderedMutations, $orderedMutations[0]);
            unset($orderedMutations[0]);
        }

        return call_user_func_array('array_merge', $orderedMutations);
    }

    /**
     * Prepare requests by event mutations
     *
     * @param array $event
     * @param array $mutations
     *
     * @return array
     */
    protected function prepareEventMutations(array $event, array $mutations)
    {
        $eventId = $event[ResourceModel::ENTITY_ID];
        try {
            $this->logger->debug('Preparing mutations for event', [
                'event_id' => $eventId
            ]);

            $requestsData = [];
            foreach ($mutations as $key => $mutationClass) {
                $requestData = $this->prepareEventMutation($event, $mutationClass);
                if (empty($requestData)) {
                    continue;
                }
                $requestsData[] = array_merge(
                    ['id' => sprintf('%s_%s', $event['id'], $key)],
                    $requestData
                );
            }
            $this->logger->debug('Prepared all mutations for event', [
                'event_id' => $eventId,
                'mutations' => $requestsData
            ]);

            return $requestsData;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error preparing mutations for event', [
                'exception' => $e,
                'event_id' => $eventId
            ]);

            return [];
        }
    }

    /**
     * Prepare requests by event mutation
     *
     * @param array $event
     * @param string $mutationClass
     *
     * @return array|null
     */
    protected function prepareEventMutation(array $event, string $mutationClass)
    {
        $eventId = $event[ResourceModel::ENTITY_ID];
        $eventName = $event['name'];
        $requestId = self::generateRequestId($event);

        $this->logger->debug('Preparing mutation for event', [
            'event_id' => $eventId,
            'mutation' => $mutationClass,
            'request_id' => $requestId
        ]);

        /** @var MutationInterface $mutation */
        $mutation = $this->objectManager->get($mutationClass);
        $mutation->setEvent($event);
        if (!$mutation->isAllowed()) {
            $this->logger->debug('Skipping mutation for event', [
                'event_id' => $eventId,
                'event_name' => $eventName,
                'mutation' => get_class($mutation)
            ]);

            return null;
        }

        $result = [
            'url' => $this->config->getAPIUrl($event['store_id']),
            'options' => [
                CURLOPT_HTTPHEADER => [
                    sprintf(
                        'Authorization: Basic %s',
                        base64_encode(sprintf('%s:%s',
                            $this->config->getAPIKey($event['store_id']),
                            $this->config->getPassword($event['store_id'])
                        ))
                    ),
                    sprintf('x-request-id: %s', $requestId),
                    sprintf('x-client-version: solvedata/plugins-magento2 %s', $this->getExtensionVersion()),
                ],
                CURLOPT_POSTFIELDS => http_build_query([
                    'query' => $mutation->getQuery(),
                    'variables' => json_encode($mutation->getVariables()),
                ]),
            ],
        ];
        $this->logger->debug('Prepared mutation for event', [
            'event_id' => $eventId,
            'event_name' => $eventName,
            'mutation' => get_class($mutation),
            'result' => $result
        ]);

        return $result;
    }

    /**
     * After send request
     *
     * @param array $event
     * @param string $body
     *
     * @return GraphQL
     */
    protected function afterRequest(array $event, string $body)
    {
        try {
            $body = json_decode($body, true);
            if (empty($body['data']['createOrUpdateProfile']['id'])
                || empty($body['data']['createOrUpdateProfile']['emails'])
            ) {
                return $this;
            }
            $websiteId = (int)$this->storeManager->getStore($event['store_id'])->getWebsiteId();
            foreach ($body['data']['createOrUpdateProfile']['emails'] as $email) {
                $this->logger->debug('Saving profile_id for customer', [
                    'profile_id' => $body['data']['createOrUpdateProfile']['id'],
                    'email' => $email
                  ]);
                $this->profileHelper->saveProfileIdByEmail(
                    $email,
                    $body['data']['createOrUpdateProfile']['id'],
                    $websiteId
                );
            }
        } catch (\Throwable $t) {
            $this->logger->error('Unexpected error saving profile_id for email from GraphQL response', [
                'exception' => $t,
                'response' => $body
            ]);
        }

        return $this;
    }

    /**
     * Generate an opaque unique token to be used as the ID for the GraphQL request.
     *
     * @return string
     */
    private static function generateRequestId(array $event): string
    {
        $store = "-no-event-";
        $entity = "-no-event-";
        if (!empty($event)) {
            $store = empty($event['store_id']) ? "-absent-" : $event['store_id'];
            $entity = empty($event[ResourceModel::ENTITY_ID]) ? "-absent-" : $event[ResourceModel::ENTITY_ID];
        }
        // Request IDs need to be more than 20 characters long.
        // uniqid("", true) returns at least a 23 character long string.
        return uniqid( "S=$store" . ',' . "E=$entity" . ',' , true);
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
            $this->logger->error('Failed to get extension version.', ['exception' => $t]);
            return 'unknown';
        }
    }
}
