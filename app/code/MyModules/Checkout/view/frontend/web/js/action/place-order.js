/**
 * Copyright � Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/place-order'
], function (quote, urlBuilder, customer, placeOrderService) {
    'use strict';

    return function (paymentData, messageContainer) {
        var serviceUrl, payload;
        payload = {
            cartId: quote.getQuoteId(),
            billingAddress: quote.billingAddress(),
            paymentMethod: paymentData,
            extensionAttribute: {
                warehouseCode: window.warehouseCode,
                forwarder_address_id: jQuery('#forwarder_address_id').val(),
                lpo_reference_document: jQuery('#reference_document_blob_url').val(),
                lpo_reference_no: jQuery('#lpo_reference_no').val(),
                cdc_document: jQuery('#cdc_document_blob_url').val(),
                pdc_document: jQuery('#pdc_document_blob_url').val(),
                pdc_ref_no: jQuery('#pdc-payment-reference-no').val(),
                cdc_ref_no: jQuery('#cdc-payment-reference-no').val(),
                cash_document: jQuery('#cash_document_blob_url').val(),
                cash_ref_no: jQuery('#cash-payment-reference-no').val()
            }
        };

        if (customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/carts/mine/payment-information', {});
        } else {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/payment-information', {
                quoteId: quote.getQuoteId()
            });
            payload.email = quote.guestEmail;
        }

        return placeOrderService(serviceUrl, payload, messageContainer);
    };
});
