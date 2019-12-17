<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Customer
 */
namespace Redington\Customer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Customer url model
 */
class Url extends \Magento\Customer\Model\Url
{
    const XML_PATH_ADB2CSIGNINURL = 'redington_customer/general/adb2csigninurl';
    const XML_PATH_ADB2CSIGNOUTURL = 'redington_customer/general/adb2csignouturl';
    
    /**
     * Retrieve customer login url
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->scopeConfig->getValue(SELF::XML_PATH_ADB2CSIGNINURL, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    
    /**
     * Retrieve customer logout url
     *
     * @return string
     */
//    public function getLogoutUrl()
//    {
//        return $this->scopeConfig->getValue(SELF::XML_PATH_ADB2CSIGNOUTURL);
//    }
    
    /**
     * Retrieve url of forgot password page
     *
     * @return string
     */
    public function getForgotPasswordUrl()
    {
        return $this->scopeConfig->getValue(SELF::XML_PATH_ADB2CSIGNINURL, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
}