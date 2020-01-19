<?php

namespace Redington\Company\Model;

class BrandPreference extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface {

	const CACHE_TAG = 'redington_brands';

    protected function _construct() {
        $this->_init('Redington\Company\Model\ResourceModel\BrandPreference');
    }
    public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}
}
