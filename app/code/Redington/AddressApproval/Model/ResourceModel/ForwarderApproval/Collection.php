<?php

namespace Redington\AddressApproval\Model\ResourceModel\ForwarderApproval;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {


    protected function _construct() {
        $this->_init('Redington\AddressApproval\Model\ForwarderApproval', 'Redington\AddressApproval\Model\ResourceModel\ForwarderApproval');
    }

}
