<?php

namespace SolveData\Events\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Declaration\Schema\Dto\Factories\MediumText;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use SolveData\Events\Model\ResourceModel\Event;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable(Event::TABLE_NAME);
        $connection = $installer->getConnection();

        $table = $connection->newTable($tableName)
            ->addColumn(
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
                'name',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ],
                'Magento Event Name'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                    'default'  => Table::TIMESTAMP_INIT
                ],
                'Created At'
            )->addColumn(
                'executed_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                ],
                'Executed At'
            )->addColumn(
                'finished_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => false,
                ],
                'Finished At'
            )->addColumn(
                'status',
                Table::TYPE_INTEGER,
                2,
                [
                    'nullable' => false,
                ],
                'Status'
            )->addColumn(
                'payload',
                Table::TYPE_TEXT,
                MediumText::DEFAULT_TEXT_LENGTH,
                [
                    'nullable' => false,
                ],
                'Payload'
            )->addColumn(
                'affected_entity_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'nullable' => false
                ],
                'Affected Entity Id'
            )->addColumn(
                'affected_increment_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true,
                ],
                'Affected Increment Id'
            )->addColumn(
                'store_id',
                Table::TYPE_SMALLINT,
                5,
                [
                    'nullable' => false,
                    'unsigned' => true,
                ],
                'Store ID'
            )->setComment('SolveData Events Table');

        $storeTableName = $installer->getTable('store');
        $table->addForeignKey(
            $installer->getFkName(
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
        $installer->endSetup();
    }
}
