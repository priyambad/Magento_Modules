<?php

namespace Redington\AddressApproval\Controller\Address;

class SetDocData extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Redington\AddressApproval\Model\AddressApprovalFactory $addressApprovalFactory,
        \Redington\AddressApproval\Model\ForwarderApprovalFactory $forwarderApprovalFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->request = $request;
        $this->addressApprovalFactory = $addressApprovalFactory;
        $this->forwarderApprovalFactory = $forwarderApprovalFactory;
        $this->serialize = $serialize;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        $postData = $this->request->getParams();

        $addressId = $postData['addressId'];
      
        $documentCode =$postData['documentCode'];
        $documentName = $postData['documentName'];
        $fileName = $postData['fileName'];
        $documentNumber = $postData['documentNumber'];
        $documentCaption = $postData['documentCaption'];
        $docUrl = $postData['docUrl'];
        $isDocumentRequired = $postData['isDocumentRequired'];
        $isDateRequired = $postData['isDateRequired'];
        $documentIssue = $postData['documentIssue'];
        $documentExpiry = $postData['documentExpiry'];
        $docAttribute = $postData['docAttribute'];
        $isForwarder = $postData['is_forwarder'];

        $this->customerSession->setDocumentName($documentName);
        $this->customerSession->setDocumentNumber($documentNumber);
        $this->customerSession->setDocUrl($docUrl);
        $this->customerSession->setDocumentExpiry($documentExpiry);
        $this->customerSession->setDocumentIssue($documentIssue);

        if($isForwarder == 'true') {

            $approvalObj = $this->getForwarderApprovalData($addressId);
           
        }else {

            $approvalObj = $this->getAddressApprovalData($addressId);
            
        }

        $previousDocData = $approvalObj->getPendingDocuments();
        if(!$previousDocData){
            $previousDocData = [];
        }else {
                $previousDocData = $this->serialize->unserialize($previousDocData);
        }
        
        $previousDocData[$documentCode] = [
            'documentName' => $documentName,
            'fileName' => $fileName,
            'documentCaption' => $documentCaption,
            'documentNumber' => $documentNumber,
            'docUrl' => $docUrl,
            'isDocumentRequired' => $isDocumentRequired,
            'isDateRequired' => $isDateRequired,
            'documentIssue' => $documentIssue,
            'documentExpiry' => $documentExpiry,
            'docAttribute' => $docAttribute
        ];
        // echo $approvalObj->getEntityId();exit; 
        try{
            $approvalObj->setPendingDocuments($this->serialize->serialize($previousDocData));
            $approvalObj->save();
        }catch(\Exception $e){
            echo $e->getMessage();exit;
        }
    }
    public function getAddressApprovalData($address_id) {
        $addressApproval = $this->addressApprovalFactory->create();
        $existInQueue = $addressApproval->getCollection()->addFieldToFilter('address_id',$address_id);
       
        if($existInQueue->count() > 0) {
                  
            $addressApproval = $existInQueue->getFirstItem();
            return $addressApproval;
        }
        return false;
    }
    public function getForwarderApprovalData($address_id) {
        $forwarderApproval = $this->forwarderApprovalFactory->create();
        $existInQueue = $forwarderApproval->getCollection()->addFieldToFilter('address_id',$address_id);
        if($existInQueue->count() > 0) {
            $forwarderApproval = $existInQueue->getFirstItem();
            return $forwarderApproval;
        }
        return false;
    }
}