<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Redington_Market
 */

namespace Redington\Market\Helper;
use Magento\Store\Model\StoreManagerInterface;
class Configdata extends \Sm\Market\Helper\Data
{
	/**
     * @param StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\App\Helper\Context $context
     */
	public function __construct(
		StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\App\Helper\Context $context
    ) {
		$this->_storeManager = $storeManagerInterface;
        parent::__construct($storeManagerInterface, $context);
    }
	
	
	public function getConfigVal($name)
	{
        return $this->scopeConfig->getValue(
            $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );		
	}
	
}