<?php

namespace SolveData\Events\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use SolveData\Events\Model\ResourceModel\Profile as ResourceModel;

class DropSidUniqueIndexInSolvedataProfileTable implements SchemaPatchInterface
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

        $connection->dropIndex(
            $tableName,
            $connection->getIndexName(
                ResourceModel::TABLE_NAME,
                ['sid'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            )
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

        $connection->addIndex(
            $connection->getIndexName(
                ResourceModel::TABLE_NAME,
                ['sid'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['sid'],
            AdapterInterface::INDEX_TYPE_UNIQUE
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
