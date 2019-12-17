/**
 * Copyright © Redington. All rights reserved.
 * 
 */
define([
    'underscore',
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (_, Component, customerData) {
    'use strict';

    var authorization = customerData.get('company_authorization');

    return {
        /**
         * Is resource allowed.
         *
         * @param {String} resource
         * @returns {Boolean}
         */
        isAllowed: function (resource) {
            var resources = authorization().resources;

            return _.isObject(resources) && resources[resource];
        }
    };
});
