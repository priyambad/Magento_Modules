<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */

namespace Redington\Company\Controller\Adminhtml\Role;

/**
 * Class EditPost.
 */
class EditPost extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::roles_edit';

    /**
     * @var \Magento\Company\Api\RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var \Magento\Company\Api\Data\RoleInterfaceFactory
     */
    private $roleFactory;

    /**
     * @var \Magento\Company\Model\CompanyUser
     */
    private $companyUser;

    /**
     * @var \Magento\Company\Model\PermissionManagementInterface
     */
    private $permissionManagement;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * EditPost constructor.
     *
     * @param \Magento\Framework\App\Action\Context                $context
     * @param \Psr\Log\LoggerInterface                             $logger
     * @param \Magento\Company\Api\RoleRepositoryInterface         $roleRepository
     * @param \Magento\Company\Api\Data\RoleInterfaceFactory       $roleFactory
     * @param \Magento\Company\Model\CompanyUser                   $companyUser
     * @param \Magento\Company\Model\PermissionManagementInterface $permissionManagement
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Company\Api\RoleRepositoryInterface $roleRepository,
        \Magento\Company\Api\Data\RoleInterfaceFactory $roleFactory,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Magento\Company\Model\PermissionManagementInterface $permissionManagement
    ) {
        parent::__construct($context);
        $this->roleRepository = $roleRepository;
        $this->roleFactory = $roleFactory;
        $this->companyUser = $companyUser;
        $this->logger = $logger;
        $this->permissionManagement = $permissionManagement;
    }

    /**
     * Roles and permissions edit post.
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $role = $this->roleFactory->create();
        $id = $this->getRequest()->getParam('id');

        $companyId = empty($this->getRequest()->getParam('companies')) ? $this->companyUser->getCurrentCompanyId() : $this->getRequest()->getParam('companies');

        try {
            if ($id) {
                $role = $this->roleRepository->get($id);
            }
            $role->setRoleName($this->getRequest()->getParam('role_name'));
            $role->setCompanyId($companyId);
            $resources = explode(',', $this->getRequest()->getParam('role_permissions'));
            $role->setPermissions($this->permissionManagement->populatePermissions($resources));
            $this->roleRepository->save($role);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->critical($e);

            if ($id) {
                $result = $this->_redirect('*/role/edit', ['id' => $id]);
            } else {
                $result = $this->_redirect('*/role/edit');
            }

            return $result;
        }

        return $this->_redirect('*/*/');
    }
}
