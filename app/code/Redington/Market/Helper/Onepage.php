<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Redington_Market
 */

namespace Redington\Market\Helper;

use Amasty\Checkout\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
class Onepage extends \Amasty\Checkout\Helper\Onepage
{
 
	 /**
     * @var CollectionFactory
     */
    protected $regionsFactory;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

	/**
     * @var \Redington\Market\Helper\Data
     */
    public $redingtonMarketHelper;

	/**
     * 
	 * @param Context $context
     * @param CollectionFactory $regionsFactory
	 * @param  \Magento\Framework\Json\Helper\Data $jsonHelper
	 * @param \Redington\Market\Helper\Data $redingtonMarketHelper
     */
    public function __construct(
        Context $context,
        CollectionFactory $regionsFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
		\Redington\Market\Helper\Data $redingtonMarketHelper
    ) {
        parent::__construct($context, $regionsFactory, $jsonHelper);
		$this->redingtonMarketHelper = $redingtonMarketHelper;  
    }
	
	/**
     * @param string $customerMobile
     * Send OTP in sms : call API
     */
	public function getCreditBalanceRes(){
		return $this->redingtonMarketHelper->getCreditBalanceFromMarketplace();
	}
}