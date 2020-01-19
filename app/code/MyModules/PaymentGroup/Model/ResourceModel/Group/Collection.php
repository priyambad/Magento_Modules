<?php

namespace Redington\PaymentGroup\Model\ResourceModel\Group;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected function _construct()
    {
        $this->_init('Redington\PaymentGroup\Model\Group', 'Redington\PaymentGroup\Model\ResourceModel\Group');
    }

}
