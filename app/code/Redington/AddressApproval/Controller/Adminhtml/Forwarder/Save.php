<?php

namespace Redington\AddressApproval\Controller\Adminhtml\Forwarder;

class Save extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApprovalFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Redington\AddressApproval\Helper\Data $approvalHelper
    )
    {
        parent::__construct($context);
        $this->request = $request;
        $this->_addressRepository = $addressRepository;
        $this->forwarderApprovalFactory = $forwarderApprovalFactory;
        $this->date = $date;
        $this->serialize = $serialize;
        $this->authSession = $authSession;
        $this->approvalHelper = $approvalHelper;
    }

    public function execute()
    {
        $addressId = $this->request->getParam('entity_id');
        $status = $this->request->getParam('status');
        $comment = $this->request->getParam('comment');


        $addressApproval = $this->forwarderApprovalFactory->create();
        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id',$addressId);
        if($existInQueue->count() > 0) {
            $addressApproval = $existInQueue->getFirstItem();
            $companyAdminId = $addressApproval->getParentId();
            $requestedById = $addressApproval->getRequestedBy();

            if($comment != '') {
                $commentData = [
                    "time" => $this->date->gmtDate(),
                    "author" => $this->authSession->getUser()->getFirstName().' '.$this->authSession->getUser()->getLastName(),
                    "content" => $comment
                ];
                //get old comments
                try {
                    $previousComments = $addressApproval->getComments();
                    if($previousComments == null){
                        $previousComments = [];
                    }
                    else {
                        $previousComments = $this->serialize->unserialize($previousComments);
                    }
                }catch(\Exception $e){
                    $previousComments = [];
                }
                array_unshift($previousComments, $commentData);
                $addressApproval->setComments($this->serialize->serialize($previousComments));
            }
        }
        $address = $this->_addressRepository->getById($addressId);

        if($status ==2) {
             $approvedDocuments = $addressApproval->getPendingDocuments();
           $addressApproval->setDocuments($approvedDocuments);
             $address = $this->_addressRepository->getById($addressId);
            $sapAddressId = $address->getCustomAttribute('sap_address_id') ? $address->getCustomAttribute('sap_address_id')->getValue() : null;

            if(!$sapAddressId){
            $sapAddressNumber = $this->approvalHelper->sendAddressDataToSap($address,'forwarder',$companyAdminId);
            if($sapAddressNumber){
                $address = $this->_addressRepository->getById($addressId);
                $address->setCustomAttribute('approved',$status-1);
                $address->setCustomAttribute('is_valid',1);
                $address->setCustomAttribute('sap_address_id',$sapAddressNumber);
                $this->_addressRepository->save($address);
                //set status
                $addressApproval->setStatus($status);
                $addressApproval->save();
                $this->approvalHelper->sendApprovalActionMail('forwarder',$address,$companyAdminId,$requestedById,'approved',$comment);
                $this->messageManager->addSuccessMessage(__('Forwarder Approved.'));
            }else{
                $this->messageManager->addError(__('Could not process the request.'));
            }
        }else{
                $address = $this->_addressRepository->getById($addressId);
                $address->setCustomAttribute('approved',$status-1);
                $address->setCustomAttribute('is_valid',1);
               
                $this->_addressRepository->save($address);
                //set status
                $addressApproval->setStatus($status);
                $addressApproval->save();
                $this->approvalHelper->sendApprovalActionMail('forwarder',$address,$companyAdminId,$requestedById,'approved',$comment);
                $this->messageManager->addSuccessMessage(__('Forwarder Approved.'));

        }
        }else {
            $address = $this->_addressRepository->getById($addressId);
            $address->setCustomAttribute('approved',$status-1);
            $this->_addressRepository->save($address);
            //set status
            $addressApproval->setStatus($status);
            $addressApproval->save();
            $this->approvalHelper->sendApprovalActionMail('forwarder',$address,$companyAdminId,$requestedById,'rejected',$comment);
            $this->messageManager->addSuccessMessage(__('Forwarder Rejected.'));
        }
        
        $this->_redirect('approval/forwarder/index');
    }

}