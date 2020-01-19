<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Company
 */
namespace Redington\Company\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Company\Model\Action\SaveCustomer as CustomerAction;

/**
 * Controller for creating a customer.
 */
class Create extends \Magento\Company\Controller\Customer\Create
{
   
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Psr\Log\LoggerInterface $logger
     * @param Structure $structureManager
     * @param CustomerAction $customerAction
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Psr\Log\LoggerInterface $logger,
        Structure $structureManager,
        CustomerAction $customerAction, 
        \Redington\Company\Helper\Data $redingtonHelperData
    ) {
        parent::__construct($context, $companyContext, $logger, $structureManager, $customerAction);
        $this->structureManager = $structureManager;
        $this->customerAction = $customerAction;
        $this->redingtonHelperData = $redingtonHelperData;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $request = $this->getRequest();
        $targetId = $request->getParam('target_id');
        $allowedIds = $this->structureManager->getAllowedIds($this->companyContext->getCustomerId());
        
        $totalUsers = $this->redingtonHelperData->getCompanyUserCount();
        $totalUsers = $totalUsers - 1;
        $maxlimit = $this->redingtonHelperData->getMaxUsers();
        if($totalUsers >= $maxlimit) {
            return $this->handleJsonError(__('You can not add more than '.$maxlimit. ' users.'));
        }
        if ($targetId && !in_array($targetId, $allowedIds['structures'])) {
            return $this->handleJsonError(__('You are not allowed to do this.'));
        } elseif (!$targetId) {
            $structure = $this->structureManager
                ->getStructureByCustomerId($this->companyContext->getCustomerId());
            if ($structure === null) {
                return $this->handleJsonError(__('Cannot create the customer.'));
            }
        }

        try {
            $customer = $this->customerAction->create($this->getRequest());
        } catch (InputMismatchException $e) {
            return $this->jsonError(
                __(
                    'A user with this email address already exists in the system. '
                    . 'Enter a different email address to create this user.'
                ),
                [
                    'field' => 'email'
                ]
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->handleJsonError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            
            return $this->handleJsonError();
        }

        return $this->handleJsonSuccess(__('The company user was successfully created.'), $customer->__toArray());
    }
}
