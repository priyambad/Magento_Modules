<?php

namespace Redington\SapIntegration\Model;

class OrderReference extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'redington_order_reference';

	protected function _construct()
	{
		$this->_init('Redington\SapIntegration\Model\ResourceModel\OrderReference');
	}
	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getEntityId()];
	}
}