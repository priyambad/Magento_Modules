<?php
/**
 * Copyright � Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Redington\AddressApproval\Plugin;

class AdminForm
{

    protected $store;
    protected $_coreRegistry;
    public function __construct(\Magento\Store\Model\System\Store $store,\Magento\Framework\Registry $registry)
    {
        $this->store = $store;
        $this->_coreRegistry = $registry;
    }

    /**
     * Get form HTML
     *
     * @return string
     */

    public function aroundGetFormHtml(
        \Magento\User\Block\User\Edit\Tab\Main $subject,
        \Closure $proceed
    )
    {
        $form = $subject->getForm();
        /** @var $model \Magento\User\Model\User */
        $model = $this->_coreRegistry->registry('permissions_user');
        if (is_object($form)) {
            $fieldset = $form->addFieldset('admin_user_website', ['legend' => __('Website permission')]);
     
               $fieldset->addField(
                'store_permission',
                'select', //Add multi select if required
                [
                    'name' => 'store_permission',
                    'label' => __('Store Permission'),
                    'title' => __('Store Permission'),
                    'note' => __(''),
                    'value' => $model->getUserId() ? $model->getData('store_permission') : '',
                    'values' => $this->store->getStoreValuesForForm(),

                ]
            );
                  
                 
            $subject->setForm($form);
        }

        return $proceed();
    }
}