<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Setup;

use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Customer\Setup\CustomerSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
	/**
     * @var\Magento\Customer\Setup\CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * Constructor
     *
     * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {

        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
		//$customerSetup->removeAttribute('customer_address', 'is_forwarder');
        if (version_compare($context->getVersion(), "1.0.2", "<")) {

            $customerSetup->addAttribute('customer_address', 'is_forwarder', [
                'type' => 'int',
				'input' => 'checkbox',
				'label' => 'Is Forwarder',
				'input' => 'boolean',
				'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
				'default' => '0',
				'sort_order' => 100,
				'position' => 100,
				'required' => false,
				'adminhtml_only' => 1,
				'system' => false,
				'is_user_defined' =>1,
				'visible' => false,
				'option' => array (
					'values' =>
						array (
							0 => 'Yes',
							1 => 'No',
						),
				),
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'is_forwarder')
            ->addData(['used_in_forms' => [
                    'adminhtml_customer_address',
					'customer_address_edit',
					'customer_register_address'
                ]
            ]);
            $attribute->save();
        }
        if (version_compare($context->getVersion(), "1.0.7", "<")) {

            $customerSetup->addAttribute('customer_address', 'is_valid', [
                'type' => 'int',
				'input' => 'checkbox',
				'label' => 'Is valid',
				'input' => 'boolean',
				'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
				'default' => '0',
				'sort_order' => 100,
				'position' => 100,
				'required' => false,
				'adminhtml_only' => 1,
				'system' => false,
				'is_user_defined' =>1,
				'visible' => false,
				'option' => array (
					'values' =>
						array (
							0 => 'Yes',
							1 => 'No',
						),
				),
            ]);

            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', 'is_forwarder')
            ->addData(['used_in_forms' => [
                    'adminhtml_customer_address',
					'customer_address_edit',
					'customer_register_address'
                ]
            ]);
            $attribute->save();
        }
    }
}