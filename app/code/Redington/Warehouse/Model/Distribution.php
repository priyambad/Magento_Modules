<?php

namespace Redington\Warehouse\Model;

class Distribution extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'redington_distribution_channel';

	protected function _construct()
	{
		$this->_init('Redington\Warehouse\Model\ResourceModel\Distribution');
	}
	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getEntityId()];
	}
}