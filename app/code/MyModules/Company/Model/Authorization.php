<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */
namespace Redington\Company\Model;

use \Magento\Company\Api\AuthorizationInterface;

/**
 * Class is responsible for checking user permissions.
 */
class Authorization extends \Magento\Framework\Authorization implements AuthorizationInterface
{
    /**
     * @var array
     */
    private $permissions = [];

    /**
     * @inheritdoc
     */
    public function isAllowed($resource, $privilege = null)
    {
        $cacheKey = $resource . '-' . $privilege;
        if (!isset($this->permissions[$cacheKey])) {
            $this->permissions[$cacheKey] = parent::isAllowed($resource, $privilege);
        }

        return $this->permissions[$cacheKey];
    }
}
