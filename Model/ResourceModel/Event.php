<?php

declare(strict_types=1);

namespace SolveData\Events\Model\ResourceModel;

use DateTime;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use SolveData\Events\Model\Config;
use SolveData\Events\Model\Event as EventModel;
use SolveData\Events\Model\Logger;

class Event extends AbstractDb
{
    const ENTITY_ID = 'id';

    const TABLE_NAME = 'solvedata_event';

    const PURGE_HISTORICAL_EVENTS_BATCH_SIZE = 10000;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param Config $config
     * @param Logger $logger
     * @param null $connectionName
     */
    public function __construct(
        Context $context,
        Config $config,
        Logger $logger,
        $connectionName = null
    ) {
        $this->config = $config;
        $this->logger = $logger;

        parent::__construct(
            $context,
            $connectionName
        );
    }

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::ENTITY_ID);
    }

    /**
     * Get events data to send
     *
     * @return array
     *
     * @throws LocalizedException
     */
    public function getEventsToSend(): array
    {
        $connection = $this->getConnection();
        $select = $connection
            ->select()
            ->from($this->getMainTable())
            ->where('status IN(?)', [
                EventModel::STATUS_NEW,
                EventModel::STATUS_RETRY,
            ])
            ->where('attempt < ?', $this->config->getMaxAttemptCount())
            ->where('scheduled_at < ?', new \Zend_Db_Expr('NOW()'))
            ->order('id ' . Select::SQL_ASC)
            ->limit($this->config->getCronBatchSize());

        return $connection->fetchAll($select);
    }

    /**
     * Purge old events older than the retention period
     *
     * @param DateTime $purgeOlderThan Purge all events with an older created_at time
     *
     * @return int Number of events purged
     *
     * @throws LocalizedException
     */
    public function purgeEventsOlderThan(DateTime $purgeOlderThan): int
    {
        $connection = $this->getConnection();

        // Delete a batch of rows with their `created_at` time older than the retention period threshold time.
        //
        // Note that if PHP & MSQL have been configured with different timezones there is the
        //      possibility that this logic will be off up to ~1 day.
        // This isn't too concerning because the retention period is somewhat arbitrary and will be a
        //      long period like 7 days or 1 month.

        $select = $connection
            ->select()
            ->from($this->getMainTable())
            ->where('created_at < ?', $purgeOlderThan)
            ->order('created_at ' . Select::SQL_ASC)
            ->limit(static::PURGE_HISTORICAL_EVENTS_BATCH_SIZE);
        
        // Reimplement the connection's `deleteFromSelect` function.
        // See https://github.com/magento/magento2/blob/2.3.5/lib/internal/Magento/Framework/DB/Adapter/Pdo/Mysql.php#L3647
        // This is because this function was assembling an invalid MySQL DELETE query.
        $select->reset(\Magento\Framework\DB\Select::DISTINCT);
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $query = sprintf('DELETE %s', $select->assemble());

        $result = $connection->query($query);
        return $result->rowCount();
    }

    /**
     * Lock events to processing
     *
     * @param array $eventIds
     *
     * @return Event
     *
     * @throws LocalizedException
     */
    public function lockEventsToProcessing(array $eventIds): Event
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'status'      => EventModel::STATUS_PROCESSING,
                'executed_at' => new \Zend_Db_Expr('NOW()'),
            ],
            [sprintf('%s IN(?)', self::ENTITY_ID) => $eventIds]
        );

        return $this;
    }

    /**
     * Create events
     *
     * @param array $events
     *
     * @return int
     *
     * @throws LocalizedException
     */
    public function createEvents(array $events): int
    {
        return $this->getConnection()->insertMultiple(
            $this->getMainTable(),
            $events
        );
    }

    /**
     * Update events by response codes
     *
     * @param array $events
     * @param array $requestResults
     *
     * @return int
     *
     * @throws LocalizedException
     */
    public function updateEvents(array $events, array $requestResults): int
    {
        foreach ($events as &$event) {
            $event['attempt'] = $event['attempt'] + 1;
            $event['finished_at'] = new \Zend_Db_Expr('NOW()');
            if (empty($requestResults[$event[self::ENTITY_ID]])) {
                $event['status'] = EventModel::STATUS_FAILED;
                continue;
            }

            $eventRequestResults = $requestResults[$event[self::ENTITY_ID]];
            $event['request'] = json_encode($eventRequestResults);

            foreach ($eventRequestResults as $requestResult) {
                if (!empty($requestResult['disabled'])) {
                    continue;
                }

                if (!empty($requestResult['exception'])) {
                    $event['status'] = EventModel::STATUS_EXCEPTION;
                    break;
                }

                $responseCode = $requestResult['response']['code'];
                $event['response_code'] = $responseCode;
                if ($responseCode >= 200 && $responseCode <= 202) {
                    $responseBody = json_decode($requestResult['response']['body'], true);
                    if (!empty($responseBody['errors'])) {

                        $this->logger->warning(sprintf(
                            'GraphQL response contained errors %s',
                            json_encode($responseBody['errors'])
                        ));

                        $event['status'] = EventModel::STATUS_FAILED;
                        break;
                    }
                    $event['status'] = EventModel::STATUS_COMPLETED;
                    continue;
                }

                $this->logger->error(sprintf('GraphQL response had an unsuccessful HTTP status code %d', $responseCode));

                // We don't retry on 429 (Too Many Requests) because we would like to avoid a retry storm
                //  until we have implemented proper backoff logic.
                $retryableStatusCode = $responseCode == 409 || $responseCode >= 500;
                $exceededMaxAttempts = $event['attempt'] >= $this->config->getMaxAttemptCount();
                
                if ($retryableStatusCode && !$exceededMaxAttempts) {
                    $event['status'] = EventModel::STATUS_RETRY;
                    $event['scheduled_at'] = new \Zend_Db_Expr(sprintf(
                        'DATE_ADD(NOW(), INTERVAL %d MINUTE)',
                        $this->config->getAttemptInterval()
                    ));
                    break;
                }
                
                $event['status'] = EventModel::STATUS_FAILED;
                break;
            }
        }

        return $this->getConnection()->insertOnDuplicate(
            $this->getMainTable(),
            $events,
            ['scheduled_at', 'finished_at', 'status', 'response_code', 'request', 'attempt']
        );
    }
}
