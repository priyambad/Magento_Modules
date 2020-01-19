/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'ko',
	'./forwarderaddress'
], function ($, ko, Address) {
    'use strict';

    var isLoggedIn = ko.observable(window.isCustomerLoggedIn);
    return {
        /**
         * @return {Array}
         */
        getAddressItems: function () {
            var items = [],
                forwarderAddressArr = window.checkoutConfig.forwarderAddress;

            if (isLoggedIn()) {
                if (Object.keys(forwarderAddressArr).length) {
                    $.each(forwarderAddressArr, function (key, item) {
                        items.push(new Address(item));
                    });
                }
            }
            return items;
        }
    };
});
