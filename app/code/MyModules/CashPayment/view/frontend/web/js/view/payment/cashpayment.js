/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CashPayment
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'cashpayment',
                component: 'Redington_CashPayment/js/view/payment/method-renderer/cashpayment-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);