<?php

namespace Redington\Category\Controller\Category;

class CategoryNotAllowed extends \Magento\Framework\App\Action\Action
{
    /**
     * construct function
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
        
    ) {
        $this->resultPageFactory = $resultPageFactory;
       
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set('Not Authorised');
        return $resultPage;
    }
}
