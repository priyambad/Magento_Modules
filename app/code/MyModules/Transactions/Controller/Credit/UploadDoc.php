<?php

namespace Redington\Transactions\Controller\Credit;

class UploadDoc extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Redington\Configuration\Helper\Data $helperData,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->request = $request;
        $this->helperData = $helperData;
        $this->scopeConfig = $scopeConfig;
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
        $companyName = $postData['companyName'];
        
        $container = $this->scopeConfig->getValue('redington_documents/general/container_path');
        
        $containerPath = $container.$companyName.'/Credit'.'/'.$creditId;
        if($_FILES) :
            try{
                foreach ($_FILES as $code => $file) :
                    $name = $file['name'];
                    $content = file_get_contents($file['tmp_name']);
                    $type = $file['type'];
                    $savedFile = $this->helperData->saveBlobStorage($content,$name,$type,$containerPath);
                endforeach;
                $response = [
                    'success' => true,
                    'doc_url' => $savedFile
                ];
                return $resultJson->setData($response);
            } catch (Exception $ex) {
                $response = [
                    'success' => false,
                    'message' => $ex->getMessage()
                ];
                return $resultJson->setData($response);
            }
        endif;
    }
}