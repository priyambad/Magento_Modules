<?php

namespace Redington\Company\Model\ResourceModel\BrandPreference;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    /**
     * 
     */
    protected function _construct() {
        $this->_init('Redington\Company\Model\BrandPreference', 'Redington\Company\Model\ResourceModel\BrandPreference');
    }

}
