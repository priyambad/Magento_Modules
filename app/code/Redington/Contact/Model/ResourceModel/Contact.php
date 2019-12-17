<?php

namespace Redington\Contact\Model\ResourceModel;

class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb {

    /**
     * 
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(
    \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct() {
        $this->_init('redington_contact', 'entity_id');
    }

}
