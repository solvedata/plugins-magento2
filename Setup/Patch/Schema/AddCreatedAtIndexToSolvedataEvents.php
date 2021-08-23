<?php

namespace SolveData\Events\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use SolveData\Events\Model\ResourceModel\Event as ResourceModel;
use Magento\Framework\DB\Adapter\AdapterInterface;

class AddCreatedAtIndexToSolvedataEvents implements SchemaPatchInterface
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

        if (!$this->indexAlreadyExists($connection->getIndexList($tableName))) {
            $connection->addIndex(
                $tableName,
                $connection->getIndexName(
                    ResourceModel::TABLE_NAME,
                    ['created_at'],
                    AdapterInterface::INDEX_TYPE_INDEX
                ),
                ['created_at'],
                AdapterInterface::INDEX_TYPE_INDEX
            );
        }

        $connection->endSetup();
    }

    private function indexAlreadyExists($indexes): bool {
        foreach ($indexes as $indexName => $indexData) {
            if ($indexData['COLUMNS_LIST'] == ['created_at']) {
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
