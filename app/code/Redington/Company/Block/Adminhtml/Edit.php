<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */
namespace Redington\Company\Block\Adminhtml;

use Magento\Company\Model\ResourceModel\Company\CollectionFactory;
/**
 * Class Edit Block
 */
class Edit extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Company\Api\RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var \Magento\Company\Api\Data\RoleInterfaceFactory
     */
    private $roleFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Magento\Framework\Acl\AclResource\ProviderInterface
     */
    private $resourceProvider;

    /**
     * @var \Magento\Company\Model\Authorization\PermissionProvider
     */
    private $permissionProvider;
    /**
     * @var CollectionFactory
     */
    private $companyCollectionFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context        $context
     * @param \Magento\Company\Api\RoleRepositoryInterface            $roleRepository
     * @param \Magento\Company\Api\Data\RoleInterfaceFactory          $roleFactory
     * @param \Magento\Framework\Json\Helper\Data                     $jsonHelper
     * @param \Magento\Framework\Acl\AclResource\ProviderInterface    $resourceProvider
     * @param \Magento\Company\Model\Authorization\PermissionProvider $permissionProvider
     * @param CollectionFactory                                       $companyCollectionFactory
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Company\Api\RoleRepositoryInterface $roleRepository,
        \Magento\Company\Api\Data\RoleInterfaceFactory $roleFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Acl\AclResource\ProviderInterface $resourceProvider,
        \Magento\Company\Model\Authorization\PermissionProvider $permissionProvider,
        CollectionFactory $companyCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->roleRepository = $roleRepository;
        $this->roleFactory = $roleFactory;
        $this->jsonHelper = $jsonHelper;
        $this->companyCollectionFactory = $companyCollectionFactory;
        $this->resourceProvider = $resourceProvider;
        $this->permissionProvider = $permissionProvider;
    }
    /**
     * Constructor for Action Urls
     *
     * @return void
     */
   

    /**
     * Get Role.
     *
     * @return \Magento\Company\Api\Data\RoleInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRole()
    {
        $id = $this->retrieveRoleId();
        if ($id) {
            $role = $this->roleRepository->get($id);
            if ($this->isDuplicate()) {
                $role->setId(null);
                $role->setRoleName($role->getRoleName() . ' - ' . __('Duplicated'));
            }
            return $role;
        }

        return $this->roleFactory->create();
    }

    /**
     * Get tree JS options.
     *
     * @return array
     */
    public function getTreeJsOptions()
    {
        $permissions = [];
        $roleId = $this->retrieveRoleId();
        if ($roleId) {
            $permissions = $this->permissionProvider->retrieveRolePermissions($roleId);
        }
        $resources = $this->resourceProvider->getAclResources();
        return [
            'roleTree' => [
                'data' => $this->prepareTreeData($resources, $permissions),
            ],
        ];
    }

    /**
     * Prepare tree data.
     *
     * @param array $resources
     * @param array $permissions
     * @param int   $level
     *
     * @return array
     */
    private function prepareTreeData(array &$resources, array $permissions, $level = 0)
    {
        for ($counter = 0; $counter < count($resources); ++$counter) {
            $resources[$counter]['text'] = $resources[$counter]['title'];
            unset($resources[$counter]['title']);
            unset($resources[$counter]['sort_order']);
            $resources[$counter]['state'] = [];
            if (!empty($resources[$counter]['children'])) {
                $this->prepareTreeData($resources[$counter]['children'], $permissions, $level + 1);
                $resources[$counter]['state']['opened'] = 'open';
            }
            if (isset($permissions[$resources[$counter]['id']])
                && $permissions[$resources[$counter]['id']] == 'allow') {
                $resources[$counter]['state']['selected'] = true;
            }
            if ($level == 0) {
                $resources[$counter]['li_attr'] = ['class' => 'root-collapsible'];
            }
        }

        return $resources;
    }

    /**
     * Retrieve role Id.
     *
     * @return string
     */
    private function retrieveRoleId()
    {
        return $this->getRequest()->getParam('id') ?: $this->getRequest()->getParam('duplicate_id');
    }

    /**
     * Check if duplicate.
     *
     * @return bool
     */
    private function isDuplicate()
    {
        return $this->getRequest()->getParam('id') ? false : true;
    }

    /**
     * Get json helper.
     *
     * @return \Magento\Framework\Json\Helper\Data
     */
    public function getJsonHelper()
    {
        return $this->jsonHelper;
    }
    /**
     * Function for list of companies
     *
     * @return array
     */
    public function getCompanies()
    {
        /** @var \Magento\Company\Model\ResourceModel\Company\Collection $collection */
        $collection = $this->companyCollectionFactory->create();

        return $collection;
    }
    /**
     * Function for selected company
     *
     * @return int
     */
    public function getSelectedCompany()
    {
        $roleId = $this->retrieveRoleId();
        if($roleId) :
        $role = $this->roleRepository->get($roleId);
        return $role->getCompanyId();
        endif;
    }   
    /**
     * Function for Back Button Url
     *
     * @return string
     */
    public function getBackUrl() {
        return $this->getUrl('*/*/index');
    }

}
