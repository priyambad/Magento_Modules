<?php

namespace Redington\PaymentGroup\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (!$installer->tableExists('redington_payment_method_group')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('redington_payment_method_group')
            )
                ->addColumn(
                    'group_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                    ],
                    'Group Id'
                )
                ->addColumn(
                    'partner_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable => false'],
                    'Partner Id'
                )
                ->addColumn(
                    'payment_method_group',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Method Group'
                )
                ->addColumn(
                    'sap_ref_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Sap Reference Code'
                )
                ->setComment('Payment Method Group Table');
            $installer->getConnection()->createTable($table);

            $installer->getConnection()->addIndex(
                $installer->getTable('redington_payment_method_group'),
                $setup->getIdxName(
                    $installer->getTable('redington_payment_method_group'),
                    ['partner_id', 'payment_method_group','sap_ref_code'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                ['partner_id', 'payment_method_group','sap_ref_code'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_FULLTEXT
            );
        }
        $installer->endSetup();
    }
}
