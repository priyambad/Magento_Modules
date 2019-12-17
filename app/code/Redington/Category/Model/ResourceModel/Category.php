<?php

namespace Redington\Category\Model\ResourceModel;

use Magento\Cron\Exception;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Category Resource Model
 */
class Category extends AbstractDb
{
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init('redington_category_data','entity_id');
    }
    
}