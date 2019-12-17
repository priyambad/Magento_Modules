<?php
/**
 * Copyright © Redington. All rights reserved.
 * 
 */

namespace Redington\Company\Controller\Adminhtml\Role;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Validate.
 */
class Validate extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::roles_edit';

    /**
     * @var \Magento\Company\Model\CompanyUser
     */
    private $companyUser;

    /**
     * @var \Magento\Company\Api\RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Action\Context        $context
     * @param \Magento\Company\Model\CompanyUser           $companyUser
     * @param \Magento\Company\Api\RoleRepositoryInterface $roleRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Company\Model\CompanyUser $companyUser,
        \Magento\Company\Api\RoleRepositoryInterface $roleRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->companyUser = $companyUser;
        $this->roleRepository = $roleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $resultJson->setData([
            'company_role_name' => $this->isCompanyRoleNameValid($this->getRequest()->getParam('company_role_name')),
        ]);

        return $resultJson;
    }

    /**
     * Is company role name valid.
     *
     * @param string $roleName
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isCompanyRoleNameValid($roleName)
    {
        //$companyId = $this->companyUser->getCurrentCompanyId();
        $companyId = $this->getRequest()->getParam('companies');
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Company\Api\Data\RoleInterface::ROLE_NAME, $roleName)
            ->addFilter(\Magento\Company\Api\Data\RoleInterface::COMPANY_ID, $companyId)
            ->create();

        return !$this->roleRepository->getList($searchCriteria)->getTotalCount();
    }
}
