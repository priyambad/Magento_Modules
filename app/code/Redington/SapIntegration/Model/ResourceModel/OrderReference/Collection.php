<?php

namespace Redington\SapIntegration\Model\ResourceModel\OrderReference;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected function _construct() {
        $this->_init('Redington\SapIntegration\Model\OrderReference', 'Redington\SapIntegration\Model\ResourceModel\OrderReference');
    }

}
