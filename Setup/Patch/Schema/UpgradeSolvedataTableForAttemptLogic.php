<?php

namespace SolveData\Events\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use SolveData\Events\Model\ResourceModel\Event as ResourceModel;

class UpgradeSolvedataTableForAttemptLogic implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();
        $tableName = $connection->getTableName(ResourceModel::TABLE_NAME);

        $connection->addColumn(
            $tableName,
            'attempt',
            [
                'type'     => Table::TYPE_SMALLINT,
                'nullable' => false,
                'default'  => 0,
                'comment'  => __('Attempt'),
                'after'    => 'status'
            ]
        );
        $connection->addColumn(
            $tableName,
            'scheduled_at',
            [
                'type'     => Table::TYPE_TIMESTAMP,
                'nullable' => false,
                'default'  => Table::TIMESTAMP_INIT,
                'comment'  => __('Scheduled At'),
                'after'    => 'created_at'
            ]
        );
        $connection->addColumn(
            $tableName,
            'response_code',
            [
                'type'     => Table::TYPE_SMALLINT,
                'nullable' => true,
                'comment'  => __('Response Code'),
                'after'    => 'status'
            ]
        );

        $connection->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();
        $tableName = $connection->getTableName(ResourceModel::TABLE_NAME);

        $connection->dropColumn($tableName, 'scheduled_at');
        $connection->dropColumn($tableName, 'attempt');
        $connection->dropColumn($tableName, 'response_code');

        $connection->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
