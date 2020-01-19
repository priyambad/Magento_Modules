/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'underscore',
    'Magento_Ui/js/form/form',
	'Redington_Checkout/js/model/forwarder-list'
],
function (
    ko,
    _,
    Component,
	ForwarderList
) {
    'use strict';
	return Component.extend({
        defaults: {
            template: 'Redington_Checkout/forwarder/forwarder'
        },
		addressOptions: ForwarderList(),
		 /**
         * @param {Object} address
         * @return {*}
         */
        addressOptionsText: function (address) {
            return address.getAddressInline();
        },
		/**
         * @param {Object} address
         * @return {*}
         */
        addressOptionsValue: function (address) {
            return address.getAddressId();
        }
    });
	
});
