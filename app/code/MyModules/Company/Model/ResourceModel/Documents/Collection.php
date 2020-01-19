<?php

namespace Redington\Company\Model\ResourceModel\Documents;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected function _construct() {
        $this->_init('Redington\Company\Model\Documents', 'Redington\Company\Model\ResourceModel\Documents');
    }

}
