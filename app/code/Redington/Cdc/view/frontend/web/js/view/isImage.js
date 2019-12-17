/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Cdc
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Redington_Cdc/js/model/imagevalidator'
    ],
    function (Component, additionalValidators, imagevalidator) {
        'use strict';
        additionalValidators.registerValidator(imagevalidator);
        return Component.extend({});
    }
);