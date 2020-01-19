<?php

namespace Redington\Transactions\Model;

class Transaction extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface {

    const CACHE_TAG = 'redington_transaction';

    protected function _construct() {
        $this->_init('Redington\Transactions\Model\ResourceModel\Transaction');
    }

    public function getIdentities() {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

}
