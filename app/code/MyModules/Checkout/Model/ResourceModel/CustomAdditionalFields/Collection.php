<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Model\ResourceModel\CustomAdditionalFields;

/**
 * @method \Redington\Checkout\Model\CustomAdditionalFields[] getItems()
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \Redington\Checkout\Model\CustomAdditionalFields::class,
            \Redington\Checkout\Model\ResourceModel\CustomAdditionalFields::class
        );
    }
}
