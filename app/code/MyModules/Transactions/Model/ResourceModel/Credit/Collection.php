<?php

namespace Redington\Transactions\Model\ResourceModel\Credit;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected function _construct() {
        $this->_init('Redington\Transactions\Model\Credit', 'Redington\Transactions\Model\ResourceModel\Credit');
    }

}
