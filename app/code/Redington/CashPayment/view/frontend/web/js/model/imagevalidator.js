/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CashPayment
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
                if (checkoutData.getSelectedPaymentMethod() == "R032") {
                    var imageValidationResult = false;

                    if ($('#cash-payment-reference-no').val() != '') {
                        imageValidationResult = true;
                        $('.cash-reference-error-message').html();
                    } else {
                        imageValidationResult = $.mage.__('Please enter the reference number.');
                        $('.cash-reference-error-message').html(imageValidationResult);
                        return false;
                    }
                    if ($('#cash_document').val() != '') {
                        imageValidationResult = true;
                        $('.cash-document-message-section').html();
                    } else {
                        imageValidationResult = $.mage.__('Please upload file.');
                        $('.cash-document-message-section').html(imageValidationResult);
                        return false;
                    }
                    return imageValidationResult;
                }
            }
        };
    }
);
