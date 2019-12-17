<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Company
 */

namespace Redington\Company\Api;

/**
 * Interface for retrieving various entity data objects by a given parameters and creating company and admin user to company.
 *
 * @api
 * @since 100.0.0
 */
interface CompanyInterface
{    
    /**
     * Create Company And Assign Admin User to company.
     * 
     * @param mixed $company
     * @return \Redington\Company\Api\CompanyInterface
     */
    public function createCompany($company);
}
