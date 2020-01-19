<?php

namespace Redington\Transactions\Helper;

class Data {
    public function __construct(
            \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
            \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
            \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
        ) {
        $this->storeManager = $storeManagerInterface;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->scopeConfig = $scopeConfig;
    }
    

    public function sendApprovalRequestMail($requestAmount,$companyAdminId,$requestedById,$companyName) {
        $companyAdmin = $this->customerRepositoryInterface->getById($companyAdminId);
        $requestedBy = $this->customerRepositoryInterface->getById($requestedById);
        $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->sendRequestMailToCompanyAdmin($requestAmount,$companyAdmin,$requestedBy);
        $this->sendRequestMailToRedingtonAdmin($requestAmount,$requestedBy,$companyName);
    }

    public function sendRequestMailToCompanyAdmin($requestAmount,$companyAdmin,$requestedBy) {
        $admin = $companyAdmin->getFirstName().' '.$companyAdmin->getLastName();
        $adminEmail=$companyAdmin->getEmail();
        $userEmail=$requestedBy->getEmail();
        $user = $requestedBy->getFirstName().' '.$requestedBy->getLastName();
        $admin = $adminEmail==$userEmail ? $companyAdmin->getFirstName().' '.$companyAdmin->getLastName() :$user;
        if($admin == $user){
            $user = NULL;
        }
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
                            'requestAmount' => $requestAmount,
                            'companyAdmin' => $admin,
                            'requestedBy' => $user,
                        );
        $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
        $this->inlineTranslation->suspend();
        $to = array($companyAdmin->getEmail());
        $cc = array($requestedBy->getEmail());
        if($adminEmail==$userEmail)
        {
            $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_credit/general/request_partner',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
        }
        else
        {
            $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_credit/general/request_partner',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->addCc($cc)
                        ->getTransport();
        }
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    public function sendRequestMailToRedingtonAdmin($requestAmount,$requestedBy,$companyName) {
        $receipientMail = $this->scopeConfig->getValue('redington_credit/general/recipients_email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $receipientEMail = explode(',', $receipientMail);
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
                            'requestAmount' => $requestAmount,
                            'requestedBy' => $requestedBy->getFirstName().' '.$requestedBy->getLastName(),
                            'partner'=>$companyName
                        );
        $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
        $this->inlineTranslation->suspend();
        $to = $receipientEMail;
        $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_credit/general/request_admin',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    public function sendApprovalActionMail($requestAmount,$companyAdmin,$requestedBy,$approvalAction,$availableCreditLimit,$comment) {
        $companyAdmin = $this->customerRepositoryInterface->getById($companyAdmin);
        $requestedBy = $this->customerRepositoryInterface->getById($requestedBy);

        $adminEmail=$companyAdmin->getEmail();
        $userEmail=$requestedBy->getEmail();
        $user = $requestedBy->getFirstName().' '.$requestedBy->getLastName();
        $admin = $adminEmail==$userEmail ? $companyAdmin->getFirstName().' '.$companyAdmin->getLastName() :$user;

        $status = $approvalAction == 'approved' ? "has been approved" : "could not be approved";
        $isApproved = $approvalAction == 'approved' ? true : false;
        $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
                            'requestAmount' => $requestAmount,
                            'approvalAction' => $approvalAction,
                            'companyAdmin' => $admin,
                            'requestedBy' =>$requestedBy->getFirstName().' '.$requestedBy->getLastName(),
                            'creditLimit' => $availableCreditLimit,
                            'comment'=>$comment,
                            'status' => $status,
                            'isApproved' => $isApproved
                        );
        $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
        $this->inlineTranslation->suspend();
        $to = array($companyAdmin->getEmail());
        $cc = array($requestedBy->getEmail());
         if($adminEmail==$userEmail)
        {
             $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_credit/general/approval_action',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
        }
        else
        {
             $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_credit/general/approval_action',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->addCc($cc)
                        ->getTransport();
        }
       
        $transport->sendMessage();
        $this->inlineTranslation->resume();

    }

    public function getSapCreditLimit($adminId){

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_setCredit.log');
		$logger = new \Zend\Log\Logger();
        $logger->addWriter($writer); 

        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        $sapAccountNumber = $adminUser->getCustomAttribute('z_sap_account_number');
        $sapAccountNumber = $sapAccountNumber? $sapAccountNumber->getValue() : '';


        //$creditLimitUrl = 'https://prod-33.westeurope.logic.azure.com/workflows/34e4c19da4ce442785d184a12d72f9ae/triggers/manual/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=7IAqTnUIRRy-wQoABbgOtsfbpFJk2UjPCYnn1TjDjlk';
        $creditLimitUrl = $this->scopeConfig->getValue('redington_sap/general/credit_check');
        $data = json_encode(
            [
                "IM_KUNNR" => $sapAccountNumber
            ]
        );   

        $logger->info('get credit request body '.$data); 
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $creditLimitUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $sapResponce = curl_exec($ch);
        curl_close($ch);
        try{
            $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($responseXml);
            $responseArray = json_decode($json,TRUE);
            $logger->info('get credit api response'); 
            $logger->info($responseArray); 
            if($responseArray['E_WAERS']) {
                return $responseArray['ET_CREDIT']['ZSCUST_CREDIT2']['AMOUNT'];
            }else {
                return false;
            }
        }catch(\Exception $e){
            return false;
        }
    }

    public function setCreditLimit($adminId,$creditLimit){

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_setCredit.log');
		$logger = new \Zend\Log\Logger();
        $logger->addWriter($writer); 
        $adminUser = $this->customerRepositoryInterface->getById($adminId);
        $sapAccountNumber = $adminUser->getCustomAttribute('z_sap_account_number');
        $sapAccountNumber = $sapAccountNumber? $sapAccountNumber->getValue() : '';


        //$creditLimitUrl = 'https://prod-20.westeurope.logic.azure.com:443/workflows/586b95e9c36f41688cb83e7924f9801b/triggers/manual/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=jW6QcreL8EyFh8icGoVTA-1dPD3nlbCi4HJIN-CzJY8';
        $creditLimitUrl = $this->scopeConfig->getValue('redington_sap/general/credit_update');
        $data = json_encode(
            [
                "P_PARTNR" => $sapAccountNumber,
                "P_CLMT" => $creditLimit
            ]
        );   

        $logger->info('set credit Request body '.$data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $creditLimitUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $sapResponce = curl_exec($ch);
        curl_close($ch);
        try{
            $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($responseXml);
            $responseArray = json_decode($json,TRUE);
            $logger->info('set credit api response'); 
            $logger->info($responseArray); 
            if($responseArray['RETURN']) {
                $response =  $responseArray['RETURN']['BAPIRET2']['TYPE'];
                if($response == 'S')
                    return true;
                else
                    return false;
            }else {
                return false;
            }
        }catch(\Exception $e){
            return false;
        }
    }


        public function getOverdueAmount($adminId)
        {

        $overdueUrl = $this->scopeConfig->getValue('redington_sap/general/overdue_api');
        if($overdueUrl){
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_overdue.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer); 

            $customerLoad = $this->customerRepositoryInterface->getById($adminId);
            $sapAccNoObj = $customerLoad->getCustomAttribute('z_sap_account_number');
            $sapCodeObj = $customerLoad->getCustomAttribute('z_sap_code');

            if(!empty($sapAccNoObj))
            {
                $sapAccNo = $sapAccNoObj->getValue();
                
            }
            if(!empty($sapCodeObj))
            {
                $sapCode = $sapCodeObj->getValue();
               
            }
            $sapDataCollection['COMP_CODE'] = $sapCode;
            $sapDataCollection['CUSTOMER'] = $sapAccNo;

            $sapDataCollectionArray['LstZBAPI3007_1'][] = $sapDataCollection;
            
            $sapDataCollectionJson = json_encode($sapDataCollectionArray);
            
            
                

                $data = $sapDataCollectionJson;
                $logger->info('Request:');
                $logger->info(print_r($data, true));
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $overdueUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 400);
                $sapResponce = curl_exec($ch);
                curl_close($ch);
                try {
                    $responseXml = simplexml_load_string($sapResponce, "SimpleXMLElement", LIBXML_NOCDATA);
                    $json = json_encode($responseXml);
                    $json = str_replace("{}", "null", $json);
                    $responseArray = json_decode($json, true);

                    $logger->info('response:');
                    $logger->info(print_r($responseArray, true));
                    if ($responseArray['OVERDUE']) {
                         
                    return $responseArray['OVERDUE']['ZTS_OVERDUE']['LC_AMOUNT'];
                    } else {
                        return false;
                    }
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
    
}
