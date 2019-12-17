<?php

namespace Redington\Category\Model;

use Magento\Cron\Exception;
use Magento\Framework\Model\AbstractModel;

/**
 * Category Model
 */
class Category extends AbstractModel
{
    const CACHE_TAG = 'redington_category_data';
    
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Redington\Category\Model\ResourceModel\Category::class);
    }

    public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getEntityId()];
	}
}