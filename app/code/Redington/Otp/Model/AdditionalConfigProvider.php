<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Otp
 */
 
namespace Redington\Otp\Model;

use Magento\Customer\Model\Context as CustomerContext;

class AdditionalConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
	/**
     * @var \Redington\Otp\Helper\Data
     */
	protected $redingtonOtpHelper;
	
    /**
     * @param CollectionFactory $addressesFactory
     */
    public function __construct(
        \Redington\Otp\Helper\Data $redingtonOtpHelper
    ) {
        $this->redingtonOtpHelper = $redingtonOtpHelper;
    }
	
	public function getConfig()
	{
		$otpConfig = [];
		$otpConfig['otpConfig']['enabled'] = $this->redingtonOtpHelper->isModuleEnabled('default', '');
		if($otpConfig['otpConfig']['enabled'] == ""){
			$otpConfig['otpConfig']['enabled'] = '0';
		}
		return $otpConfig;
	}
}