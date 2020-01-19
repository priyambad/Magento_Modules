<?php

namespace Redington\Company\Setup;

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
        $tableName = $installer->getTable('redington_documents');
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()
                    ->newTable($tableName)
                    ->addColumn(
                            'id', Table::TYPE_INTEGER, null, [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                            ], 'ID'
                    )
                    ->addColumn(
                            'company_id', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Company ID'
                    )
                    ->addForeignKey(
                            $setup->getFkName('redington_documents', 'company_id', 'company', 'entity_id'), 'company_id', $setup->getTable('company'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    ->addColumn(
                            'document_details', Table::TYPE_TEXT, 65536, ['nullable' => false, 'default' => ''], 'Document detail'
                    );
            $installer->getConnection()->createTable($table);
        }

        $tableName = $installer->getTable('redington_brand_preference');
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()
                    ->newTable($tableName)
                    ->addColumn(
                            'id', Table::TYPE_INTEGER, null, [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                            ], 'ID'
                    )
                    ->addColumn(
                            'company_id', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Company ID'
                    )
                    ->addForeignKey(
                            $setup->getFkName('redington_brand_preference', 'company_id', 'company', 'entity_id'), 'company_id', $setup->getTable('company'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    ->addColumn(
                            'requested', Table::TYPE_TEXT, 65536, ['nullable' => false, 'default' => ''], 'Requested'
                    )
                    ->addColumn(
                            'approved', Table::TYPE_TEXT, 65536, ['nullable' => false, 'default' => ''], 'Approved'
                    );
            $installer->getConnection()->createTable($table);
        }


        $tableName = $installer->getTable('redington_parent_company');
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()
                    ->newTable($tableName)
                    ->addColumn(
                            'id', Table::TYPE_INTEGER, null, [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                            ], 'ID'
                    )
                    ->addColumn(
                            'company_id', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Company ID'
                    )
                    ->addForeignKey(
                            $setup->getFkName('redington_parent_company', 'company_id', 'company', 'entity_id'), 'company_id', $setup->getTable('company'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    ->addColumn(
                            'admin_email', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Admin Email'
                    )
                    ->addColumn(
                            'website_id', Table::TYPE_INTEGER, null, ['nullable' => false], 'Website Id'
                    );
            $installer->getConnection()->createTable($table);
        }


        $installer->endSetup();
    }

}
