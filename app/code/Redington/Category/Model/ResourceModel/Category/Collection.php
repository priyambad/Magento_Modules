<?php

namespace Redington\Category\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Category Resource Model Collection
 *
 */
class Collection extends AbstractCollection
{

	
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Redington\Category\Model\Category', 'Redington\Category\Model\ResourceModel\Category');
    }
}