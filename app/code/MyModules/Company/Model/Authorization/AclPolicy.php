<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */
namespace Redington\Company\Model\Authorization;

/**
 * Class AclPolicy.
 */
class AclPolicy extends \Magento\Framework\Authorization\Policy\Acl
{
    /**
     * Check whether given role has access to give id.
     *
     * @param int $roleId
     * @param string $resourceId
     * @param string $privilege
     * @return bool
     */
    public function isAllowed($roleId, $resourceId, $privilege = null)
    {
        if ($roleId === 0) {
            return true;
        }
        return parent::isAllowed($roleId, $resourceId, $privilege);
    }
}
