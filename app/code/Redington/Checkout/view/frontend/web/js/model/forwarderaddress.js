/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([], function () {
    'use strict';

    /**
     * Returns new address object.
     *
     * @param {Object} addressData
     * @return {Object}
     */
    return function (addressData) {
    
        return {
            customerAddressId: addressData.id,
            /**
             * @return {*}
             */
            getAddressInline: function () {
                return addressData.inline;
            },
			
			/**
             * @return {*}
             */
            getAddressId: function () {
                return addressData.id;
            }
        };
    };
});
