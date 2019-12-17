<?php

namespace Redington\AddressApproval\Helper;

class Data {
    /**
     * construct function
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory
     */
    public function __construct(
            \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
            \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
            \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
            \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
            \Magento\Directory\Model\CountryFactory $countryFactory,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Redington\Warehouse\Model\ResourceModel\Distribution\CollectionFactory $distributionCollectionFactory,
            \Magento\Customer\Model\Session $customerSession
        ) {
        $this->storeManager = $storeManagerInterface;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->countryFactory = $countryFactory;
        $this->scopeConfig = $scopeConfig;
        $this->distributionCollectionFactory = $distributionCollectionFactory;
        $this->customerSession = $customerSession;
    }
    

    /**
     * sendApprovalRequestMail function
     *
     * @param [type] $emailSubject
     * @param [type] $emailTitle
     * @param [type] $requestType
     * @param [type] $address
     * @param [type] $companyAdminId
     * @param [type] $requestedById
     * @param [type] $companyName
     * @return void
     */
    public function sendApprovalRequestMail($emailSubject,$emailTitle,$requestType,$address,$companyAdminId,$requestedById,$companyName) {
        $companyAdmin = $this->customerRepositoryInterface->getById($companyAdminId);
        $requestedBy = $this->customerRepositoryInterface->getById($requestedById);
        $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->sendRequestMailToCompanyAdmin($emailSubject,$emailTitle,$requestType,$address,$companyAdmin,$requestedBy);
        $this->sendRequestMailToRedingtonAdmin($requestType,$address,$companyAdmin,$requestedBy,$companyName);
    }

    /**
     * sendRequestMailToCompanyAdmin function
     *
     * @param [type] $emailSubject
     * @param [type] $emailTitle
     * @param [type] $requestType
     * @param [type] $address
     * @param [type] $companyAdmin
     * @param [type] $requestedBy
     * @return void
     */
    public function sendRequestMailToCompanyAdmin($emailSubject,$emailTitle,$requestType,$address,$companyAdmin,$requestedBy) {
      $region = '';
       $city ='';
        if(empty($address->getRegion()->getRegion())){
           $region = $address->getCity() ? $address->getCity() : null;
           $city ='';
        }else{
            $region = $address->getRegion()->getRegion();
            $city = $address->getCity() ? $address->getCity() : null;
        }
        $admin = $companyAdmin->getFirstName().' '.$companyAdmin->getLastName();
        $user = $requestedBy->getFirstName().' '.$requestedBy->getLastName();
        $adminEmail=$companyAdmin->getEmail();
        $userEmail=$requestedBy->getEmail();
        $user = $requestedBy->getFirstName().' '.$requestedBy->getLastName();
       	$admin = $adminEmail==$userEmail ? $companyAdmin->getFirstName().' '.$companyAdmin->getLastName() :$user;

        if($admin == $user){
            $user = NULL;
        }

        $isForwarder = $requestType == "Forwarder" ? true : false;
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
                            'requestType' => $requestType,
                            'isForwarder' => $isForwarder,
                            'companyAdmin' => $admin,
                            'requestedBy' => $user,
                            'company' => $address->getCompany(),
                            'street1' => $address->getStreet()[0],
                            'street2' => count($address->getStreet()) > 1 ? $address->getStreet()[1] : null,
                            'region' =>$region,
                            'city' => $city,
                            'country' => $this->countryFactory->create()->loadByCode($address->getCountryId())->getName(),
                            'postalcode' => $address->getPostcode(),
                            'mobile'  => $address->getTelephone(),
                            'emailSubject' => $emailSubject,
                            'emailTitle' => $emailTitle
                        );
        $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
        $this->inlineTranslation->suspend();
        $to = array($companyAdmin->getEmail());
        $cc =array($requestedBy->getEmail());
        if($adminEmail==$userEmail)
        {
        	$transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_approval/general/request_partner',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
        }
        else
        {
        	$transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_approval/general/request_partner',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
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

    /**
     * sendRequestMailToRedingtonAdmin function
     *
     * @param [type] $requestType
     * @param [type] $address
     * @param [type] $companyAdmin
     * @param [type] $requestedBy
     * @param [type] $companyName
     * @return void
     */
    public function sendRequestMailToRedingtonAdmin($requestType,$address,$companyAdmin,$requestedBy,$companyName) {
       $region = '';
       $city ='';
        if(empty($address->getRegion()->getRegion())){
           $region = $address->getCity() ? $address->getCity() : null;
           $city ='';
        }else{
            $region = $address->getRegion()->getRegion();
            $city = $address->getCity() ? $address->getCity() : null;
        }
        $isForwarder = $requestType == "Forwarder" ? true : false;
        $receipientMail = $this->scopeConfig->getValue('redington_approval/general/recipients_email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $receipientEMail = explode(',', $receipientMail);
       
        
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
                            'requestType' => $requestType,
                            'isForwarder' => $isForwarder,
                            'companyAdmin' => $companyAdmin->getFirstName().' '.$companyAdmin->getLastName(),
                            'requestedBy' => $requestedBy->getFirstName().' '.$requestedBy->getLastName(),
                            'company' => $address->getCompany(),
                            'street1' => $address->getStreet()[0],
                            'street2' => count($address->getStreet()) > 1 ? $address->getStreet()[1] : null,
                            'region' =>$region,
                            'city' => $city,
                            'country' => $this->countryFactory->create()->loadByCode($address->getCountryId())->getName(),
                            'postalcode' => $address->getPostcode(),
                            'mobile'  => $address->getTelephone(),
                            'partner'=>$companyName
                        );
        $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
        $this->inlineTranslation->suspend();
        $to = $receipientEMail;
        $transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_approval/general/request_admin',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    /**
     * sendApprovalActionMail function
     *
     * @param [type] $requestType
     * @param [type] $address
     * @param [type] $companyAdmin
     * @param [type] $requestedBy
     * @param [type] $approvalAction
     * @param [type] $comment
     * @return void
     */
    public function sendApprovalActionMail($requestType,$address,$companyAdmin,$requestedBy,$approvalAction,$comment) {
       
         $region = '';
       $city ='';
        if(empty($address->getRegion()->getRegion())){
           $region = $address->getCity() ? $address->getCity() : null;
           $city ='';
          
        }else{
            $region = $address->getRegion()->getRegion();
            $city = $address->getCity() ? $address->getCity() : null;
            
        }
        $companyAdmin = $this->customerRepositoryInterface->getById($companyAdmin);
        $requestedBy = $this->customerRepositoryInterface->getById($requestedBy);
        $adminEmail=$companyAdmin->getEmail();
        $userEmail=$requestedBy->getEmail();
       
        $user = $requestedBy->getFirstName().' '.$requestedBy->getLastName();
       	$admin = $adminEmail==$userEmail ? $companyAdmin->getFirstName().' '.$companyAdmin->getLastName() :$user;
       
        $isForwarder = $requestType == "forwarder" ? true : false;
        $status = $approvalAction == "approved" ? 'has been approved': 'needs some correction';
        $isApproved = $approvalAction == "approved" ? true : false;
        $this->senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $this->senderName = $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
                            'requestType' => $requestType,
                            'approvalAction' => $approvalAction,
                            'status' => $status,
                            'isApproved' => $isApproved,
                            'isForwarder' => $isForwarder,
                            'companyAdmin' => $admin,
                            'requestedBy' => $user,
                            'company' => $address->getCompany(),
                            'street1' => $address->getStreet()[0],
                            'street2' => count($address->getStreet()) > 1 ? $address->getStreet()[1] : null,
                            'region' =>$region,
                            'city' => $city,
                            'country' => $this->countryFactory->create()->loadByCode($address->getCountryId())->getName(),
                            'postalcode' => $address->getPostcode(),
                            'mobile'  => $address->getTelephone(),
                            'comment' => $comment,
                        );
        $from = array('email' => $this->senderEmail, 'name' => $this->senderName);
        $this->inlineTranslation->suspend();
        $to = array($companyAdmin->getEmail());
        $cc =array($requestedBy->getEmail());
        if($adminEmail==$userEmail)
        {
        	$transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_approval/general/approval_action',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->getTransport();
        
        }
        else
        {
        	$transport = $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('redington_approval/general/approval_action',\Magento\Store\Model\ScopeInterface::SCOPE_STORES))
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
    /**
     * getFormattedAddress function
     *
     * @param [type] $addressObject
     * @return void
     */
    public function getFormattedAddress($addressObject)
    {
        $countryName = $this->countryFactory->create()->loadByCode($addressObject->getCountryId())->getName();
        $completeAddress = $addressObject->getCompany().',|';
        foreach ($addressObject->getStreet() as $key => $streetAddress) {
            if($streetAddress){
                $completeAddress .= $streetAddress.',|';
            }
        }
        $completeAddress .= $addressObject->getCity().'|'.$countryName.', '.$addressObject->getPostCode().'|'.$addressObject->getTelePhone();
        
        $completeAddress = str_replace("|", PHP_EOL, $completeAddress);


        return $completeAddress;
    }
    /**
     * sendAddressDataToSap function
     *
     * @param [type] $address
     * @param [type] $type
     * @param [type] $parentId
     * @return void
     */
    public function sendAddressDataToSap($address, $type, $parentId){

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Redington_shipTo.log');
		$logger = new \Zend\Log\Logger();
        $logger->addWriter($writer); 
        
        //$addressUpdateurl = 'https://prod-98.westeurope.logic.azure.com:443/workflows/ea120040836f4b07a723c9c25d1852f0/triggers/manual/paths/invoke?api-version=2016-10-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=S4DX9gGcUUrQmRvuQF1mnp8Nq_mgoFKRJASnRf17cQ0';
        $addressUpdateurl = $this->scopeConfig->getValue('redington_sap/general/ship_to');
        $data = $this->getRequestData($address, $type, $parentId);
        $data = json_encode($data);
        $logger->info('request Body '.$data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $addressUpdateurl);
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
            $logger->info('response Body ');
            $logger->info(print_r($responseArray,true));
            if($responseArray['EX_OUTPUT']) {
                $response =  $responseArray['EX_OUTPUT']['MSG_TYPE'];
                if($response == 'S')
                    return $responseArray['EX_OUTPUT']['KUNAG'];
                else
                    return false;
            }else {
                return false;
            }
        }catch(\Exception $e){
            return false;
        }
    }

    /**
     * getRequestData function
     *
     * @param [type] $address
     * @param [type] $type
     * @param [type] $parentId
     * @return void
     */
    public function getRequestData($address, $type, $parentId){
        $street = $address->getStreet();
        $streetLen = sizeof($street);
        $this->encodeCompanyName($address->getCompany());
        $typeCode = $type == 'forwarder' ? "IM_FW" : "IM_SHIP";
        $addressData = [ 
            $typeCode => "I",
            "KUNNR" => $this->getSapAccountNumber($parentId),
            "KUNAG" => "",
            "BUKRS" => $this->getSapCode($parentId),
            "VKORG" => $this->getSapCode($parentId),
            "VTWEG" => $this->getDistributionChannel($parentId),
            "SPART" => "0",
            "NAME1" => $this->companyNames[0],
            "NAME2" => sizeof($this->companyNames)>1 ? $this->companyNames[1] : '',
            "ZTERM" => "R001",
            "KDGRP" => "C9",
            "VSBED" => "Z1",
            "INCO1" => "DDP",
            "KTGRD" => "Z1",
            "TAXKD" => $address->getCountryId() == "AE" ? "1" : "0",
            "LICNR" => "12345",
            "DATE_EF" => "20190110",
            "DATE_ET" => "20200110",
            "BRSCH" => $type == 'forwarder' ? "Freight Forwarder" : $this->getSegment($parentId),
            "SH_STR_SUPPL" => $streetLen > 1 ? $this->encodeStreetAddres($street[1]) : "",
            "SH_STR_SUPPL2" => $streetLen > 2 ? $this->encodeStreetAddres($street[2]) : "",
            "SH_STREET" => $this->encodeStreetAddres($street[0]),
            "SH_POST_CODE" => $address->getPostcode(),
            "SH_COUNTRY" => $address->getCountryId(),
            "SH_REGION" => $this->getEncodedRegionId($address),
            "SH_PO_BOX" => "",
            "SH_CITY" => $address->getCity(),
            "SH_TEL_NUMBER" => $address->getTelephone(),
            "SH_MOB_NUMBER" => $address->getTelephone(),
            "SH_SMTP_ADDR" => $this->getAdminEmailId($parentId),
            "C_FIRST_NAME" => $address->getFirstName(),
            "C_LAST_NAME" => $address->getLastName(),
            "C_TEL_NUMBER" => $address->getTelephone(),
            "C_MOB_NUMBER" => $address->getTelephone(),
            "C_SMTP_ADDR" => $this->getAdminEmailId($parentId)
        ];
       
        return $addressData;
    }
    /**
     * getEncodedRegionId function
     *
     * @param [type] $address
     * @return void
     */
    public function getEncodedRegionId($address){
        $number = $address->getRegionId();
        $numberString = (string)$number;
        $numLength =  strlen($numberString);
        for($i=0; $i < 3 - $numLength ; $i++) {
            $numberString = "0".$numberString;
        }
        return $numberString;
    }
    /**
     * encodeCompanyName function
     *
     * @param [type] $companyName
     * @return void
     */
    public function encodeCompanyName($companyName){
        //$companyName = str_replace(":", "C7F6DC",$companyName);
        //$companyName = str_replace("-", "2XEBHF",$companyName);
        //$companyName = str_replace(".", "BQ9D5F",$companyName);
        $companyName = str_replace("/", "B438FA",$companyName);
        $companyName = str_replace("\\", "6A7E93",$companyName);
        //$companyName = str_replace("(", "1XF7E2",$companyName);
        //$companyName = str_replace(")", "C738AD7",$companyName);
        $companyName = str_replace("&", "C2812D",$companyName);

        $this->companyNames = str_split($companyName, 70);
    }

    /**
     * encodeStreetAddres function
     *
     * @param [type] $streetAddress
     * @return $streetAddress
     */
    public function encodeStreetAddres($streetAddress)
    {
        $streetAddress = str_replace("/","B438FA",$streetAddress);
        $streetAddress = str_replace("#","C49D",$streetAddress);
        $streetAddress = str_replace("&","C2812D",$streetAddress);
        //$streetAddress = str_replace("'","E02T",$streetAddress);
        return $streetAddress;
        
    }
    /**
     * getAdminEmailId function
     *
     * @param [type] $parentId
     * @return void
     */
    public function getAdminEmailId($parentId){
        $companyAdmin = $this->customerRepositoryInterface->getById($parentId);
        $email = $companyAdmin->getEmail();
        return $email;
    }
    /**
     * getSegment function
     *
     * @param [type] $parentId
     * @return void
     */
    public function getSegment($parentId){
        $companyAdmin = $this->customerRepositoryInterface->getById($parentId);
        $segment = $companyAdmin->getCustomAttribute('z_segment')->getValue();
        return $segment;
    }
    /**
     * getSapCode function
     *
     * @param [type] $parentId
     * @return void
     */
    public function getSapCode($parentId){
        $companyAdmin = $this->customerRepositoryInterface->getById($parentId);
        $sapAccountCode = $companyAdmin->getCustomAttribute('z_sap_code')->getValue();
        return $sapAccountCode;
    }
    /**
     * getSapAccountNumber function
     *
     * @param [type] $parentId
     * @return void
     */
    public function getSapAccountNumber($parentId){
        $companyAdmin = $this->customerRepositoryInterface->getById($parentId);
        $sapAccountCode = $companyAdmin->getCustomAttribute('z_sap_account_number')->getValue();
        return $sapAccountCode;
    }
    /**
     * getDistributionChannel function
     *
     * @param [type] $parentId
     * @return void
     */
    public function getDistributionChannel($parentId){
        $companyAdmin = $this->customerRepositoryInterface->getById($parentId);
        $storeViewId = $companyAdmin->getStoreId();
        $storeId = $this->storeManager->getStore($storeViewId)->getGroupId();
        $storeCode = $this->storeManager->getGroup($storeId)->getCode();
		$distribution = $this->distributionCollectionFactory->create()->addFieldToFilter('store_code',$storeCode)->getFirstItem();
		return $distribution->getDistributionChannel();
    }
    /**
     * logMessage function
     *
     * @param [type] $message
     * this fuhnction loggs error messages.
     * @return void
     */
    public function logMessage($message) {
        $filePath = '/var/log/Redington_AddressApproval_Exxeptions.log';
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(print_r($message,true));
    }
}
