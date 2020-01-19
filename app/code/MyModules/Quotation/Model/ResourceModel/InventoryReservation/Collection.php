<?php

namespace Redington\Quotation\Model\ResourceModel\InventoryReservation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected function _construct() {
        $this->_init('Redington\Quotation\Model\InventoryReservation', 'Redington\Quotation\Model\ResourceModel\InventoryReservation');
    }

}
