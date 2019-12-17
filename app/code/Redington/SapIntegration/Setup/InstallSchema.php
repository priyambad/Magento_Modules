<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_SapIntegration
 */

namespace Redington\SapIntegration\Setup;

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
        $tableName = $installer->getTable('sales_order');

        $columns = [
            'sap_reference_number' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => '20',
                'nullable' => true,
                'comment' => 'Sap reference number',
            ]
        ];

        $connection = $installer->getConnection();
        foreach ($columns as $name => $definition) {
            $connection->addColumn($tableName, $name, $definition);
        }


        $tableName = $installer->getTable('redington_order_reference');
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
                        'order_id', Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => false
                        ], 'Order ID'
                )
                ->addForeignKey(
                        $setup->getFkName('redington_order_reference', 'order_id', 'sales_order', 'entity_id'), 'order_id', $setup->getTable('sales_order'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addColumn(
                        'request_data', Table::TYPE_TEXT, 65536, ['nullable' => false, 'default' => ''], 'Request Data'
                )
                ->addColumn(
                    'response_data', Table::TYPE_TEXT, 65536, ['nullable' => false, 'default' => ''], 'Response Data'
                )
                ->addColumn(
                    'status', Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => false
                        ], 'Status'
                );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}