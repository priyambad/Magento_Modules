<?php
/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CashPayment
 */

namespace Redington\CashPayment\Model;

/**
 * Pay In Store payment method model
 */
class Cashpayment extends \Magento\Payment\Model\Method\AbstractMethod
{

/**
 * Payment code
 *
 * @var string
 */
    protected $_code = 'cashpayment';

/**
 * Availability option
 *
 * @var bool
 */
    protected $_isOffline = true;
}
