<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Redington\Quotation\Observer;
use Magento\Framework\Event\Observer;

/**
 * Increase reward points balance for sales rules applied to orders.
 */
class EarnForOrder extends \Magento\Reward\Observer\EarnForOrder
{
    

    /**
     * Process order.
     *
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->_restriction->isAllowed() === false) {
            return;
        }

        $event = $observer->getEvent();
        $orders = $event->getOrders() ?: [$event->getOrder()];
        /* @var $order Order */
        foreach ($orders as $order) {
            //$this->saveOrderHistoryComment($order);
        }
    }

    
}
