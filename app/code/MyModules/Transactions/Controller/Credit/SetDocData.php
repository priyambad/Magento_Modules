<?php

namespace Redington\Transactions\Controller\Credit;

class SetDocData extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Redington\Transactions\Model\CreditFactory $creditFactory,
        \Magento\Framework\Serialize\Serializer\Serialize $serialize
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->request = $request;
        $this->creditFactory = $creditFactory;
        $this->serialize = $serialize;
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

        $creditId = $postData['creditId'];
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
        
        $credit = $this->creditFactory->create()->load($creditId);

        $previousDocData = $credit->getDocuments();
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
            'documentExpiry' => $documentExpiry
        ];
        
        $credit->setDocuments($this->serialize->serialize($previousDocData));
        $credit->save();
    }
}