<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */

namespace Redington\Company\Model;

use Magento\Company\Api\AclInterface;

/**
 * Management operations for user roles.
 */
class UserRoleManagement implements AclInterface
{
    /**
     * @var \Magento\Company\Model\ResourceModel\UserRole\CollectionFactory
     */
    private $userRoleCollectionFactory;

    /**
     * @var \Magento\Company\Model\ResourceModel\Role\CollectionFactory
     */
    private $roleCollectionFactory;

    /**
     * @var \Magento\Company\Model\UserRoleFactory
     */
    private $userRoleFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Company\Api\RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var \Magento\Company\Api\RoleManagementInterface
     */
    private $roleManagement;

    /**
     * @var \Magento\Company\Model\CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * @var \Magento\Framework\Acl\Data\CacheInterface
     */
    private $aclDataCache;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     *
     * @param \Magento\Company\Model\ResourceModel\UserRole\CollectionFactory $userRoleCollectionFactory
     * @param \Magento\Company\Model\ResourceModel\Role\CollectionFactory $roleCollectionFactory
     * @param \Magento\Company\Model\UserRoleFactory $userRoleFactory
     * @param \Magento\Company\Api\RoleRepositoryInterface $roleRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Company\Api\RoleManagementInterface $roleManagement
     * @param \Magento\Company\Model\CompanyAdminPermission $companyAdminPermission
     * @param \Magento\Framework\Acl\Data\CacheInterface $aclDataCache
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Company\Model\ResourceModel\UserRole\CollectionFactory $userRoleCollectionFactory,
        \Magento\Company\Model\ResourceModel\Role\CollectionFactory $roleCollectionFactory,
        \Magento\Company\Model\UserRoleFactory $userRoleFactory,
        \Magento\Company\Api\RoleRepositoryInterface $roleRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Company\Api\RoleManagementInterface $roleManagement,
        \Magento\Company\Model\CompanyAdminPermission $companyAdminPermission,
        \Magento\Framework\Acl\Data\CacheInterface $aclDataCache,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->userRoleCollectionFactory = $userRoleCollectionFactory;
        $this->roleCollectionFactory = $roleCollectionFactory;
        $this->userRoleFactory = $userRoleFactory;
        $this->roleRepository = $roleRepository;
        $this->customerRepository = $customerRepository;
        $this->roleManagement = $roleManagement;
        $this->companyAdminPermission = $companyAdminPermission;
        $this->aclDataCache = $aclDataCache;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function assignRoles($userId, array $roles)
    {
        $customer = $this->customerRepository->getById($userId);
        $companyId = $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId();
        $firstRole = reset($roles);
        if (empty($firstRole) || !$firstRole->getId()) {
            throw new \Magento\Framework\Exception\InputException(
                __('"%fieldName" is required. Enter and try again.', ['fieldName' => 'id'])
            );
        }
        if (!$companyId) {
            throw new \Magento\Framework\Exception\InputException(
                __(
                    'You cannot update the requested attribute. Row ID: %fieldName = %fieldValue.',
                    ['fieldName' => 'roleId', 'fieldValue' => $firstRole->getId()]
                )
            );
        } else {
            $this->validateDataBeforeAssignRoles($companyId, $userId, $roles);
        }
        $userRoleCollection = $this->userRoleCollectionFactory->create();
        $userRoleCollection->addFieldToFilter('user_id', ['eq' => $userId])->load();
        $userRoles = $userRoleCollection->getItems();
        foreach ($userRoles as $userRole) {
            $userRole->delete();
        }
        foreach ($roles as $role) {
            $userRole = $this->userRoleFactory->create();
            $userRole->setRoleId($role->getId());
            $userRole->setUserId($userId);
            $userRole->save();
        }
        $this->aclDataCache->clean();
    }

    /**
     * {@inheritdoc}
     */
    public function assignUserDefaultRole($userId, $companyId)
    {
        $role = $this->roleManagement->getCompanyDefaultRole($companyId);
        $this->assignRoles($userId, [$role]);
    }

    /**
     * {@inheritdoc}
     */
    public function getRolesByUserId($userId)
    {
        try {
            $isCompanyAdmin = $this->companyAdminPermission->isGivenUserCompanyAdmin($userId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $isCompanyAdmin = false;
        }

        if ($isCompanyAdmin) {
            return [$this->roleManagement->getAdminRole()];
        }

        $userRoleCollection = $this->userRoleCollectionFactory->create();
        $userRoleCollection->addFieldToFilter('user_id', ['eq' => $userId])->load();
        $userRoles = $userRoleCollection->getItems();
        if (!count($userRoles)) {
            return [];
        }
        $roleIds = [];
        foreach ($userRoles as $userRole) {
            $roleIds[] = $userRole->getRoleId();
        }
        $roleCollection = $this->roleCollectionFactory->create();
        $roleCollection->addFieldToFilter('role_id', ['in' => $roleIds])->load();
        if (!$roleCollection->getSize()) {
            return [];
        }

        return $roleCollection->getItems();
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersByRoleId($roleId)
    {
        $this->roleRepository->get($roleId);
        $userRoleCollection = $this->userRoleCollectionFactory->create();
        $userRoleCollection->addFieldToFilter('role_id', ['eq' => $roleId])->load();
        $userIds = $userRoleCollection->getColumnValues('user_id');
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $userIds, 'in')
            ->create();
        return $this->customerRepository->getList($searchCriteria)->getItems();
    }

    /**
     * {@inheritdoc}
     */
    public function getUsersCountByRoleId($roleId)
    {
        $userRoleCollection = $this->userRoleCollectionFactory->create();
        $userRoleCollection->addFieldToFilter('role_id', ['eq' => $roleId]);

        return (int)$userRoleCollection->getSize();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRoles($userId)
    {
        $userRoleCollection = $this->userRoleCollectionFactory->create();
        $userRoleCollection->addFieldToFilter('user_id', ['eq' => $userId])->load();
        $userRoles = $userRoleCollection->getItems();
        foreach ($userRoles as $userRole) {
            $userRole->delete();
        }
        $this->aclDataCache->clean();
    }

    /**
     * Validates data before change a role for a company user.
     *
     * @param int $companyId
     * @param int $userId
     * @param \Magento\Company\Api\Data\RoleInterface[] $roles
     * @throws \Magento\Framework\Exception\InputException
     * @return void
     */
    private function validateDataBeforeAssignRoles($companyId, $userId, array $roles)
    {
        if ($this->companyAdminPermission->isGivenUserCompanyAdmin($userId)) {
            throw new \Magento\Framework\Exception\InputException(
                __('You cannot assign a different role to a company admin.')
            );
        }
        $companyRoles = $this->roleManagement->getRolesByCompanyId($companyId);
        $companyRoleIds = $this->prepareRoleIds($companyRoles);
        $assignedRoleId = $this->prepareRoleIds($roles);
        if (count($assignedRoleId) > 1) {
            throw new \Magento\Framework\Exception\InputException(
                __('You cannot assign multiple roles to a user.')
            );
        }
        if (array_diff($assignedRoleId, $companyRoleIds)) {
            throw new \Magento\Framework\Exception\InputException(
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['fieldName' => 'role_id', 'value' => $assignedRoleId[0]]
                )
            );
        }
    }

    /**
     * Prepare role ids for validation, before change a role for a company user.
     *
     * @param \Magento\Company\Api\Data\RoleInterface[] $roles
     * @return array
     */
    private function prepareRoleIds(array $roles)
    {
        $roleIds = [];
        /** @var \Magento\Company\Api\Data\RoleInterface $role */
        foreach ($roles as $role) {
            $roleIds[] = $role->getId();
        }
        return $roleIds;
    }
}
