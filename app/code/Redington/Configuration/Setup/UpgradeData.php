<?php
namespace Redington\Configuration\Setup;
 
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
 
class UpgradeData implements UpgradeDataInterface
{
 
   public function __construct(
        EavSetupFactory $eavSetupFactory,
        CategorySetupFactory $categorySetupFactory,
        SetFactory $attributeSetFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }
 
   public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_sku_id',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Sku Id',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_vendor_skuid',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Vendor Sku Id',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_sku_type',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Sku Type',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\SkuType',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_uom',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Unit of measure',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\Uom',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_availability',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Availability',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\Availability',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_condition',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Condition',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\Condition',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'filterable_in_search' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_ean_upc',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'EAN/ UPC Code',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_product_family',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Product Family',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_product_series',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Product Series',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_gross_weigth',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Gross weigth',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_net_weight',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Net weight',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_volume',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Volume',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_width',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Width',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_depth',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Depth',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_height',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Height',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_screen_size',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Screen size',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_screen_resolution',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Screen resolution',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_touchscreen',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Touchscreen',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\YesNo',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_display',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Display',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_rear_camera',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Rear camera',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\YesNo',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_rear_camera_resolution',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Rear camera resolution',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_front_camera',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Front camera',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\YesNo',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_front_camera_resolution',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Front camera resolution',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_camera',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Camera',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\YesNo',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_camera_resolution',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Camera resolution',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_os',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Operating System',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_storage',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Storage',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_external_memory',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'External memory',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_processor_frequency',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Processor frequency',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_internal_memory',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Internal memory',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_internal_memory_type',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Internal memory type',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_graphics',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Graphics',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_wifi',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Wifi',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\YesNo',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_bluetooth',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Bluetooth',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\YesNo',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_port_interface',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Port & interface',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_accessories_type',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Accessories type',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_compatibility',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Compatibility',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_lte_support',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Lte support',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_sim_slot',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Sim slot',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\YesNo',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_band_material',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Band material',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_brand',
                [
                    'type' => 'int',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Brand',
                    'input' => 'select',
                    'class' => '',
                    'source' => 'Redington\Configuration\Model\Config\Source\Brands',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 1,
                    'comparable' => true,
                    'visible_on_front' => true,
                    'unique' => false
                ]
            );

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_periodicity',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Periodicity',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => false,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_threshold',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Threshold',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => false,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_min_qty',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Minimum Quantity',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => false,
                    'unique' => false
                ]
            );
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'z_max_qty',
                [
                    'type' => 'text',
                    'backend' => '',
                    'frontend' => '',
                    'label' => 'Maximum Quantity',
                    'input' => 'text',
                    'class' => '',
                    'source' => '',
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'default' => '',
                    'searchable' => false,
                    'filterable' => 0,
                    'comparable' => true,
                    'visible_on_front' => false,
                    'unique' => false
                ]
            );
        }
        if (version_compare($context->getVersion(), '1.0.2', '<')) {

            $eavSetup = $this->eavSetupFactory->create();
            $categorySetup = $this->categorySetupFactory->create();

            $attributeSet = $this->attributeSetFactory->create();
            $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);

            // default product attribute group id
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

            $basic = ['z_sku_id','z_vendor_skuid','name','z_sku_type','z_uom','category_ids','z_availability','z_condition','z_ean_upc','price','description','short_description'];
            $brands = ['z_brand','z_product_family','z_product_series'];
            $dimension = ['z_gross_weigth','z_net_weight','z_volume','z_width','z_depth','z_height'];
            $system = ['z_periodicity','z_threshold','z_min_qty','z_max_qty'];


            $attributeSetArray = [
                'MOBILITY_SMARTPHONES',
                'MOBILITY_FEATUREPHONES',
                'MOBILITY_SMARTWATCHES',
                'MOBILITY_ACCESSORIES',
                'PC',
                'PC_ACCESSORIES',
                'TABLETS',
                'TABLET_ACCESSORIES'
            ];
            $attributeGroupArray = [
                'BASIC',
                'BRAND',
                'WEIGHT & DiMENSION',
                'SYSTEM'
            ];

            $productCharecteristicsArray = [
                'MOBILITY_SMARTPHONES' => [
                    'z_screen_size',
                    'z_screen_resolution',
                    'z_touchscreen',
                    'z_display',
                    'z_rear_camera',
                    'z_rear_camera_resolution',
                    'z_front_camera',
                    'z_front_camera_resolution',
                    'z_os',
                    'z_storage',
                    'color',
                    'z_external_memory'
                ],
                'MOBILITY_FEATUREPHONES' => [
                    'z_screen_size',
                    'z_screen_resolution',
                    'z_touchscreen',
                    'z_display',
                    'z_rear_camera',
                    'z_rear_camera_resolution',
                    'z_front_camera',
                    'z_front_camera_resolution',
                    'z_os',
                    'z_storage',
                    'color',
                    'z_external_memory'
                ],
                'MOBILITY_SMARTWATCHES' => [
                    'z_screen_size',
                    'z_screen_resolution',
                    'z_touchscreen',
                    'z_display',
                    'z_camera',
                    'z_camera_resolution',
                    'z_os',
                    'z_storage',
                    'color',
                    'z_external_memory',
                    'z_band_material',
                    'z_lte_support'
                ],
                'MOBILITY_ACCESSORIES' => [
                    'z_accessories_type',
                    'color',
                    'z_compatibility'
                ],
                'PC' => [
                    'z_screen_size',
                    'z_screen_resolution',
                    'z_touchscreen',
                    'z_display',
                    'z_front_camera',
                    'z_front_camera_resolution',
                    'z_os',
                    'z_storage',
                    'color',
                    'z_processor_frequency',
                    'z_internal_memory',
                    'z_internal_memory_type',
                    'z_graphics',
                    'z_wifi',
                    'z_bluetooth',
                    'z_port_interface'
                ],
                'PC_ACCESSORIES' => [
                    'z_accessories_type',
                    'color',
                    'z_compatibility'
                ],
                'TABLETS' => [
                    'z_screen_size',
                    'z_screen_resolution',
                    'z_touchscreen',
                    'z_display',
                    'z_rear_camera',
                    'z_rear_camera_resolution',
                    'z_front_camera',
                    'z_front_camera_resolution',
                    'z_os',
                    'z_storage',
                    'color',
                    'z_processor_frequency',
                    'z_internal_memory',
                    'z_lte_support',
                    'z_wifi',
                    'z_bluetooth',
                    'z_sim_slot'
                ],
                'TABLET_ACCESSORIES' => [
                    'z_accessories_type',
                    'color',
                    'z_compatibility'
                ],
            ];

            $fixedAttributeArray = [
                'BASIC' => $basic,
                'BRAND' => $brands,
                'WEIGHT & DiMENSION' => $dimension,
                'SYSTEM' => $system
            ];


            foreach ($attributeSetArray as $attributeSetName) {
                $attributeSet = $this->attributeSetFactory->create();
                $data = [
                    'attribute_set_name' => $attributeSetName,
                    'entity_type_id' => $entityTypeId,
                    'sort_order' => 100,
                ];
    
        //		create attribute set
                $attributeSet->setData($data);
                $attributeSet->validate();
                $attributeSet->save();
                $attributeSet->initFromSkeleton($attributeSetId);
                $attributeSet->save();
        //		get id of attribute created
                $attributeId = $attributeSet->getId();
    
        //		create group inside created attribute set
    
                foreach ($attributeGroupArray as $groupName) {
                    $categorySetup->addAttributeGroup(
                        \Magento\Catalog\Model\Product::ENTITY,
                        $attributeId,
                        $groupName, // attribute group name
                        100 // sort order
                    );
                    foreach($fixedAttributeArray[$groupName] as $attr_code ) {
                        echo 'assigning '.$attr_code,' to '.$groupName.' in '. $attributeSetName.'--------------------------------';
                        $categorySetup->addAttributeToGroup(
                            $entityTypeId, 
                            $attributeId,
                            $groupName, // attribute group
                            $attr_code, // this is defined above as 'chapagain_attribute_2
                            null // sort order, can be integer value like 10 or 30, etc.
                        );
                    }
                }
                $categorySetup->addAttributeGroup(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeId,
                    'PRODUCT CHARECTERISTICS', // attribute group name
                    100 // sort order
                );
                foreach($productCharecteristicsArray[$attributeSetName] as $attr_code ) {
                    echo 'assigning '.$attr_code,' to PRODUCT CHARECTERISTICS in '. $attributeSetName.'--------------------------------';
                    $categorySetup->addAttributeToGroup(
                        $entityTypeId, 
                        $attributeId,
                        'PRODUCT CHARECTERISTICS', // attribute group
                        $attr_code, // this is defined above as 'chapagain_attribute_2
                        null // sort order, can be integer value like 10 or 30, etc.
                    );
                }
            }


        }
    }
}
