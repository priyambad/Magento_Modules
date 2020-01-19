<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Customer
 */

namespace Redington\Customer\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    /**
     * default
     */
    const SCOPE_DEFAULT = 'default';

    /**
     * Catalog Module Enable configuration
     */
    const CUSTOMER_MODULE_ENABLED = 'redington_customer/general/enabled';

    /**
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        Session $customerSession
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * Get scope config value by path, scope type and code
     *
     * @param type $path
     * return string | int
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check module is enabled or not
     *
     * @return string | null
     */
    public function isModuleEnabled()
    {
        return $this->getConfig(self::CUSTOMER_MODULE_ENABLED);
    }

    public function isCustomerLoggedIn()
    {
        return $this->customerSession->getCustomer()->getId();
    }
    
    public function getCustomerFullName() {
        $fullName = '';
        try {
            $firstName = trim($this->customerSession->getCustomer()->getData('firstname'));
            $fname=explode(" ",$firstName);
            //$lastName = $this->customerSession->getCustomer()->getData('lastname');
            if($firstName) {
                $fullName .= $fname[0];
            }

            // if($firstName && $lastName){
            //     $fullName .= ' ';
            // }
            // if($lastName) {
            //     $fullName .= $lastName;
            // }
            return $fullName;
        } catch (Exception $e) {
            return $fullName;
        }        
    }
}
