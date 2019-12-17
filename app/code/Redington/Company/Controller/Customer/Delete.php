<?php

namespace Redington\Company\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;

/**
 * Controller for deleting a customer from the frontend.
 */
class Delete extends \Magento\Company\Controller\Customer\Delete
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structureManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Company\Model\Company\Structure $structureManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Company\Model\Company\Structure $structureManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context, $companyContext, $logger, $structureManager, $customerRepository);
        $this->structureManager = $structureManager;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Delete team action.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest();

        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());
        $customerId = $request->getParam('customer_id');
        if ($customerId == $this->companyContext->getCustomerId()) {
            return $this->jsonError(__('You cannot delete yourself.'));
        }

        if (!in_array($customerId, $allowedIds['users'])) {
            return $this->jsonError(__('You are not allowed to do this.'));
        }

        $structure = $this->structureManager->getStructureByCustomerId($customerId);
        if ($structure === null) {
            return $this->jsonError(__('Cannot delete this user.'));
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            /** @var CompanyCustomerInterface $companyAttributes */
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            $companyAttributes->setStatus(CompanyCustomerInterface::STATUS_INACTIVE);
            $this->customerRepository->save($customer);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->handleJsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            
            return $this->handleJsonError();
        }

        return $this->handleJsonSuccess(__('The company user was successfully set to inactive.'));
    }
}
