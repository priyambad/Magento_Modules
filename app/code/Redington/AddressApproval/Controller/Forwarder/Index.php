<?php

namespace Redington\AddressApproval\Controller\Forwarder;

class Index extends \Magento\Framework\App\Action\Action {
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
        $resultPage->getConfig()->getTitle()->set('Forwarder Book');
        return $resultPage;
    }
}