<?php

namespace Redington\Company\Model;

class ParentCompany extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'redington_parent_company';

	protected function _construct()
	{
		$this->_init('Redington\Company\Model\ResourceModel\ParentCompany');
	}
	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}
}