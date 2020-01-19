<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Redington\Company\Block\Company;

/**
 * Class CompanyProfile
 *
 * @api
 * @since 100.0.0
 */
class CompanyProfile extends \Magento\Company\Block\Company\CompanyProfile
{
    

    /**
     * CompanyProfile constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\Company\Api\CompanyManagementInterface $companyManagement
     * @param \Magento\Company\Model\CountryInformationProvider $countryInformationProvider
     * @param \Magento\User\Model\UserFactory $userFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Customer\Api\CustomerNameGenerationInterface $customerViewHelper
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param \Magento\Company\Model\CustomerFactory $customerFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\Company\Api\CompanyManagementInterface $companyManagement,
        \Magento\Company\Model\CountryInformationProvider $countryInformationProvider,
        \Magento\User\Model\UserFactory $userFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Api\CustomerNameGenerationInterface $customerViewHelper,
        \Magento\Company\Api\AuthorizationInterface $authorization,
        \Magento\Company\Model\CustomerFactory $customerFactory,
        array $data = []
    ) {
        $this->customerFactory = $customerFactory;
        parent::__construct($context,$userContext,$companyManagement,$countryInformationProvider,$userFactory,$messageManager,$customerViewHelper,$authorization, $data);
    }


    /**
     * Get company admin mobile number
     *
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @return string
     */
    public function getAdminContact(\Magento\Company\Api\Data\CompanyInterface $company){
        try{
            $companyAdmin = $this->getCompanyAdmin($company);
            $companyAdminId = $companyAdmin->getId();
            $admin = $this->customerFactory->create()->load($companyAdminId);
            return $admin->getCountryCode().''.$admin->getTelephone();
        }catch(\Exception $e){
            return false;
        }
    }
}
