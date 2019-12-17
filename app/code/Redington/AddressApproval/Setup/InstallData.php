<?php
namespace Redington\AddressApproval\Setup;
 
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
 
class InstallData implements InstallDataInterface
{
    private $customerSetupFactory;
    private $attributeSetFactory;
 
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }
 
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
 
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
 
        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
 
        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
 
        $customerSetup->addAttribute('customer_address', 'approved', [
            'type' => 'int',
            'label' => 'Approved',
            'input' => 'boolean',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
            'default' => 0,
            'sort_order' => 100,
            'position' => 100,
            'required' => false,
            'adminhtml_only' => 0,
            'is_visible' => false,
            'system' => false,
            'is_user_defined' =>1,
            'option' => array (
                'values' =>
                    array (
                        0 => 'Yes',
                        1 => 'No',
                    ),
            ),
        ]);
 
        $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'approved')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
            ]);

        $customerSetup->addAttribute('customer_address', 'sap_address_id', [
            'type' => 'varchar',
            'label' => 'Sap Address Id',
            'input' => 'text',
            'default' => '',
            'sort_order' => 100,
            'position' => 100,
            'required' => false,
            'is_visible' => false,
            'system' => false,
            'is_user_defined' =>1
        ]);
    
        $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'sap_address_id')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
            ]);

        $customerSetup->addAttribute('customer_address', 'delivery_plant', [
            'type' => 'varchar',
            'label' => 'Delivery Plant',
            'input' => 'text',
            'default' => '',
            'sort_order' => 100,
            'position' => 100,
            'required' => false,
            'is_visible' => false,
            'system' => false,
            'is_user_defined' =>1
        ]);
    
        $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'delivery_plant')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
            ]);
 
        $attribute->save();
    }
}