<?php

namespace Redington\Category\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        /**
         * Create table 'redinton_category_data'
         */

        if (!$setup->getConnection()->isTableExists($setup->getTable('redington_category_data'))) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable('redington_category_data'))
                ->addColumn(
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    10,
                    ['identity' => true, 'primary'=>true, 'unsigned' => true, 'nullable' => false],
                    'Entity ID'
                )
                ->addColumn(
                    'website_id', Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => false
                        ], 'Website ID'
                )
                ->addColumn(
                    'partner_id', Table::TYPE_INTEGER, null, [
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => false
                        ], 'Partner ID'
                )
                ->addForeignKey(
                        $setup->getFkName('redington_category_data', 'partner_id', 'company', 'entity_id'), 'partner_id', $setup->getTable('company'), 'entity_id', \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
                )
                ->addColumn(
                        'categories', Table::TYPE_TEXT, 65536, ['nullable' => false], 'Categories'
                )
                ->addColumn(
                        'brands', Table::TYPE_TEXT, 65536, ['nullable' => false], 'Brands'
                )
                ->setComment('Redington Category Data Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');

            $setup->getConnection()->createTable($table);
        }
        $setup->endSetup();
    }
}