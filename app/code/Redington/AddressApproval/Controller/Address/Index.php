<?php

namespace Redington\AddressApproval\Controller\Address;

class Index extends \Magento\Customer\Controller\Address\Index {
    public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Customer\Model\Session $customerSession,
            \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
            \Magento\Customer\Model\Metadata\FormFactory $formFactory,
            \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
            \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
            \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
            \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
            \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
            \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
        ) {
        parent::__construct($context, $customerSession, $formKeyValidator, $formFactory, $addressRepository, $addressDataFactory, $regionDataFactory, $dataProcessor, $dataObjectHelper, $resultForwardFactory, $resultPageFactory, $customerRepository);
    }
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()->getBlock('address_book');
        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }
        return $resultPage;
    }
}
