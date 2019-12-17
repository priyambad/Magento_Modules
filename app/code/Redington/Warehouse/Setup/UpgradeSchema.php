<?php
 
namespace Redington\Warehouse\Setup;
 
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class UpgradeSchema implements UpgradeSchemaInterface {
 
    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
        $installer = $setup;
 
        $installer->startSetup();
        
        if(version_compare($context->getVersion(), '1.0.1', '<')) {
            $table = $installer->getTable('inventory_source');
            $columns = [
                'plant_code' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => '10',
                    'nullable' => true,
                    'comment' => 'Plant code',
                ]
            ];

            $connection = $installer->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($table, $name, $definition);
            }
        }
 
        $installer->endSetup();
    }
}