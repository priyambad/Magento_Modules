<?php

namespace Redington\Transactions\Model\ResourceModel\Transaction;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected function _construct() {
        $this->_init('Redington\Transactions\Model\Transaction', 'Redington\Transactions\Model\ResourceModel\Transaction');
    }

}
