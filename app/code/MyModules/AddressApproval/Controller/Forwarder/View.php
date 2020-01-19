<?php

namespace Redington\AddressApproval\Controller\Forwarder;

class View extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $approvalId = $this->request->getParam('entity_id');
        $addressId = $this->request->getParam('address_id');
        $this->_coreRegistry->register('address_approval_id', $approvalId);
        $this->_coreRegistry->register('address_id', $addressId);
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Forwarder Status')));
        return $resultPage;
    }
}