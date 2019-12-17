<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Redington\Company\Controller\Customer;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

/**
 * Controller for retrieving customer info on the frontend.
 */
class Get extends \Magento\Company\Controller\AbstractAction implements HttpGetActionInterface
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\Company\Api\AclInterface
     */
    private $acl;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structureManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Company\Model\Company\Structure $structureManager
     * @param \Magento\Company\Api\AclInterface $acl
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Company\Model\Company\Structure $structureManager,
        \Magento\Company\Api\AclInterface $acl
    ) {
        parent::__construct($context, $companyContext, $logger);
        $this->acl = $acl;
        $this->customerRepository = $customerRepository;
        $this->structureManager = $structureManager;
    }

    /**
     * Get customer action.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest();

        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());
        $customerId = $request->getParam('customer_id');

        if (!in_array($customerId, $allowedIds['users'])) {
            return $this->jsonError(__('You are not allowed to do this.'));
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $companyAttributes = null;
            if ($customer->getExtensionAttributes() !== null
                && $customer->getExtensionAttributes()->getCompanyAttributes() !== null
            ) {
                $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->handleJsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            
            return $this->handleJsonError();
        }

        $customerData = $customer->__toArray();
        if ($companyAttributes !== null) {
            $customerData['extension_attributes[company_attributes][job_title]'] = $companyAttributes->getJobTitle();
            $customerData['extension_attributes[company_attributes][telephone]'] = $companyAttributes->getTelephone();
            $customerData['extension_attributes[company_attributes][status]'] = $companyAttributes->getStatus();
            $customerData['extension_attributes[company_attributes][country_code]'] = $companyAttributes->getCountryCode();
        }
        $roles = $this->acl->getRolesByUserId($customerId);
        if (count($roles)) {
            foreach ($roles as $role) {
                $customerData['role'] = $role->getId();
                break;
            }
        }
        return $this->jsonSuccess($customerData);
    }
}
