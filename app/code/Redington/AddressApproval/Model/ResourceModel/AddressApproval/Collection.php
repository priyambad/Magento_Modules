<?php

namespace Redington\AddressApproval\Model\ResourceModel\AddressApproval;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {


    protected function _construct() {
        $this->_init('Redington\AddressApproval\Model\AddressApproval', 'Redington\AddressApproval\Model\ResourceModel\AddressApproval');
    }

}
