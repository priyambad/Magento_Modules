<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Customer
 */

namespace Redington\Customer\Plugin\Customer\Model\Registration;

use Magento\Customer\Model\Registration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class DisableRegistrationPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    const XML_PATH_DISABLE_CUSTOMER_REGISTRATION = 'redington_customer/general/disable_customer_registration';

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * If Registration is not allowed by the plugin - returns false, otherwise returns original value.
     *
     * @param Registration $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsAllowed(Registration $subject, $result)
    {
        if ($this->scopeConfig->isSetFlag(
            self::XML_PATH_DISABLE_CUSTOMER_REGISTRATION,
            ScopeInterface::SCOPE_STORE
        )) {
            return false;
        }

        return $result;
    }
}
