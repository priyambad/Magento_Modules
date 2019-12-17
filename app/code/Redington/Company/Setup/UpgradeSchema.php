<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Company
 */

namespace Redington\Company\Setup;

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
            $tableName = $setup->getTable('company_advanced_customer_entity');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {

                // Declare data
                $columns = [
                    'country_code' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => '10',
                        'nullable' => true,
                        'comment' => 'Country code',
                    ],
                ];

                $connection = $setup->getConnection();

                foreach ($columns as $name => $definition) {
                    $connection->addColumn($tableName, $name, $definition);
                }
            }
        }
    }
}
