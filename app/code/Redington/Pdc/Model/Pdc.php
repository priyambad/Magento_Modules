<?php
/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Pdc
 */

namespace Redington\Pdc\Model;

/**
 * Pay In Store payment method model
 */
class Pdc extends \Magento\Payment\Model\Method\AbstractMethod
{

/**
 * Payment code
 *
 * @var string
 */
    protected $_code = 'pdc';

/**
 * Availability option
 *
 * @var bool
 */
    protected $_isOffline = true;
}
