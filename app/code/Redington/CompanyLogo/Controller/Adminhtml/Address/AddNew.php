<?php
/**
 * Copyright © Redington. All rights reserved.
 *
 */
namespace Redington\CompanyLogo\Controller\Adminhtml\Address;


class AddNew extends \Magento\Backend\App\Action
{
  /**
     * Undocumented function
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Backend\Model\Auth\Session $adminSession
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Backend\Model\Auth\Session $adminSession
    ) {
		
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_adminSession = $adminSession;
		
     }
    /**
     * Undocumented function
     *
     * @return void
     */
    public function execute() {
		$this->_adminSession->setRequest("addnew");
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('New Record')));

        return $resultPage;
    }
}
