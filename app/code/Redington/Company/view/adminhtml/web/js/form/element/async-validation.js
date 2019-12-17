/**
 * Copyright © Redington. All rights reserved.
 * 
 */

define([
    'jquery',
    'jquery/ui',
    'mage/validation'
], function ($) {
    'use strict';

    $.widget('mage.formElementAsyncValidation', {
        options: {
            validateUrl: '',
            name: ''
        },

        /**
         * @private
         */
        _create: function () {
            this._bind();
            this.initialValue = $(this.element).val();
        },

        /**
         * Bind observers
         *
         * @private
         */
        _bind: function () {
            this._on({
                focusout:  this._validateField.bind(this)
            });
        },

        /**
         * Validate input field
         *
         * @private
         */
        _validateField: function () {
            var isValidEmail,
                currentValue = $(this.element).val();

            $(this.element).data('async-is-valid', true);
            isValidEmail = $.validator.validateSingleElement(this.element);
            $(this.element).data('async-is-valid', false);

            if (!isValidEmail || this.initialValue === currentValue) {
                $(this.element).data('async-is-valid', true);

                return false;
            }

            this._validate(currentValue)
                .done(function (data) {
                    $(this.element).data('async-is-valid', data[this.options.name]);
                    $.validator.validateSingleElement(this.element);
                }.bind(this))
                .fail(function () {
                    $(this.element).data('async-is-valid', false);
                    $.validator.validateSingleElement(this.element);
                }.bind(this));
        },

        /**
         * Validate value
         *
         * @param {String} value
         * @returns {jQuery.Promise}
         * @private
         */
        _validate: function (value) {
            var data = {};

            data[this.options.name] =  value;

            return $.ajax({
                url: this.options.validateUrl,
                data: data,
                type: 'post',
                dataType: 'json',
                showLoader: true
            });
        }
    });

    return $.mage.formElementAsyncValidation;
});
