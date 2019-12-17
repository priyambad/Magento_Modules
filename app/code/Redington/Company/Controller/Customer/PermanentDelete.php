<?php

namespace Redington\Company\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Delete company user on storefront.
 */
class PermanentDelete extends \Magento\Company\Controller\Customer\PermanentDelete
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
     * PermanentDelete constructor.
     *
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
     * Delete customer action.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $customerId = $this->getRequest()->getParam('customer_id');

        if ($customerId == $this->companyContext->getCustomerId()) {
            return $this->jsonError(__('You cannot delete yourself.'));
        }

        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());

        if (!in_array($customerId, $allowedIds['users'])) {
            return $this->jsonError(__('You are not allowed to do this.'));
        }

        if ($this->structureManager->getStructureByCustomerId($customerId) === null) {
            return $this->jsonError(__('Cannot delete this user.'));
        }

        try {
            $this->customerRepository->deleteById($customerId);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->handleJsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            
            return $this->handleJsonError();
        }
        
        return $this->handleJsonSuccess(__('The company user was successfully deleted.'));
    }
}
