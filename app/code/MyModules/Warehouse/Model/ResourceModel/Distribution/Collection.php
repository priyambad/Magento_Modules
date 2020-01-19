<?php

namespace Redington\Warehouse\Model\ResourceModel\Distribution;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected function _construct() {
        $this->_init('Redington\Warehouse\Model\Distribution', 'Redington\Warehouse\Model\ResourceModel\Distribution');
    }

}
