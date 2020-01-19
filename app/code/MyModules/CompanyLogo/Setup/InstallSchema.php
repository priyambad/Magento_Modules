<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CompanyLogo
 */
namespace Redington\CompanyLogo\Setup;

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
         * Create table 'redington_pdf_data'
         */
        if (!$setup->getConnection()->isTableExists($setup->getTable('redington_pdf_data'))) {
            
            $table = $setup->getConnection()
                ->newTable($setup->getTable('redington_pdf_data'))
                ->addColumn(
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    10,
                    ['identity' => true, 'primary'=>true, 'unsigned' => true, 'nullable' => false],
                    'Entity ID'
                )
                ->addColumn(
                    'company_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Company Code'
                )
                ->addColumn(
                    'company_logo',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Company Logo Path'
                )
                ->addColumn(
                    'company_address',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Company Address'
                )
                ->setComment('Redington Company Data Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');

            $setup->getConnection()->createTable($table);
        }
        $setup->endSetup();
    }
}