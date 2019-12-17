<?php

namespace Redington\Transactions\Setup;

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
        $tableName = $installer->getTable('redington_credit');
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
                            'company_id', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Company ID'
                    )
                    ->addForeignKey(
                            $setup->getFkName('redington_credit', 'company_id', 'company', 'entity_id'), 'company_id', $setup->getTable('company'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    
                    ->addColumn(
                            'requester', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Requester'
                    )
                    ->addColumn(
                            'request_date', Table::TYPE_DATE, null, ['nullable' => false], 'Request Date'
                    )
                    ->addColumn(
                            'requested_credit_limit', Table::TYPE_DECIMAL, null, ['length'    => '20,2','nullable' => false], 'Requested Credit Limit'
                    )
                    ->addColumn(
                            'available_credit_limit', Table::TYPE_DECIMAL, null, ['length'    => '20,2','nullable' => false], 'Available Credit Limit'
                    )
                    ->addColumn(
                            'sap_acc_no', Table::TYPE_TEXT, null, ['nullable' => false, 'default' => ''], 'Sap Account number'
                    )
                    ->addColumn(
                            'action_taken_by', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => true,
                        'primary' => false
                            ], 'Action taken by'
                    )
                    ->addColumn(
                            'action_date', Table::TYPE_DATE, null, ['nullable' => true], 'Action Date'
                    )
                    ->addColumn(
                            'account_name', Table::TYPE_TEXT, null, ['nullable' => false], 'Account Name'
                    )
                    ->addColumn(
                            'status', Table::TYPE_INTEGER, null, ['nullable' => true], 'Status'
                    )
                    ->addColumn(
                            'comments', Table::TYPE_TEXT, 65536, ['nullable' => false], 'Comments'
                    )
                    ->addColumn(
                    'documents', Table::TYPE_TEXT, 65536, ['nullable' => false], 'Documents'
            );
            $installer->getConnection()->createTable($table);
        }

        $tableName = $installer->getTable('redington_transaction');
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
                            'company_id', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false
                            ], 'Company ID'
                    )
                    ->addForeignKey(
                            $setup->getFkName('redington_transaction', 'company_id', 'company', 'entity_id'), 'company_id', $setup->getTable('company'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                    )
                    ->addColumn(
                            'transaction_date', Table::TYPE_DATE, null, ['nullable' => false], 'Transaction Date'
                    )
                    ->addColumn(
                            'transaction_amount', Table::TYPE_DECIMAL, null, ['length'    => '20,2','nullable' => false], 'Transaction Amount'
                    )
                    ->addColumn(
                            'remaining_credit_limit', Table::TYPE_DECIMAL, null, ['length'    => '20,2','nullable' => false], 'Remaining Credit Limit'
                    )
                    ->addColumn(
                            'action_taken_by', Table::TYPE_INTEGER, null, [
                        'unsigned' => true,
                        'nullable' => true,
                        'primary' => false
                            ], 'Action taken by'
                    )
                    ->addColumn(
                            'transaction_type', Table::TYPE_TEXT, null, ['nullable' => false], 'Transaction Type'
                    );
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }

}
