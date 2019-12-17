<?php

namespace Redington\AddressApproval\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface {
    /**
     * 
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
        $tableName = $installer->getTable('redington_address_approval');
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()
                    ->newTable($tableName)
                    ->addColumn(
                        'entity_id', Table::TYPE_INTEGER, null, [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                            ], 'Entity ID'
                    )
                    ->addColumn(
                        'address_id', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Address Id'
                    )
                    ->addForeignKey(
                            $setup->getFkName('redington_address_approval', 'address_id', 'customer_address_entity', 'entity_id'), 'address_id', $setup->getTable('customer_address_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    ->addColumn(
                        'company_id', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Company ID'
                    )
                    ->addForeignKey(
                            $setup->getFkName('redington_address_approval', 'company_id', 'company', 'entity_id'), 'company_id', $setup->getTable('company'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    ->addColumn(
                        'parent_id', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Parent Id'
                    )
                    ->addForeignKey(
                            $setup->getFkName('redington_address_approval', 'parent_id', 'customer_entity', 'entity_id'), 'parent_id', $setup->getTable('customer_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    ->addColumn(
                        'requested_by', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Requested By'
                    )
                    ->addForeignKey(
                        $setup->getFkName('redington_address_approval', 'requested_by', 'customer_entity', 'entity_id'), 'requested_by', $setup->getTable('customer_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                     )
                    ->addColumn(
                            'company_name', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Company Name'
                    )
                    ->addColumn(
                            'phone', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Phone'
                    )
                    ->addColumn(
                            'city', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'City'
                    )->addColumn(
                            'postcode', Table::TYPE_INTEGER, null, ['nullable' => false], 'Zip Code'
                    )
                    ->addColumn(
                            'country', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Country'
                    )
                    ->addColumn(
                            'address', Table::TYPE_TEXT, 500, ['nullable' => false, 'default' => ''], 'Address'
                    )
                    ->addColumn(
                            'status', Table::TYPE_INTEGER, null, ['nullable' => false], 'Status'
                    )
                    ->addColumn(
                            'comments', Table::TYPE_TEXT, 65536, ['nullable' => false], 'Comments'
                    )
                    ->addColumn(
                            'documents', Table::TYPE_TEXT, 65536, ['nullable' => false], 'Documents'
                    );
            $installer->getConnection()->createTable($table);
        }

        $tableName = $installer->getTable('redington_forwarder_approval');
        if ($installer->getConnection()->isTableExists($tableName) != true) {
                $table = $installer->getConnection()
                        ->newTable($tableName)
                        ->addColumn(
                            'entity_id', Table::TYPE_INTEGER, null, [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true
                                ], 'Entity ID'
                        )
                        ->addColumn(
                            'address_id', Table::TYPE_INTEGER, null, [
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => false
                                ], 'Address Id'
                        )
                        ->addForeignKey(
                                $setup->getFkName('redington_forwarder_approval', 'address_id', 'customer_address_entity', 'entity_id'), 'address_id', $setup->getTable('customer_address_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                        )
                        ->addColumn(
                            'company_id', Table::TYPE_INTEGER, null, [
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => false
                                ], 'Company ID'
                        )
                        ->addForeignKey(
                                $setup->getFkName('redington_forwarder_approval', 'company_id', 'company', 'entity_id'), 'company_id', $setup->getTable('company'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                        )
                        ->addColumn(
                            'parent_id', Table::TYPE_INTEGER, null, [
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => false
                                ], 'Parent Id'
                        )
                        ->addForeignKey(
                                $setup->getFkName('redington_forwarder_approval', 'parent_id', 'customer_entity', 'entity_id'), 'parent_id', $setup->getTable('customer_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                        )
                        ->addColumn(
                            'requested_by', Table::TYPE_INTEGER, null, [
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => false
                                ], 'Requested By'
                        )
                        ->addForeignKey(
                            $setup->getFkName('redington_forwarder_approval', 'requested_by', 'customer_entity', 'entity_id'), 'requested_by', $setup->getTable('customer_entity'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                         )
                        ->addColumn(
                                'company_name', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Company Name'
                        )
                        ->addColumn(
                                'phone', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Phone'
                        )
                        ->addColumn(
                                'city', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'City'
                        )->addColumn(
                                'postcode', Table::TYPE_INTEGER, null, ['nullable' => false], 'Zip Code'
                        )
                        ->addColumn(
                                'country', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Country'
                        )
                        ->addColumn(
                                'address', Table::TYPE_TEXT, 500, ['nullable' => false, 'default' => ''], 'Address'
                        )
                        ->addColumn(
                                'status', Table::TYPE_INTEGER, null, ['nullable' => false], 'Status'
                        )
                        ->addColumn(
                                'comments', Table::TYPE_TEXT, 65536, ['nullable' => false], 'Comments'
                        )
                        ->addColumn(
                                'documents', Table::TYPE_TEXT, 65536, ['nullable' => false], 'Documents'
                        );
                $installer->getConnection()->createTable($table);
            }

        $installer->endSetup();
    }

}
