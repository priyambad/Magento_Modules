<?php

namespace Redington\AddressApproval\Controller\Address;

class SaveDefaultRequest extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Redington\AddressApproval\Helper\Data $approvalHeler,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApproval,
        \Redington\AddressApproval\Helper\Data $addressHelper,
        \Redington\Company\Helper\Data $companyHelper,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Redington\Company\Model\ResourceModel\Documents\CollectionFactory $documentCollectionFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize
        ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->approvalHeler = $approvalHeler;
        $this->addressRepository = $addressRepository;
        $this->addressApprovalFactory = $addressApproval;
        $this->addressHelper = $addressHelper;
        $this->companyHelper = $companyHelper;
        $this->companyUser = $companyUser;
        $this->documentCollectionFactory = $documentCollectionFactory;
        $this->serialize = $serialize;
        parent::__construct($context);
    }

    public function execute()
    {
        $postData = $this->request->getParams();
        $redirectUrl = '';
        $type = '';
        try {
            $addressId = $this->customerSession->getAddressRequestId();
            $approvalRequestId = $this->customerSession->getApprovalRequestId();
            $address = $this->addressRepository->getById($addressId);
            $addressApproval = $this->addressApprovalFactory->create();
            $approvalRequest = $this->addressApprovalFactory->create()->load($approvalRequestId);
            $addressType = $this->customerSession->getAddressType();

            if ($addressType == 'billing') {
                $redirectUrl = 'customer/address/index';
                $type = 'update default billing address documents';
                $emailSubject = 'Request for ';
                $emailTitle = 'You have submitted updated documents for below default billing address - ';
            } else {
                if ($addressType == 'shipping') {
                    $redirectUrl = 'customer/address/index';
                    $type = 'update default shipping address documents';
                    $emailSubject = 'Request for ';
                    $emailTitle = 'You have submitted updated documents for below default shipping address - ';
                } else {
                    return false;
                }
            }

            $approvalRequest->setStatus(0)->save();
        } catch (\Exception $e) {
            $this->addressHelper->logMessage('could not process the request ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Unable to process the request for ' . $type));
            $this->_redirect($redirectUrl);
        }
        $parentId = $approvalRequest->getParentId();
        $requestedBy= $approvalRequest->getRequestedBy();
        $this->approvalHeler->sendApprovalRequestMail($emailSubject,$emailTitle,$type, $address, $parentId, $requestedBy, $this->companyHelper->retrieveCompanyName());
        $this->_redirect($redirectUrl);
    }
}
