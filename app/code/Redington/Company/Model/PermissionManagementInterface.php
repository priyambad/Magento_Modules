<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */
namespace Redington\Company\Model;

/**
 * PermissionManagement interface.
 *
 * @api
 * @since 100.0.0
 */
interface PermissionManagementInterface
{
    /**
     * Retrieve allowed resources.
     *
     * @param \Magento\Company\Api\Data\PermissionInterface[] $permissions
     * @return array
     */
    public function retrieveAllowedResources(array $permissions);

    /**
     * Retrieve default role permissions.
     *
     * @return \Magento\Company\Api\Data\PermissionInterface[] $permissions
     */
    public function retrieveDefaultPermissions();

    /**
     * Populate permissions.
     *
     * @param array $allowedResources
     * @return \Magento\Company\Api\Data\PermissionInterface[] $permissions
     */
    public function populatePermissions(array $allowedResources);
}
