<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */

namespace Redington\Company\Controller\Adminhtml\Role;

/**
 * Class Edit.
 */
class Edit extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::roles_edit';

    /**
     * Roles and permissions edit.
     *
     * @throws \RuntimeException
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->loadLayoutUpdates();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Add New Role'));
        if ($this->getRequest()->getParam('id')) {
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Edit Role'));
        }
        $this->_view->renderLayout();
    }
}
