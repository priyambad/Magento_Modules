<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */
namespace Redington\Company\Model\Authorization\Loader;

use Magento\Company\Model\Permission;
use \Magento\Framework\Acl;

/**
 * Populate ACL permissions for all company roles.
 */
class Rule implements \Magento\Framework\Acl\LoaderInterface
{
    /**
     * @var \Magento\Company\Model\ResourceModel\Permission\Collection
     */
    private $collection;

    /**
     * @var \Magento\Framework\Acl\RootResource
     */
    private $rootResource;

    /**
     * @var \Magento\Framework\Acl\AclResource\ProviderInterface
     */
    private $resourceProvider;

    /**
     * @var \Magento\Company\Api\RoleManagementInterface
     */
    private $roleManagement;

    /**
     * @var \Magento\Company\Model\CompanyUser
     */
    private $companyUser;

    /**
     * @param \Magento\Framework\Acl\RootResource $rootResource
     * @param \Magento\Company\Model\ResourceModel\Permission\Collection $collection
     * @param \Magento\Framework\Acl\AclResource\ProviderInterface $resourceProvider
     * @param \Magento\Company\Api\RoleManagementInterface $roleManagement
     * @param \Magento\Company\Model\CompanyUser $companyUser
     */
    public function __construct(
        \Magento\Framework\Acl\RootResource $rootResource,
        \Magento\Company\Model\ResourceModel\Permission\Collection $collection,
        \Magento\Framework\Acl\AclResource\ProviderInterface $resourceProvider,
        \Magento\Company\Api\RoleManagementInterface $roleManagement,
        \Magento\Company\Model\CompanyUser $companyUser
    ) {
        $this->rootResource = $rootResource;
        $this->collection = $collection;
        $this->resourceProvider = $resourceProvider;
        $this->roleManagement = $roleManagement;
        $this->companyUser = $companyUser;
    }

    /**
     * Populate ACL with rules from external storage.
     *
     * @param Acl $acl
     * @return void
     */
    public function populateAcl(Acl $acl)
    {
        $reverseAclArray = $this->getReverseAclArray($this->resourceProvider->getAclResources());
        $processedResources = [];
        $this->collection->addFieldToFilter(
            \Magento\Company\Api\Data\PermissionInterface::ROLE_ID,
            ['in' => $this->getCompanyRolesIds()]
        );
        $permissions = $this->collection->getItems();

        /** @var \Magento\Company\Api\Data\PermissionInterface $rule */
        foreach ($permissions as $rule) {
            $roleId = $rule->getRoleId();
            $resource = $rule->getResourceId();
            if (!isset($processedResources[$roleId])) {
                $processedResources[$roleId] = [];
            }
            $this->hydrateAclByResource($acl, $rule, $resource, $roleId, $processedResources, $reverseAclArray);
        }
    }

    /**
     * Gets revers acl array.
     *
     * @param array $aclArray
     * @param array $parents
     * @param array $return
     * @return array
     */
    private function getReverseAclArray(array $aclArray, array $parents = [], array &$return = [])
    {
        foreach ($aclArray as $item) {
            if (isset($item['children']) && count($item['children'])) {
                $this->getReverseAclArray($item['children'], array_merge($parents, [$item['id']]), $return);
            } else {
                $return[$item['id']] = $parents;
            }
        }
        return $return;
    }

    /**
     * Hydrate Acl with rules only if Acl has each resource
     * @param Acl $acl
     * @param Permission $rule
     * @param string $resource
     * @param int $roleId
     * @param array $processedResources
     * @param array $reverseAclArray
     * @return void
     */
    private function hydrateAclByResource(
        Acl $acl,
        Permission $rule,
        $resource,
        $roleId,
        array &$processedResources,
        array $reverseAclArray
    ) {
        if ($acl->has($resource) && !in_array($resource, $processedResources[$roleId])) {
            if ($rule->getPermission() == 'allow') {
                if ($resource === $this->rootResource->getId()) {
                    $acl->allow($roleId, null);
                }
                $acl->allow($roleId, $resource);
                $processedResources[$roleId][] = $resource;
                if (isset($reverseAclArray[$resource])) {
                    foreach ($reverseAclArray[$resource] as $reverseAclArrayItem) {
                        $acl->allow($roleId, $reverseAclArrayItem);
                        $processedResources[$roleId][] = $reverseAclArrayItem;
                    }
                }
            } elseif ($rule->getPermission() == 'deny') {
                $acl->deny($roleId, $resource);
                $processedResources[$roleId][] = $resource;
            }
        }
    }

    /**
     * Get IDs of all company roles.
     *
     * @return array
     */
    private function getCompanyRolesIds()
    {
        $roles = $this->roleManagement->getRolesByCompanyId($this->companyUser->getCurrentCompanyId());
        return array_map(
            function ($role) {
                return $role->getId();
            },
            $roles
        );
    }
}
