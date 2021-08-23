<?php

namespace SolveData\Events\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use SolveData\Events\Model\ResourceModel\Event as ResourceModel;
use Magento\Framework\DB\Adapter\AdapterInterface;

class AddCreatedAtAndStatusIndexesToSolvedataEvents implements SchemaPatchInterface
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

        $this->createIndexIfNotExists(['created_at'], $connection);
        $this->createIndexIfNotExists(['status'], $connection);

        $connection->endSetup();
    }

    private function createIndexIfNotExists($columns, $connection) {
        $tableName = $connection->getTableName(ResourceModel::TABLE_NAME);
        $indexes = $connection->getIndexList($tableName);

        if (!$this->indexAlreadyExists($indexes, $columns)) {
            $connection->addIndex(
                $tableName,
                $connection->getIndexName(
                    ResourceModel::TABLE_NAME,
                    $columns,
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                $columns,
                AdapterInterface::INDEX_TYPE_INDEX
            );
        }
    }

    private function indexAlreadyExists($indexes, $columns): bool {
        foreach ($indexes as $indexName => $indexData) {
            if ($indexData['COLUMNS_LIST'] == $columns) {
                return true;
            }
        }
        return false;
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
