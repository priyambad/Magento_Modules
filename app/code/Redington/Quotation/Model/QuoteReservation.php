<?php

namespace Redington\Quotation\Model;

class QuoteReservation extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'redington_inventory';

	protected function _construct()
	{
		$this->_init('Redington\Quotation\Model\ResourceModel\QuoteReservation');
	}
	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getReservationId()];
	}
}