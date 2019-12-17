/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Cdc
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
                type: 'cdc',
                component: 'Redington_Cdc/js/view/payment/method-renderer/cdc-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);