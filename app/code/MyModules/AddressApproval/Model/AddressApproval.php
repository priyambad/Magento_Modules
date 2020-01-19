<?php

namespace Redington\AddressApproval\Model;

class AddressApproval extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'redington_address_approval';

	protected function _construct()
	{
		$this->_init('Redington\AddressApproval\Model\ResourceModel\AddressApproval');
	}
	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}
}