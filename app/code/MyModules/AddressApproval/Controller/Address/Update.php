<?php

namespace Redington\AddressApproval\Controller\Address;

class Update extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->_coreRegistry = $registry;
        $this->customerSession = $customerSession;
        $this->addressRepository = $addressRepository;
        $this->addressApprovalFactory = $addressApprovalFactory;
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
        $this->customerSession->setAddressRequest('update_documents');
        $this->customerSession->setIsForwarderRequest('0');
        $this->customerSession->setAddressRequestId($this->request->getParam('id'));
        $this->customerSession->setApprovalRequestId($this->request->getParam('entity_id'));

        // Set request status as Incomplete.
        // $this->addressApprovalFactory->create()->load($this->request->getParam('entity_id'))->setStatus(100)->save();

        // $address = $this->addressRepository->getById($this->request->getParam('id'));
        // $address->setCustomAttribute('approved',0);
        // $this->addressRepository->save($address);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set('Update Address');

        return $resultPage;
    }
}
