<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Redington\Company\Plugin\Sales\Controller\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Controller\Order\View;
use Magento\Framework\View\Result\PageFactory;

/**
 * Restrict access to the order view page depending on permissions for company users.
 */
class ViewPlugin extends \Magento\Company\Plugin\Sales\Controller\Order\ViewPlugin
{
    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Company\Api\AuthorizationInterface
     */
    private $authorization;

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $companyStructure;

    /**
     * @var \Magento\Company\Model\CompanyContext
     */
    private $companyContext;

    /**
     * @var \Redington\Company\Helper\Data
     */
    public $redingtonHelperData;

    /**
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param \Magento\Company\Model\Company\Structure $companyStructure
     * @param \Magento\Company\Model\CompanyContext $companyContext
     * @param \Redington\Company\Helper\Data $redingtonHelperData
     */
    public function __construct(
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Company\Api\AuthorizationInterface $authorization,
        \Magento\Company\Model\Company\Structure $companyStructure,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Redington\Company\Helper\Data $redingtonHelperData,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->userContext = $userContext;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->authorization = $authorization;
        $this->companyStructure = $companyStructure;
        $this->companyContext = $companyContext;
        $this->redingtonHelperData = $redingtonHelperData;
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        parent::__construct(
            $userContext,
            $resultRedirectFactory,
            $orderRepository,
            $request,
            $authorization,
            $companyStructure,
            $companyContext
        );
    }

    /**
     * View around execute plugin.
     *
     * @param View $subject
     * @param \Closure $proceed
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        View $subject,
        \Closure $proceed
    ) {
        $customerId = $this->userContext->getUserId();
        if ($customerId) {
            $orderId = $this->request->getParam('order_id');
            try {
                $order = $this->orderRepository->get($orderId);
            } catch (NoSuchEntityException $exception) {
                return $proceed();
            }

            if (!$this->canViewOrder($order)) {
                $resultRedirect = $this->resultRedirectFactory->create();

                if ($this->companyContext->isCurrentUserCompanyUser()) {
                    $resultRedirect->setPath('company/accessdenied');
                } else {
                    $resultRedirect->setPath('noroute');
                }

                return $resultRedirect;
            }
        }
        $this->registry->register('current_order', $order);
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
        // return $proceed();
    }

    /**
     * Order can be viewed.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function canViewOrder(\Magento\Sales\Model\Order $order)
    {
        $customerId = $this->redingtonHelperData->getCompanyAdminId();
        //$customerId = $this->userContext->getUserId();
        $orderOwnerId = $order->getCustomerId();
        if ($orderOwnerId != $customerId &&
            (
                !$this->authorization->isAllowed('Magento_Sales::view_orders_sub') ||
                !$this->companyContext->isModuleActive()
            )
        ) {
            return false;
        }

        if ($this->companyContext->isCurrentUserCompanyUser()
            && !$this->authorization->isAllowed('Magento_Sales::view_orders')) {
            return false;
        }

        $subCustomers = $this->companyStructure->getAllowedChildrenIds($customerId);
        if (!in_array($orderOwnerId, $subCustomers) && $orderOwnerId != $customerId) {
            return false;
        }
        return true;
    }
}
