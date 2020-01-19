<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */
namespace Redington\Company\Controller\Adminhtml\Role;
/**
 * Class Index for execute method
 */
class Index extends \Magento\Backend\App\Action
{
     /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::roles_view';

    /**
     * Roles and permissions grid.
     *
     * @return void
     * @throws \RuntimeException
     */
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Role controller page.
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Roles and Permissions')));

        return $resultPage;
    }
}
