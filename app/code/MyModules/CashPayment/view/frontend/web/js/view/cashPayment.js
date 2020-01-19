/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CashPayment
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Redington_CashPayment/js/model/cashvalidator'
    ],
    function (Component, additionalValidators, cashvalidator) {
        'use strict';
        additionalValidators.registerValidator(cashvalidator);
        return Component.extend({});
    }
);