<?php

namespace Redington\Contact\Model;

class Contact extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'redington_contact';

	protected function _construct()
	{
		$this->_init('Redington\Contact\Model\ResourceModel\Contact');
	}
	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}
}
