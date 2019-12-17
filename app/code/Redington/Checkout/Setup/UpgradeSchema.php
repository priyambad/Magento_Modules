<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            // Get module table
            $tableName = $setup->getTable('sales_order');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {

                // Declare data
                $columns = [
                    'forwarder_address_id' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'length' => '10',
                        'nullable' => false,
                        'comment' => 'Forwarder Address Id',
                    ],
                ];

                $connection = $setup->getConnection();

                foreach ($columns as $name => $definition) {
                    $connection->addColumn($tableName, $name, $definition);
                }
            }
        }
        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            $amastyTable = $setup->getTable('amasty_amcheckout_additional');
            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($amastyTable) == true) {
                $columns = [
                    'lpo_reference_document' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'Reference Document',
                    ],
                ];

                $connection = $setup->getConnection();
                foreach ($columns as $name => $definition) {
                    $connection->addColumn($amastyTable, $name, $definition);
                }
            }

            // Get module table
            $tableName = $setup->getTable('sales_order');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {

                $connection = $setup->getConnection();

                $connection->addColumn($tableName, 'warehouse_code', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => '10',
                    'nullable' => true,
                    'comment' => 'Warehouse Code',
                ]);
            }
        }
        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $amastyTable = $setup->getTable('amasty_amcheckout_additional');
            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($amastyTable) == true) {
                $columns = [
                    'pdc_document' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'PDC Document',
                    ],
                    'cdc_document' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'CDC Document',
                    ],
                ];

                $connection = $setup->getConnection();
                foreach ($columns as $name => $definition) {
                    $connection->addColumn($amastyTable, $name, $definition);
                }
            }

            // Get module table
            $tableName = $setup->getTable('sales_order');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {

                $connection = $setup->getConnection();

                $connection->addColumn($tableName, 'warehouse_code', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => '10',
                    'nullable' => true,
                    'comment' => 'Warehouse Code',
                ]);
            }
        }
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $amastyTable = $setup->getTable('amasty_amcheckout_additional');
            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($amastyTable) == true) {
                $columns = [
                    'pdc_ref_no' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'PDC Payment Reference No',
                    ],
                    'cdc_ref_no' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'CDC Payment Reference No',
                    ],
                ];

                $connection = $setup->getConnection();
                foreach ($columns as $name => $definition) {
                    $connection->addColumn($amastyTable, $name, $definition);
                }
            }
        }
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $amastyTable = $setup->getTable('amasty_amcheckout_additional');
            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($amastyTable) == true) {
                $columns = [
                    'cash_ref_no' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'Cash Payment Reference No',
                    ],
                    'cash_document' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'Cash Payment Document',
                    ],
                ];

                $connection = $setup->getConnection();
                foreach ($columns as $name => $definition) {
                    $connection->addColumn($amastyTable, $name, $definition);
                }
            }

        }
        $setup->endSetup();
    }
}
