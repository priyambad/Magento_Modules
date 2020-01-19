<?php

namespace Redington\AddressApproval\Controller\Adminhtml\Forwarder;

class View extends \Magento\Backend\App\Action
{
	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\App\RequestInterface $request,
		\Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Magento\Framework\Registry $registry
	)
	{
		parent::__construct($context);
		$this->request = $request;
		$this->_addressRepository = $addressRepository;
		$this->resultPageFactory = $resultPageFactory;
		$this->_coreRegistry = $registry;
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();

		$addressId = $this->request->getParam('id');
        $address = $this->_addressRepository->getById($addressId);
        $this->_coreRegistry->register('address_approval', $address);
        
        $resultPage->getConfig()->getTitle()->prepend(__('Forwarder Approval'));
        return $resultPage;
	}

}