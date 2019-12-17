<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Warehouse
 */

namespace Redington\Warehouse\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $table = $installer->getTable('inventory_source');

        $columns = [
            'sap_account_code' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => '100',
                'nullable' => true,
				'default' => '',
                'comment' => 'Sap account code',
            ],
            'distribution' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => '10',
                'nullable' => true,
                'comment' => 'Distribution channel',
            ]
        ];

        $connection = $installer->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($table, $name, $definition);
        }


        $tableName = $installer->getTable('redington_distribution_channel');
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                        'entity_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                        ], 'Entity Id'
                )
                ->addColumn(
                    'store_code', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Store code'
                )
                ->addColumn(
                        'distribution_channel', Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => false
                        ], 'Distribution channel'
                );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}