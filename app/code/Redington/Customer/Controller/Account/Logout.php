<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Customer
 */

namespace Redington\Customer\Controller\Account;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\App\ObjectManager;
/**
 * Customer As Customer Login action
 */
class Logout extends \Magento\Customer\Controller\Account\Logout
{   
    /**
    * ADB2C logout url
    */
    const XML_PATH_ADB2CSIGNOUTURL = 'redington_customer/general/adb2csignouturl';
   
    /**
    * @var \Magento\Customer\Model\Session
    */
    protected $customerSession;
    
    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var PhpCookieManager
     */
    private $cookieMetadataManager;

    /*
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $scopeConfig;

    /**
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context, 
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {        
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $customerSession);
    }
    
    /**
     * Retrieve cookie manager
     *
     * @deprecated 100.1.0
     * @return PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = ObjectManager::getInstance()->get(PhpCookieManager::class);
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 100.1.0
     * @return CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = ObjectManager::getInstance()->get(CookieMetadataFactory::class);
        }
        return $this->cookieMetadataFactory;
    }
    
    /**
     * Login as customer action
     *
     * @return \Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {   
         try { 
            $lastCustomerId = $this->customerSession->getId();            
            if($lastCustomerId) {
                $this->customerSession->logout()->setBeforeAuthUrl($this->_redirect->getRefererUrl())->setLastCustomerId($lastCustomerId);
                
                //$this->messageManager->addSuccess(__('You are successfully logged out.'));
                if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                    $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                    $metadata->setPath('/');
                    $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                }
                $LogoutUrl = $this->scopeConfig->getValue(self::XML_PATH_ADB2CSIGNOUTURL);
                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($LogoutUrl);
                return $resultRedirect;
            }else{
                $this->_redirect('customer/account');
                return;
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_redirect('customer/account');
            return;
        }
    }
}
