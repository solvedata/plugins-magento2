<?php

namespace SolveData\Events\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use SolveData\Events\Model\ResourceModel\Profile as ResourceModel;

class CreateSolvedataProfileTable implements SchemaPatchInterface
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
        $storeTableName = $connection->getTableName('store');
        $table = $connection->newTable($tableName);

        $table->addColumn(
            'id',
            Table::TYPE_INTEGER,
            10,
            [
                'identity' => true,
                'nullable' => false,
                'primary'  => true,
                'unsigned' => true,
            ],
            'Entity ID'
        )->addColumn(
            'email',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false,
            ],
            'Profile Email'
        )->addColumn(
            'sid',
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false,
            ],
            'Profile SID'
        )->addColumn(
            'store_id',
            Table::TYPE_SMALLINT,
            5,
            [
                'nullable' => false,
                'unsigned' => true,
            ],
            'Store ID'
        )->addIndex(
            $connection->getIndexName(
                ResourceModel::TABLE_NAME,
                ['email', 'store_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['email', 'store_id'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addIndex(
            $connection->getIndexName(
                ResourceModel::TABLE_NAME,
                ['sid'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['sid'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        )->addForeignKey(
            $connection->getForeignKeyName(
                $tableName,
                'store_id',
                $storeTableName,
                'store_id'
            ),
            'store_id',
            $storeTableName,
            'store_id',
            Table::ACTION_CASCADE
        );

        $connection->createTable($table);

        $connection->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $connection->dropTable(ResourceModel::TABLE_NAME);

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
