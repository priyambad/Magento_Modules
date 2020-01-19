<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CompanyLogo
 */
namespace Redington\CompanyLogo\Model\ResourceModel;

use Magento\Cron\Exception;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Category Resource Model
 */
class CompanyLogo extends AbstractDb
{
	
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init('redington_pdf_data','entity_id');
    }
   
}