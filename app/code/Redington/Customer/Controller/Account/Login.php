<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Customer
 */

namespace Redington\Customer\Controller\Account;

use Magento\Framework\Exception\NotFoundException;

/**
 * Customer As Customer Login action
 */
class Login extends \Magento\Framework\App\Action\Action
{
    const REQUESTERRORCODE = 'access_denied';
    
    const REQUESTINVALID = 'invalid_request';
    
	/**
    * ADB2C logout url
    */
    const XML_PATH_ADB2CFORGOTPWDURL = 'redington_customer/general/adb2cforgotpwdurl';
   
	/**
    * ADB2C login policy
    */
    const XML_PATH_ADB2CSIGNINPOLICY = 'redington_customer/general/adb2csigninpolicy';
    
	/**
    * ADB2C forgot pwd policy
    */
    const XML_PATH_ADB2CFORGOTPWDPOLICY = 'redington_customer/general/adb2cforgotpwdpolicy';
   
   
    /**
    * @var \Magento\Customer\Model\Customer
    */
    protected $customer;
    
    /**
    * @var \Magento\Customer\Model\Session
    */
    protected $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface 
     */
    protected $storeManager;
    
    /**
     * @var \Redington\Customer\Helper\Data 
     */
    private $helperData;
    
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;
        
    /**
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Redington\Customer\Helper\Data $helperData
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context, 
        \Magento\Customer\Model\CustomerFactory $customer,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Redington\Customer\Helper\Data $helperData,
        \Magento\Store\Api\StoreCookieManagerInterface $storeCookieManager,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->customer = $customer;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->helperData = $helperData;
        $this->urlInterface = $urlInterface;
        $this->storeCookieManager = $storeCookieManager;
		$this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    /**
     * Login as customer action
     *
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {   
        try {            
            $state = $this->getRequest()->getParam('state') ? $this->getRequest()->getParam('state') : null;
            $error = $this->getRequest()->getParam('error') ? $this->getRequest()->getParam('error') : null;            
            $resultRedirect = $this->resultRedirectFactory->create();    
            if($state && !($error == SELF::REQUESTERRORCODE || $error == SELF::REQUESTINVALID)) {
				$this->messageManager->getMessages(true); 
                $code = $this->getRequest()->getParam('code') ? $this->getRequest()->getParam('code') : null;
                $tokenId = $this->getRequest()->getParam('id_token') ? $this->getRequest()->getParam('id_token') : null;                          
                
                $clientInfo = $this->getRequest()->getParam('client_info') ? $this->getRequest()->getParam('client_info') : null;                          
                
                $websiteId = $this->getRequest()->getParam('website') ? $this->getRequest()->getParam('website') : 0;
                if(!isset($tokenId) || empty($tokenId))
                {
                    $this->messageManager->addErrorMessage(__('The account sign-in was incorrect or your account is disabled temporarily. Please wait and try again later.'));
                                            $this->_redirect('customer/account/logout');
                                            return;
                }
                $token_parts = explode(".", $tokenId);
                
                // First part is header, which we ignore  Second part is JWT, which we want to parse

                // First, in case it is url-encoded, fix the characters to be valid base64
                $encoded_token = str_replace('-', '+', $token_parts[1]);
                $encoded_token = str_replace('_', '/', $encoded_token);
                // Next, add padding if it is needed.
                switch (strlen($encoded_token) % 4){
                  case 0:
                    // No pad characters needed.
                    break;
                  case 2:
                    $encoded_token = $encoded_token."==";
                    break;
                  case 3:
                    $encoded_token = $encoded_token."=";
                    break;
                  default:
                    // Invalid base64 string!                  
                }
                
                $json_string = base64_decode($encoded_token);
                $jwt = json_decode($json_string, true);   
				
				$loginPolicy = $this->scopeConfig->getValue(self::XML_PATH_ADB2CSIGNINPOLICY);
                $forgotpwdPolicy = $this->scopeConfig->getValue(self::XML_PATH_ADB2CFORGOTPWDPOLICY);
                
				if($jwt['tfp'] == $loginPolicy) {
					$timestamp = $jwt['exp']; 
					$seconds = time() - $timestamp; 

                if ( $seconds < 1) {                  
                    $emailId = $jwt['emails'][0];
                    if ($emailId && $this->helperData->isModuleEnabled() && $state) {
                        if(!$this->customerSession->isLoggedIn()) {                         
                            $this->customer = $this->customer->create();                        
                            $customerData = $this->customer->getCollection()
                                            ->addFieldToFilter("email", array("eq" => $emailId))->getFirstItem();                         
                            $websiteId = $customerData->getWebsiteId();
                            $siteWebsiteId = $this->storeManager->getWebsite()->getId();
                            $storeId = $customerData->getStoreId();
                           
                            if($customerData->getId()) {
                               
                                if($siteWebsiteId == $websiteId) {
                                    $this->customer->setWebsiteId($websiteId);
                                    $customer = $this->customer->loadByEmail($emailId);  
                                                     
                                    if($customer->getId() && $customer->getIsActive()) {
                                        
                                       $store = $this->storeManager->getStore($storeId);
                                                                           
                                        $this->storeManager->setCurrentStore($store->getCode());  
                                        $this->storeCookieManager->setStoreCookie($store);
                                        $this->customerSession->loginById($customer->getId());
                                        $this->customerSession->regenerateId();
                                        $this->customerSession->setCustomerAsLoggedIn($customer);
                                        $this->customerSession->setSingleToken($tokenId);
                                       
                                   
                                        $this->messageManager->addSuccess(__('You are successfully logged in.'));
                                        $this->_redirect('customer/account');  
                                        return;
                                    } else {
										$this->messageManager->addErrorMessage(__('Your application is still pending for approval, once it is approved, you will be able to login to this portal.'));
                                        $this->_redirect('customer/account/logout');
                                        return;
                                    }
                                } else {
                                    $targetUrl = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getBaseUrl();
                                    $currentFullUrl = $this->urlInterface->getCurrentUrl();

                                    $currentUrl = $this->urlInterface->getUrl();
                                    $finalUrl = str_replace($currentUrl, $targetUrl, $currentFullUrl);

                                    $resultRedirect->setUrl($finalUrl);
                                    return $resultRedirect;
                                } 
                            } else {
                                $this->messageManager->addErrorMessage(__('Your application is still pending for approval, once it is approved, you will be able to login to this portal.'));
                                $this->_redirect('customer/account/logout');
                                return;
                            }  
                        }else{ 
                            $this->messageManager->addSuccess(__('You are already logged in.'));
                            $this->_redirect('customer/account');
                            return;
                        }                            
                    }
                } else {
                    $this->messageManager->addErrorMessage(__('The time is expired, please login again.'));
                    $this->_redirect('customer/account/logout');
                    return;
                }
				} else if($jwt['tfp'] == $forgotpwdPolicy) {
                    $this->messageManager->addSuccess(__('You have changed your password successfully, please login with new password.'));
                    $this->_redirect('customer/account/logout');
                    return;
                }
            }
            else if($error == SELF::REQUESTERRORCODE){ 
                $errorDescription = $this->getRequest()->getParam('error_description') ? $this->getRequest()->getParam('error_description') : null;            
                if (strpos($errorDescription, 'AADB2C90118')!== false) {
					$forgotPwdUrl = $this->scopeConfig->getValue(self::XML_PATH_ADB2CFORGOTPWDURL);                                   
                    $resultRedirect->setUrl($forgotPwdUrl);  
                    return $resultRedirect;
                } else {
                    //$this->messageManager->addErrorMessage($errorDescription);
                    $this->_redirect('customer/account/login');
                    return;
                }
            } else {
                $this->_redirect('customer/account/logout');
                return;
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_redirect('customer/account/login');
            return;
        }
        throw new NotFoundException(__('Page not found.'));
    }
}