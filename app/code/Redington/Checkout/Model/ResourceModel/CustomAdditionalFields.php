<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Checkout
 */

namespace Redington\Checkout\Model\ResourceModel;

use Redington\Checkout\Api\Data\CustomAdditionalFieldsInterface;

class CustomAdditionalFields extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const MAIN_TABLE = 'amasty_amcheckout_additional';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, CustomAdditionalFieldsInterface::ID);
    }
}
