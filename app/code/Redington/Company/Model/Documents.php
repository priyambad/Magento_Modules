<?php

namespace Redington\Company\Model;

class Documents extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'redington_companies';

	protected function _construct()
	{
		$this->_init('Redington\Company\Model\ResourceModel\Documents');
	}
	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}
}