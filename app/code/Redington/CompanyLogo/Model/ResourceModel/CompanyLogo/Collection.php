<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CompanyLogo
 */
namespace Redington\CompanyLogo\Model\ResourceModel\CompanyLogo;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * CompanyLogo Resource Model Collection
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
        $this->_init('Redington\CompanyLogo\Model\CompanyLogo', 'Redington\CompanyLogo\Model\ResourceModel\CompanyLogo');
    }
}