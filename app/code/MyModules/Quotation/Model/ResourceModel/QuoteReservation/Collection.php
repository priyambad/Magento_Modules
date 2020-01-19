<?php

namespace Redington\Quotation\Model\ResourceModel\QuoteReservation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected function _construct() {
        $this->_init('Redington\Quotation\Model\QuoteReservation', 'Redington\Quotation\Model\ResourceModel\QuoteReservation');
    }

}
