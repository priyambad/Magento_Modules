/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Pdc
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Redington_Pdc/js/model/pdcvalidator'
    ],
    function (Component, additionalValidators, pdcvalidator) {
        'use strict';
        additionalValidators.registerValidator(pdcvalidator);
        return Component.extend({});
    }
);