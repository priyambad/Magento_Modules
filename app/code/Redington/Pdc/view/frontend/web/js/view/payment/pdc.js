/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Pdc
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
                 type: 'pdc',
                 component: 'Redington_Pdc/js/view/payment/method-renderer/pdc-method'
             }
         );
         /** Add view logic here if needed */
         return Component.extend({});
    }
    );