/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Pdc
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
                if (checkoutData.getSelectedPaymentMethod() == "pdc") {
                    var imageValidationResult = false;

                    if ($('#pdc-payment-reference-no').val() != '') {
                        imageValidationResult = true;
                        $('.pdc-reference-error-message').html();
                    } else {
                        imageValidationResult = $.mage.__('Please enter the reference number.');
                        $('.pdc-reference-error-message').html(imageValidationResult);
                        return false;
                    }
                    if ($('#pdc_document').val() != '') {
                        imageValidationResult = true;
                        $('.pdc-document-message-section').html();
                    } else {
                        imageValidationResult = $.mage.__('Please upload file.');
                        $('.pdc-document-message-section').html(imageValidationResult);
                        return false;
                    }
                    return imageValidationResult;
                }
            }
        };
    }
);
