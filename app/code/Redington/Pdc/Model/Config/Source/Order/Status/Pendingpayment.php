<?php
/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Pdc
 */
namespace Redington\Pdc\Model\Config\Source\Order\Status;

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
