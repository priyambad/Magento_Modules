<?php

namespace Redington\AddressApproval\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            $setup->startSetup();
            $setup->getConnection()->addColumn(
                $setup->getTable('redington_address_approval'),
                'request_type',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '20',
                    'nullable' => false,
                    'default' => 'Shipping',
                    'comment' => 'address request type'
                    ]
            );
             $setup->getConnection()->addColumn(
                $setup->getTable('redington_address_approval'),
                'pending_documents',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'temp address doc'
                    ]
            );
              $setup->getConnection()->addColumn(
                $setup->getTable('redington_forwarder_approval'),
                'pending_documents',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'temp forwarder doc'
                    ]
            );
          }if (version_compare($context->getVersion(), '1.0.3') < 0) {
                  $setup->startSetup();
                $setup->getConnection()->addColumn(
                $setup->getTable('redington_address_approval'),
                'customer_store_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'store id'
                    ]
            );
           
               $setup->getConnection()->addColumn(
                $setup->getTable('redington_forwarder_approval'),
                'customer_store_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'store id'
                    ]
            );
                $setup->getConnection()->changeColumn(
                $setup->getTable('admin_user'),
                'salesrule_websites',
                'store_permission',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => '',
                    'nullable' => true,
                    'default'  => null,
                    'comment' => 'store_permission'
                ]
            );
            $setup->endSetup();
        }
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
             $installer = $setup;
             $installer->startSetup();
 
            /**
             * Add full text index to our table redington_forwarder_approval
             */
 
            $tableName = $installer->getTable('redington_forwarder_approval');
            $fullTextIntex = array('company_name'); // Column with fulltext index, you can put multiple fields
 
 
            $setup->getConnection()->addIndex(
                $tableName,
                $installer->getIdxName($tableName, $fullTextIntex, \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT),
                $fullTextIntex,
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            );
 
           $installer->endSetup();
 
        }
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
             $installer = $setup;
             $installer->startSetup();
 
            /**
             * Add full text index to our table redington_address_approval
             */
 
            $tableName = $installer->getTable('redington_address_approval');
            $fullTextIntex = array('company_name','city','country','phone'); // Column with fulltext index, you can put multiple fields
 
 
            $setup->getConnection()->addIndex(
                $tableName,
                $installer->getIdxName($tableName, $fullTextIntex, \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT),
                $fullTextIntex,
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            );
 
           $installer->endSetup();
 
        }
    }
}
