<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CompanyLogo
 */
namespace Redington\CompanyLogo\Controller\Adminhtml\Address;

/**
 * Class Index for execute method
 */
class Index extends \Magento\Backend\App\Action
{

    /**
     * Payment Group grid.
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
        $resultPage->getConfig()->getTitle()->prepend((__('Company Addresses')));

        return $resultPage;
    }
}
