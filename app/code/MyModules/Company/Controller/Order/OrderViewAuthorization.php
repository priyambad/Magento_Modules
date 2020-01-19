<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Redington\Company\Controller\Order;

/**
 * Class OrderViewAuthorization
 */
class OrderViewAuthorization extends \Magento\NegotiableQuote\Controller\Order\OrderViewAuthorization
{
    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structure;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    private $orderConfig;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Redington\Company\Helper\Data
     */
    public $redingtonHelperData;

    /**
     * @param \Magento\Company\Model\Company\Structure $structure
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Redington\Company\Helper\Data $redingtonHelperData
     */
    public function __construct(
        \Magento\Company\Model\Company\Structure $structure,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Redington\Company\Helper\Data $redingtonHelperData
    ) {
        $this->structure = $structure;
        $this->orderConfig = $orderConfig;
        $this->userContext = $userContext;
        $this->redingtonHelperData = $redingtonHelperData;
        parent::__construct(
            $structure,
            $orderConfig,
            $userContext
        );
    }

    /**
     * {@inheritdoc}
     */
    public function canView(\Magento\Sales\Model\Order $order)
    {
        $customerId = $this->redingtonHelperData->getCompanyAdminId();
        //$customerId = $this->userContext->getUserId();
        $availableStatuses = $this->orderConfig->getVisibleOnFrontStatuses();
        $allowedChildIds = $this->structure->getAllowedChildrenIds($customerId);
        $allowedChildIds[] = $customerId;

        if ($order->getId()
            && $order->getCustomerId()
            && in_array($order->getCustomerId(), $allowedChildIds)
            && in_array($order->getStatus(), $availableStatuses, true)
        ) {
            return true;
        }
        return false;
    }
}
