<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CompanyLogo
 */
namespace Redington\CompanyLogo\Model;

use Magento\Cron\Exception;
use Magento\Framework\Model\AbstractModel;

/**
 * CompanyLogo Model
 */
class CompanyLogo extends AbstractModel
{
    const CACHE_TAG = 'redington_pdf_data';
    
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Redington\CompanyLogo\Model\ResourceModel\CompanyLogo::class);
    }
    /**
     * Get Identities
     *
     * @return void
     */
    public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}
}