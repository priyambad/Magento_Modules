<?php


namespace Redington\Warehouse\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{

    public function __construct(
            \Magento\Inventory\Model\SourceFactory $inventorySourceFactory,
            \Redington\Warehouse\Helper\Data $warehouseHelper
        ) {
        $this->inventorySourceFactory = $inventorySourceFactory;
        $this->warehouseHelper = $warehouseHelper;
    }
    /**
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $warehouseList = $this->warehouseHelper->getWarehouseList();
        foreach ($warehouseList as $warehouse) {
            $source = $this->inventorySourceFactory->create();
            $source->setDistribution($warehouse['distribution_channel']);
            $source->setSapAccountCode($warehouse['sap_account_code']);
            $source->setPlantCode($warehouse['plant_code']);
            $source->setName($warehouse['name']);
            $source->setSourceCode($warehouse['source_code']);
            $source->setContactName($warehouse['name']);
            $source->setCountryId($warehouse['country_id']);
            $source->setCity($warehouse['city']);
            $source->setStreet($warehouse['street']);
            $source->setPostcode($warehouse['postcode']);
            $source->setPhone($warehouse['phone']);
            $source->setEnabled(1);
            $source->setUseDefaultCarrierConfig(1);
            $source->save();
        }


        $tableName = $setup->getTable('redington_distribution_channel');

        if ($setup->getConnection()->isTableExists($tableName) == true) {
        $tabledata = [
        [
            'entity_id' => 1,
            'store_code' => 'volume_ae',
            'distribution_channel' => 11
        ],
        [
            'entity_id' => 2,
            'store_code' => 'telco_ae',
            'distribution_channel' => 13
        ],
        [
            'entity_id' => 3,
            'store_code' => 'volume_ke',
            'distribution_channel' => 31
        ],
        [
            'entity_id' => 4,
            'store_code' => 'telco_ke',
            'distribution_channel' => 33
        ],
        ];
        foreach ($tabledata as $data) {
                $setup->getConnection()->insert($tableName, $data);
            } 
        }
    }
}