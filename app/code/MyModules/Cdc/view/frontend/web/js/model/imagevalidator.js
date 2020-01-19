/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Cdc
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/checkout-data',
        'mage/validation'
    ],
    function ($, checkoutData) {
        'use strict';

        return {

            /**
             * Validate file
             *
             * @returns {Boolean}
             */
            validate: function () {
                if (checkoutData.getSelectedPaymentMethod() == "cdc") {
                    var imageValidationResult = false;

                    if ($('#cdc-payment-reference-no').val() != '') {
                        imageValidationResult = true;
                        $('.cdc-reference-error-message').html();
                    } else {
                        imageValidationResult = $.mage.__('Please enter the reference number.');
                        $('.cdc-reference-error-message').html(imageValidationResult);
                        return false;
                    }
                    if ($('#cdc_document').val() != '') {
                        imageValidationResult = true;
                        $('.cdc-document-message-section').html();
                    } else {
                        imageValidationResult = $.mage.__('Please upload file.');
                        $('.cdc-document-message-section').html(imageValidationResult);
                        return false;
                    }
                    return imageValidationResult;
                }
            }
        };
    }
);
