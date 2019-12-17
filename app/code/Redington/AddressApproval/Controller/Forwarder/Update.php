<?php

namespace Redington\AddressApproval\Controller\Forwarder;

class Update extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApprovalFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->_coreRegistry = $registry;
        $this->customerSession = $customerSession;
        $this->addressRepository = $addressRepository;
        $this->forwarderApprovalFactory = $forwarderApprovalFactory;
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
        $block = $resultPage->getLayout()->getBlock('address_book');
        $this->customerSession->setAddressRequest('forwarder_update_documents');
        $this->customerSession->setIsForwarderRequest('1');
        $this->customerSession->setAddressRequestId($this->request->getParam('id'));
        $this->customerSession->setApprovalRequestId($this->request->getParam('entity_id'));

        // Set request status as Incomplete.
        //$this->forwarderApprovalFactory->create()->load($this->request->getParam('entity_id'))->setStatus(100)->save();

        $resultPage = $this->resultPageFactory->create();
    $resultPage->getConfig()->getTitle()->set('Update Forwarder');

        return $resultPage;
    }
}
