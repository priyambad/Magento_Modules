<?php

namespace Redington\Company\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Exception\State\InputMismatchException;

/**
 * Update customer for company structure.
 */
class Save extends \Magento\Company\Controller\Customer\Save
{
    /**
     * Authorization level of a company session.
     */
    const COMPANY_RESOURCE = 'Magento_Company::users_edit';

    /**
     * @var \Magento\Company\Model\Action\SaveCustomer
     */
    private $customerAction;

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structureManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Company\Model\Action\SaveCustomer $customerAction
     * @param \Magento\Company\Model\Company\Structure $structureManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Company\Model\Action\SaveCustomer $customerAction,
        \Magento\Company\Model\Company\Structure $structureManager
    ) {
        parent::__construct($context, $companyContext, $logger, $customerAction, $structureManager);
        $this->customerAction = $customerAction;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $request = $this->getRequest();

        $customerId = $request->getParam('customer_id');
        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());

        if (!in_array($customerId, $allowedIds['users'])) {
            throw new InputMismatchException(__('You are not allowed to do this.'));
        }

        try {
            $customer = $this->customerAction->update($request);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->handleJsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);

            return $this->handleJsonError();
        }

        return $this->handleJsonSuccess(__('The company user was successfully updated.'), $customer->__toArray());
    }
}
