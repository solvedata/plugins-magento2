<?php

namespace SolveData\Events\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use SolveData\Events\Model\ResourceModel\Profile as ResourceModel;

class ChangeStoreIdToWebsiteIdInSolvedataProfileTable implements SchemaPatchInterface
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
        $storeWebsiteTableName = $connection->getTableName('store_website');

        $connection->truncateTable($tableName);

        $connection->dropIndex(
            $tableName,
            $connection->getIndexName(
                ResourceModel::TABLE_NAME,
                ['email', 'store_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            )
        );
        $connection->dropColumn($tableName, 'store_id');
        $connection->addColumn(
            $tableName,
            'website_id',
            [
                'type'     => Table::TYPE_SMALLINT,
                'length'   => 5,
                'nullable' => false,
                'unsigned' => true,
                'comment'  => 'Store Id',
                'after'    => 'sid'
            ]
        );
        $connection->addIndex(
            $tableName,
            $connection->getIndexName(
                ResourceModel::TABLE_NAME,
                ['email', 'website_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['email', 'website_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );
        $connection->addForeignKey(
            $connection->getForeignKeyName(
                $tableName,
                'website_id',
                $storeWebsiteTableName,
                'website_id'
            ),
            $tableName,
            'website_id',
            $storeWebsiteTableName,
            'website_id'
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
        $storeTableName = $connection->getTableName('store');

        $connection->dropIndex(
            $tableName,
            $connection->getIndexName(
                ResourceModel::TABLE_NAME,
                ['email', 'website_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            )
        );
        $connection->dropColumn($tableName, 'store_id');
        $connection->addColumn(
            $tableName,
            'store_id',
            [
                'type'     => Table::TYPE_SMALLINT,
                'length'   => 5,
                'nullable' => false,
                'unsigned' => true,
                'comment'  => 'Store Id',
                'after'    => 'sid'
            ]
        );
        $connection->addIndex(
            $tableName,
            $connection->getIndexName(
                ResourceModel::TABLE_NAME,
                ['email', 'store_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['email', 'store_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );
        $connection->addForeignKey(
            $connection->getForeignKeyName(
                $tableName,
                'store_id',
                $storeTableName,
                'store_id'
            ),
            $tableName,
            'store_id',
            $storeTableName,
            'store_id'
        );

        $connection->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            CreateSolvedataProfileTable::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
