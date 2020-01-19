<?php
/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Cdc
 */

namespace Redington\Cdc\Model;

/**
 * Pay In Store payment method model
 */
class Cdc extends \Magento\Payment\Model\Method\AbstractMethod
{

/**
 * Payment code
 *
 * @var string
 */
    protected $_code = 'cdc';

/**
 * Availability option
 *
 * @var bool
 */
    protected $_isOffline = true;
}
