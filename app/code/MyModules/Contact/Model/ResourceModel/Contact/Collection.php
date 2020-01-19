<?php

namespace Redington\Contact\Model\ResourceModel\Contact;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {


    protected function _construct() {
        $this->_init('Redington\Contact\Model\Contact', 'Redington\Contact\Model\ResourceModel\Contact');
    }

}