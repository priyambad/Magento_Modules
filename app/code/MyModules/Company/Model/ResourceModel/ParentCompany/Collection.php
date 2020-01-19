<?php

namespace Redington\Company\Model\ResourceModel\ParentCompany;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected function _construct() {
        $this->_init('Redington\Company\Model\ParentCompany', 'Redington\Company\Model\ResourceModel\ParentCompany');
    }

}
