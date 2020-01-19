<?php

namespace Redington\Transactions\Model;

class Credit extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface {

    const CACHE_TAG = 'redington_credit';

    protected function _construct() {
        $this->_init('Redington\Transactions\Model\ResourceModel\Credit');
    }

    public function getIdentities() {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

}
