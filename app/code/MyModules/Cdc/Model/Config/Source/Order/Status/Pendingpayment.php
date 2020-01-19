<?php
/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Cdc
 */

 namespace Redington\Cdc\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

/**
 * Order Status source model
 */
class Pendingpayment extends Status
{
/**
 * @var string[]
 */
    protected $_stateStatuses = [Order::STATE_PENDING_PAYMENT];
}
