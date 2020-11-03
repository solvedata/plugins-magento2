<?php

namespace SolveData\Events\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Declaration\Schema\Dto\Factories\MediumText;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use SolveData\Events\Model\ResourceModel\Event as ResourceModel;

class AddRequestColumnToSolvedataTable implements SchemaPatchInterface
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
            'request',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => MediumText::DEFAULT_TEXT_LENGTH,
                'nullable' => true,
                'comment'  => __('Request'),
                'after'    => 'payload'
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

        $connection->dropColumn($tableName, 'request');

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
