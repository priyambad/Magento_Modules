<?php

namespace Redington\PaymentGroup\Model;

class Group extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'redington_paymentgroup'; 
	protected function _construct()
	{
		$this->_init('Redington\PaymentGroup\Model\ResourceModel\Group');
	}
	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getGroupId()];
	}
}