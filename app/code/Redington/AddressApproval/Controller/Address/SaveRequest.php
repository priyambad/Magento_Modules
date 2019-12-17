<?php

namespace Redington\AddressApproval\Controller\Address;

class SaveRequest extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Redington\AddressApproval\Helper\Data $approvalHeler,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApproval,
        \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApproval,
        \Redington\AddressApproval\Helper\Data $addressHelper,
        \Redington\Company\Helper\Data $companyHelper
        ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->customerSession = $customerSession;
        $this->approvalHeler = $approvalHeler;
        $this->addressRepository = $addressRepository;
        $this->addressApprovalFactory = $addressApproval;
        $this->forwarderApprovalFactory = $forwarderApproval;
        $this->addressHelper = $addressHelper;
        $this->companyHelper = $companyHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $redirectUrl = '';
        $type = '';
        try {
            $addressId = $this->customerSession->getAddressRequestId();
            $address = $this->addressRepository->getById($addressId);
            $addressRequest = $this->customerSession->getAddressRequest();
            $approvalRequestId = $this->customerSession->getApprovalRequestId();

            $isForwarder = $this->customerSession->getIsForwarderRequest();

            if ($isForwarder) {
                $approvalRequest = $this->forwarderApprovalFactory->create()->load($approvalRequestId);
                $redirectUrl = 'customer/forwarder/index';
               
                 if($this->customerSession->getAddressRequest() == 'forwarder_update_documents') {
                    $type = 'update documents';
                    $emailSubject = 'Request for ';
                    $emailTitle = 'You have submitted updated documents for below forwarder - ';
                }else if($this->customerSession->getAddressRequest() == 'forwarder_edit_documents') {
                    $type = 'update forwarder';
                    $emailSubject = 'Request for ';
                    $emailTitle = 'You have requested for update forwarder.Below are the details - ';
                }else{
                     $type = 'new forwarder';
                     $emailSubject = 'Request for ';
                     $emailTitle = 'You have requested new forwarder.Below are the details - ';
                }
            } else {
                $approvalRequest = $this->addressApprovalFactory->create()->load($approvalRequestId);
                $redirectUrl = 'customer/address/index';
                if($this->customerSession->getAddressRequest() == 'update_documents') {
                    $type = 'update documents';
                    $emailSubject = 'Request for ';
                    $emailTitle = 'You have submitted updated documents for below address - ';
                }else if($this->customerSession->getAddressRequest() == 'edit_documents') {
                    $type = 'update address';
                    $emailSubject = 'Request for ';
                    $emailTitle = 'You have requested to update shipping address.Below are the details - ';
                      
                }else{
                    $type = 'new shipping address';
                    $emailSubject = 'Request for ';
                    $emailTitle = 'You have requested new shipping address.Below are the details - ';
                     
                }
            }

            // Set request as pending
            $approvalRequest->setStatus(0);
            $approvalRequest->save();
            // Set address as not approved.
            $address->setCustomAttribute('approved', 0);
            $this->addressRepository->save($address);
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
