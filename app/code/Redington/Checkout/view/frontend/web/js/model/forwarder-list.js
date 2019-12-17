/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'ko',
    './customer-forwarder-addresses'
], function (ko, defaultForwarderProvider) {
    'use strict';
    return ko.observableArray(defaultForwarderProvider.getAddressItems());
});
