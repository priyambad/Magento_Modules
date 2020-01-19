<?php


namespace Redington\Transactions\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
         $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            // Get module table
            $tableName = $setup->getTable('redington_transaction');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {

                // Declare data
                $columns = [
                    'transaction_id' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '15',
                        'nullable' => true,
                        'comment' => 'Order Reference Number',
                    ],
                ];

                $connection = $setup->getConnection();

                foreach ($columns as $name => $definition) {
                    $connection->addColumn($tableName, $name, $definition);
                }
            }
        }
        if (version_compare($context->getVersion(), '1.0.2') < 0) {

                $setup->getConnection()->addColumn(
                $setup->getTable('redington_credit'),
                'customer_store_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'store id'
                    ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.3') < 0) {
             $installer = $setup;
             $installer->startSetup();
 
            /**
             * Add full text index to our table redington_address_approval
             */
 
            $tableName = $installer->getTable('redington_credit');
            $fullTextIntex = array('account_name','sap_acc_no'); // Column with fulltext index, you can put multiple fields
 
 
            $setup->getConnection()->addIndex(
                $tableName,
                $installer->getIdxName($tableName, $fullTextIntex, \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT),
                $fullTextIntex,
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            );
 
          
        }
        $setup->endSetup();
    }
}
