<?php

namespace Redington\AddressApproval\Controller\Address;

class UploadDoc extends \Magento\Framework\App\Action\Action {
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Magento\Framework\App\RequestInterface $request,
        \Redington\Configuration\Helper\Data $helperData,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Company\Model\CompanyFactory $companyFactory
    ) {
        $this->resultJsonFactory = $jsonFactory;
        $this->companyUser = $companyUser;
        $this->request = $request;
        $this->helperData = $helperData;
        $this->scopeConfig = $scopeConfig;
        $this->companyFactory = $companyFactory;
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
        $companyId = $this->companyUser->getCurrentCompanyId();
        $companyName = $this->companyFactory->create()->load($companyId)->getData('company_name');
        $postData = $this->request->getParams();
        $addressId = $postData['addressId'];
       
        
        //$companyName = $postData['companyName'];

        $container = $this->scopeConfig->getValue('redington_documents/general/container_path');
       
        $containerPath = $container.$companyName.'/'.$addressId;
        
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
            }

        endif;
    }
}