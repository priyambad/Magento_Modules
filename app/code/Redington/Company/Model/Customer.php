<?php

/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Company
 */

namespace Redington\Company\Model;

class Customer extends \Magento\Company\Model\Customer
{   
    public function getCountryCode()
    {
        return $this->getData('country_code');
    }
    
    public function setCountryCode($countryCode)
    {
        return $this->setData('country_code', $countryCode);
    }
}
