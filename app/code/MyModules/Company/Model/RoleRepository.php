<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */
namespace Redington\Company\Model;

/**
 * A repository class for role entity that provides basic CRUD operations.
 */
class RoleRepository implements \Magento\Company\Api\RoleRepositoryInterface
{
    /**
     * @var \Magento\Company\Api\Data\RoleInterface[]
     */
    private $instances = [];

    /**
     * @var \Magento\Company\Api\Data\RoleInterfaceFactory
     */
    private $roleFactory;

    /**
     * @var \Magento\Company\Model\ResourceModel\Role
     */
    private $roleResource;

    /**
     * @var \Magento\Company\Model\ResourceModel\Role\CollectionFactory
     */
    private $roleCollectionFactory;

    /**
     * @var \Magento\Company\Api\Data\RoleSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var \Magento\Company\Model\Role\Permission
     */
    private $rolePermission;

    /**
     * @var \Magento\Company\Model\PermissionManagementInterface
     */
    private $permissionManagement;

    /**
     * @var \Magento\Company\Model\Role\Validator
     */
    private $validator;

    /**
     * @param \Magento\Company\Api\Data\RoleInterfaceFactory $roleFactory
     * @param \Magento\Company\Model\ResourceModel\Role $roleResource
     * @param \Magento\Company\Model\ResourceModel\Role\CollectionFactory $roleCollectionFactory
     * @param \Magento\Company\Api\Data\RoleSearchResultsInterfaceFactory $searchResultsFactory
     * @param \Magento\Company\Model\Role\Permission $rolePermission
     * @param \Magento\Company\Model\PermissionManagementInterface $permissionManagement
     * @param \Magento\Company\Model\Role\Validator $validator
     *
     */
    public function __construct(
        \Magento\Company\Api\Data\RoleInterfaceFactory $roleFactory,
        \Magento\Company\Model\ResourceModel\Role $roleResource,
        \Magento\Company\Model\ResourceModel\Role\CollectionFactory $roleCollectionFactory,
        \Magento\Company\Api\Data\RoleSearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Company\Model\Role\Permission $rolePermission,
        \Magento\Company\Model\PermissionManagementInterface $permissionManagement,
        \Magento\Company\Model\Role\Validator $validator
    ) {
        $this->roleFactory = $roleFactory;
        $this->roleResource = $roleResource;
        $this->roleCollectionFactory = $roleCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->rolePermission = $rolePermission;
        $this->permissionManagement = $permissionManagement;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function save(\Magento\Company\Api\Data\RoleInterface $role)
    {
        $role = $this->validator->retrieveRole($role);
        $allowedResources = $this->permissionManagement->retrieveAllowedResources($role->getPermissions());
        $permissions = $this->permissionManagement->populatePermissions($allowedResources);
        $this->validator->validatePermissions($permissions, $allowedResources);
        $role->setPermissions($permissions);
        if ($this->validateRoleName($role) === false) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(__(
                'User role with this name already exists. Enter a different name to save this role.'
            ));
        }
        $this->roleResource->save($role);
        $this->rolePermission->saveRolePermissions($role);
        unset($this->instances[$role->getId()]);

        return $role;
    }

    /**
     * Validate that role name is unique.
     *
     * @param \Magento\Company\Api\Data\RoleInterface $role
     * @return bool
     */
    private function validateRoleName(\Magento\Company\Api\Data\RoleInterface $role)
    {
        $collection = $this->roleCollectionFactory->create();
        $collection->addFieldToFilter(
            \Magento\Company\Api\Data\RoleInterface::ROLE_NAME,
            ['eq' => $role->getRoleName()]
        );
        $collection->addFieldToFilter(
            \Magento\Company\Api\Data\RoleInterface::COMPANY_ID,
            ['eq' => $role->getCompanyId()]
        );

        if ($role->getId()) {
            $collection->addFieldToFilter(
                \Magento\Company\Api\Data\RoleInterface::ROLE_ID,
                ['neq' => $role->getId()]
            );
        }

        return !$collection->getSize();
    }

    /**
     * @inheritdoc
     */
    public function get($roleId)
    {
        if (!isset($this->instances[$roleId])) {
            /** @var \Magento\Company\Api\Data\RoleInterface $role */
            $role = $this->roleFactory->create();
            $this->roleResource->load($role, $roleId);
            $this->validator->checkRoleExist($role, $roleId);
            $role->setPermissions($this->rolePermission->getRolePermissions($role));
            $this->instances[$roleId] = $role;
        }
        return $this->instances[$roleId];
    }

    /**
     * @inheritdoc
     */
    public function delete($roleId)
    {
        $role = $this->get($roleId);
        $this->validator->validateRoleBeforeDelete($role);
        try {
            $this->roleResource->delete($role);
            $this->rolePermission->deleteRolePermissions($role);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotDeleteException(
                __(
                    'Cannot delete role with id %1',
                    $role->getId()
                ),
                $e
            );
        }
        unset($this->instances[$roleId]);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->roleCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? : 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection()
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $items = $collection->getItems();

        foreach ($items as $itemKey => $itemValue) {
            $items[$itemKey]->setPermissions($this->rolePermission->getRolePermissions($itemValue));
        }

        $searchResults->setItems($items);
        return $searchResults;
    }
}
