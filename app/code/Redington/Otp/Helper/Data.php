<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Otp
 */

namespace Redington\Otp\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    /**
     * OTP General configuration
     */
	const OTP_MODULE_GENERAL_SECTION = 'redington_otp/general/';
	
	/**
     * OTP Module Enable configuration
     */
	const OTP_MODULE_MESSAGE_NOTE_SECTION = 'redington_otp/message_note/';
	
    /**
     * OTP Module Enable configuration
     */
    const OTP_MODULE_ENABLED = 'redington_otp/general/enabled';
    
    /**
     * OTP Module Expire Time
     */
    const OTP_MODULE_OTP_EXPIRE_TIME = 'redington_otp/general/otp_expire_time';
	
	/**
     * OTP Module Expire Time
     */
    const OTP_MODULE_OTP_EMAIL_TEMPLATE = 'redington_otp/general/otp_email_template';
    
    /**
     * Customer session
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
	
	/**
     * @var \Magento\Customer\Model\Session
     */
	protected $transportBuilder;
	
	/**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
	public $customerSession;
	
	/**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
	protected $addressRepository;
    
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(        
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
		\Magento\Authorization\Model\UserContextInterface $userContext
    ) {

        $this->scopeConfig = $scopeConfig; 
		$this->checkoutSession = $checkoutSession;
		$this->transportBuilder = $transportBuilder;
		$this->customerSession = $customerSession;
		$this->addressRepository = $addressRepository;
		$this->storeManager = $storeManagerInterface;    
		$this->customerRepository = $customerRepository;
		$this->userContext = $userContext;
                
    }
    
	/**
     * Get scope config value by path for Message & Note
     * 
     * @param type $path
     * @param type $scopeType
     * @param type $scopeCode
     * return string | int 
     */
    public function getConfigThoughVal($path, $type) {
		if($type == 'g'){
			$path = self::OTP_MODULE_GENERAL_SECTION.$path;
		}else{
			$path = self::OTP_MODULE_MESSAGE_NOTE_SECTION.$path;
		}
        return $this->scopeConfig->getValue($path, 'default', '');
    }
	
    /**
     * Get scope config value by path, scope type and code
     * 
     * @param type $path
     * @param type $scopeType
     * @param type $scopeCode
     * return string | int 
     */
    public function getConfig($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null) {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }
    
    /**
     * @param string $scopeType
     * @param string | null $scopeCode
     * @return string | null 
     */
    public function isModuleEnabled($scopeType, $scopeCode) {
        return $this->getConfig(self::OTP_MODULE_ENABLED, $scopeType, $scopeCode);
    }
	
	/**
     * @param string $scopeType
     * @param string | null $scopeCode
     * @return string | null 
     */
    public function otpExpireTime($scopeType, $scopeCode) {
        return $this->getConfig(self::OTP_MODULE_OTP_EXPIRE_TIME,$scopeType, $scopeCode); 
    }
	
	/**
     * @param string $email
     * @return string 
     */
	public function obfuscateEmail($email)
	{
		$em   = explode("@",$email);
		$emailPart = implode(array_slice($em, 0, count($em)-1), '@');
		
		$maskedEmailPart = $this->functionobfuscateVal($emailPart);
		return $maskedEmailPart . "@" . end($em);   
	}
	/**
     * Is OTP Expire check
     * @return Y/N 
     */
	public function isOtpExpire(){
		$expireMinutes = $this->getConfigThoughVal('otp_expire_time','g');
		
		$recentTime = time();
		$otpStartTime =  $this->checkoutSession->getRandOtpStartTime();
		$otpExpireMarginTime = ($otpStartTime+(60 * $expireMinutes));

		if($recentTime > $otpStartTime && $recentTime < $otpExpireMarginTime){
			return 'N';
			//$this->isOtpExpireFlag = true;
		}else{
			return 'Y';
			//$this->isOtpExpireFlag = false;
		}
	}
	/**
     * Genarate OTP
     * Set RandOtp & RandOtpStartTime in checkout session
     */
	public function genarateOTP(){
		$this->checkoutSession->setRandOtp('');
		$this->checkoutSession->setRandOtpStartTime('');
		$six_digit_random_number = mt_rand(100000, 999999);
		$this->checkoutSession->setRandOtp($six_digit_random_number);
		$this->checkoutSession->setRandOtpStartTime(time());
	}
	
	/**
     * @param string $customerEmail
     * Send OTP in mail
     */
	public function sendOtpInMail($customerEmail){
		$otpVal = $this->checkoutSession->getRandOtp();
	    $senderEmail = $this->scopeConfig->getValue('trans_email/ident_general/email', 'default', '');
		$senderName = $this->scopeConfig->getValue('trans_email/ident_general/name', 'default', '');
		$salesEmail = $this->scopeConfig->getValue('trans_email/ident_support/email', 'default', '');
		$recipientEmail = $customerEmail;
		$customerName = $this->customerSession->getCustomer()->getFirstname()." ".$this->customerSession->getCustomer()->getLastname();
		$identifier = $this->getConfigThoughVal('otp_email_template','g');
		$otpVal = $this->checkoutSession->getRandOtp();
		
		$templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());	
		$transport = $this->transportBuilder
			->setTemplateIdentifier($identifier)
			->setTemplateOptions($templateOptions)
			->setTemplateVars(['otp' => $otpVal, 'customerName' => $customerName, 'salesEmail' => $salesEmail])
			->setFrom(['name' => $senderName, 'email' => $senderEmail])
			->addTo([$recipientEmail])
			->getTransport();
		$transport->sendMessage();
		$this->debugLog('SUCCESS:(Data-sendOtpInMail) Send Otp.Email: '.$recipientEmail.' OTP: '.$otpVal, false);	
	}
	
	/**
         * Get Customer telephone to send otp
         * @return Telephone
         */
	public function getCustomerTelephone(){
            if($this->customerSession->isLoggedIn()) {
                $userTelephone = '';                    
                $userTelephone .= $this->getUserCountryCode();
                $userTelephone .= $this->getUserTelephone();
                return $userTelephone;                    
            }
	}
	
	/**
     * @return Telephone with mask
     */
	public function getCustomerTelephoneWithMask(){
		$customerTelephoneNo = $this->getCustomerTelephone();
		
		if($customerTelephoneNo != ""){
			return $this->obfuscateTelephone($customerTelephoneNo);
		}
	}
	
	/**
     * @param string $telephone
     * @return string 
     */
	public function obfuscateTelephone($telephone)
	{
		return $this->functionobfuscateVal($telephone);
	}
	/**
     * @param string
     * @return string 
     */
	public function functionobfuscateVal($stringVal){
		if($stringVal){
			$stringLen = strlen($stringVal);
			$len1  = floor($stringLen/3);
			if($stringLen == 3){
				return substr($stringVal,0, $len1). str_repeat('*', $len1).substr($stringVal, $len1*2, $stringLen);
			}else{
				return substr($stringVal,0, $len1). str_repeat('*', $len1+1).substr($stringVal, $len1*2+1, $stringLen);
			}
		}
	}
	/**
     * @param string $customerMobile
     * Send OTP in sms
     */
	public function sendOtpInPhone($customerMobile){
		$six_digit_random_number = mt_rand(100000, 999999);
		$requestId= 'testrequestid'.$six_digit_random_number;					
		$responseJson = $this->callAPI($customerMobile, $requestId);
		$responseArray = json_decode($responseJson, true);
		
		return $responseArray['smsMessageResponse']['status'];
	}
	
	/**
     * @param string $customerMobile
     * Send OTP in sms : call API
     */
	public function callAPI($customerMobile, $requestId){
		try{
				$otpVal = $this->checkoutSession->getRandOtp();
				$this->debugLog('SUCCESS:(Data-sendOtpInPhone) Send Otp.Phone: '.$customerMobile.' OTP: '.$otpVal, false);
				$otpSmsContent = $this->getConfigThoughVal('otp_sms_content','g');
				if($otpSmsContent != "") {
					$otpSmsContent = str_replace('{{otp_val}}', $otpVal, $otpSmsContent);
				}
				$otpMessage = ($otpSmsContent != "") ? $otpSmsContent : "Your OTP (One Time Password) for completing your place order is ".$otpVal.". Please do not share this with anyone.";
				
				$url = $this->getConfigThoughVal('otp_sms_api_url','g');
				$this->debugLog('API URL: '.$url, false);
				//== START CURL
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, true);

				$val =	'{  
				   "commonAttributesMessage":{  
					  "applicationID":"redington",
					  "requestID":"'.$requestId.'"
				   },
				   "smsMessage":{  
					  "source":"redington",
					  "messages":"'.$otpMessage.'",
					  "destinations":"'.$customerMobile.'"
				   }
				}';
				
				//Fore testing use "destinations":"971554515817"
				$data = array("payload"=>$val);

				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				$output = curl_exec($ch);
				$info = curl_getinfo($ch);
				curl_close($ch);
				$this->debugLog('Payload=>: '.$val, false);
				return $output;
			}catch(\Exception $e){
				$this->debugLog('Exception: '.$e->getMessage(), false);
			}
	}
	/**
     * For Debug
     */
	public function debugLog($logInfo, $fileName) {
		if(!$fileName){
			$filePath = '/var/log/Redington_Otp.log';
		}else{
			$filePath = '/var/log/'.$fileName;
		}
        $writer = new \Zend\Log\Writer\Stream(BP . $filePath);
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info($logInfo);
    }
    
    /**
      * Get company user telephone.
      * 
      * @return string
      */
    public function getUserTelephone() {
        $customer = $this->customerRepository->getById($this->userContext->getUserId());
        return $customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getCompanyAttributes()
            ? $customer->getExtensionAttributes()->getCompanyAttributes()->getTelephone()
            : '';
    }
    /**
      * Get company user country code.
      * 
      * @return string
      */
    public function getUserCountryCode() {
        $customer = $this->customerRepository->getById($this->userContext->getUserId());
        return $customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getCompanyAttributes()
            ? $customer->getExtensionAttributes()->getCompanyAttributes()->getCountryCode()
            : '';
    }
}
