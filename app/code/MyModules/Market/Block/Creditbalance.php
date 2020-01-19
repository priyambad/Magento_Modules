<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Redington_Market
 */

/**
 * Available credit balance block
 */
namespace Redington\Market\Block;


/**
 * Switcher block
 *
 * @api
 * @since 100.0.2
 */
class Creditbalance extends \Magento\Customer\Block\Account\Customer
{
    /**
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $context;

	/**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;
	
	/**
     * @var \Redington\Market\Helper\Data
     */
    protected $redingtonMarketHelper;
	
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
	 * @param \Redington\Market\Helper\Data $redingtonMarketHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
		\Redington\Market\Helper\Data $redingtonMarketHelper,
        array $data = []
    ) {
        parent::__construct($context, $httpContext, $data);
        $this->httpContext = $httpContext;
		$this->redingtonMarketHelper = $redingtonMarketHelper;
    }
	/**
     * Get Available Credit balance
     *
     * @return price
     */
	public function getCreditBalanceRes(){
		return $this->redingtonMarketHelper->getCreditBalanceFromMarketplace();
	}
	/**
     * Get Available Credit balance
     *
     * @return price
     */
	public function isCustomerLogin(){
		return $this->redingtonMarketHelper->isCustomerLogin();
    }
    /**
     * Get available Credit amount
     *
     * @return void
     */
    public function getCreditAmount(){
        return $this->redingtonMarketHelper->getCreditLimit();
    }
	
}
