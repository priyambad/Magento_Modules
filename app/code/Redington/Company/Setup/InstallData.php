<?php

namespace Redington\Company\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;

class InstallData implements \Magento\Framework\Setup\InstallDataInterface {

    /**
     * 
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
    EavSetupFactory $eavSetupFactory,
    AttributeSetFactory $attributeSetFactory,
    CustomerSetupFactory $customerSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function Install(
    \Magento\Framework\Setup\ModuleDataSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context) {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $attributes = [
            'z_short_company_name' => [
                'type' => 'varchar',
                'label' => 'Short Company Name',
                'input' => 'text',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1
            ],
            'z_segment' => [
                'type' => 'varchar',
                'label' => 'Segment',
                'input' => 'text',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1
            ],
            'z_max_number_users' => [
                'type' => 'int',
                'label' => 'Maximum number of users allowed',
                'input' => 'text',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1
            ],
            'z_stock_allocation' => [
                'type' => 'int',
                'label' => 'Stock Allocation',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'default' => '0',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1,
                'option' => array (
                    'values' =>
                        array (
                            0 => 'Yes',
                            1 => 'No',
                        ),
                ),
            ],
            'z_request_for_quote_allowed' => [
                'type' => 'int',
                'label' => 'Request for quote allowed',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'default' => '0',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1,
                'option' => array (
                    'values' =>
                        array (
                            0 => 'Yes',
                            1 => 'No',
                        ),
                ),
            ],
            'z_bulk_order_allowed' => [
                'type' => 'int',
                'label' => 'Bulk order allowed',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'default' => '0',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1,
                'option' => array (
                    'values' =>
                        array (
                            0 => 'Yes',
                            1 => 'No',
                        ),
                ),
            ],
            'z_download_stock_availability' => [
                'type' => 'int',
                'label' => 'Download stock availability',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'default' => '0',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1,
                'option' => array (
                    'values' =>
                        array (
                            0 => 'Yes',
                            1 => 'No',
                        ),
                ),
            ],
            'z_order_review' => [
                'type' => 'varchar',
                'label' => 'Order review',
                'input' => 'boolean',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'default' => '0',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1,
                'option' => array (
                    'values' =>
                        array (
                            0 => 'Yes',
                            1 => 'No',
                        ),
                ),
            ],
            'z_purchase_period' => [
                'type' => 'varchar',
                'label' => 'Purchase period',
                'input' => 'text',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1

            ],
            'z_sap_account_number' => [
                'type' => 'varchar',
                'label' => 'Sap Account number',
                'input' => 'text',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1
            ],
            'z_sap_code' => [
                'type' => 'varchar',
                'label' => 'Sap code',
                'input' => 'text',
                'sort_order' => 100,
                'position' => 100,
                'required' => false,
                'adminhtml_only' => 1,
                'system' => false,
                'is_user_defined' =>1
            ]
        ];
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        foreach ($attributes as $code => $options) {
            $customerSetup->addAttribute(Customer::ENTITY, $code, $options);
            //add attribute to attribute set
            $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $code)
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer'],
            ]);

            $attribute->save();
        }
    }
}
