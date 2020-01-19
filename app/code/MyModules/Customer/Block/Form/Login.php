<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Customer
 */
namespace Redington\Customer\Block\Form;

/**
 * Adb2c Login block
 * 
 */
class Login extends \Magento\Customer\Block\Form\Login
{
    protected $scopeConfig;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param array $data
     */
    const SIGNUP_URL = 'redington_customer/general/adb2csignupurl';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $customerUrl, $data);
        $this->_isScopePrivate = false;
        $this->_customerUrl = $customerUrl;
        $this->_customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
    }
    
    /**
     * Retrieve form posting url
     *
     * @return string
     */
    public function getAdb2cSignInUrl()
    {
        return $this->_customerUrl->getLoginUrl();
    }    
    public function getAdb2cSignUpUrl()
    {
         $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        return $this->scopeConfig->getValue(self::SIGNUP_URL, $storeScope);
        
    }
}
