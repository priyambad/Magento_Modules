/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'jquery/ui',
    'Magento_NegotiableQuote/quote/create/negotiated-price',
    'mage/translate'
], function ($) {
    'use strict';

    $.widget('mage.saveAsDraft', {

        options: {
            negotiatedPriceTable: '.negotiated_price_type .data-table',
            delFiles: [],
            reload: false,
            form: '[data-action="comment-form"]',
            updateEl: '[data-role="update-quote"]',
            formKey: window.FORM_KEY,
            blockToSend: '[data-role="items-errors"]',
            shippingMethodForm: '[data-role="quote-shipping-price-form"]'
        },

        /**
         * Build widget
         *
         * @private
         */
        _create: function () {
            this._bind();
        },

        /**
         * Scroll to Element
         *
         * @private
         */
       /* _scrollTo: function (element) {
            $('html, body').animate({
                scrollTop: element.offset().top - 50
            }, 200);
        },*/

        /**
         * Check validate of shipping method
         *
         * @return {Boolean} true if form valid and false if not
         * @private
         */
        _checkShippingMethod: function () {
            var form = $(this.options.shippingMethodForm);

            return !form.length || $(this.options.shippingMethodForm).valid();
        },

        /**
         * Swt data of delete files
         *
         * @private
         */
        _setDelFiles: function (e, data) {
            if (data) {
                this.options.delFiles.push(data);
            }
        },

        /**
         * Set events
         *
         * @private
         */
        _bind: function () {
            this.element.on('setDelFiles', $.proxy(this._setDelFiles, this))
                .on('click', $.proxy(this._sendData, this));
        },

        /**
         * Set data for remote query
         *
         * @private
         */
        _sendData: function () {
            var expirationDateValue = 0,
                $negotiatedPriceTable = $(this.options.negotiatedPriceTable),
                formData = new FormData($(this.options.form)[0]),
                expirationDate, $expirationEl;

            if ($(this.options.blockToSend).children().length) {
                $(this.options.blockToSend).trigger('notification');

                return;
            }

            if (this.options.delFiles) {
                formData.append('delFiles', this.options.delFiles);
            }

            /*if ($negotiatedPriceTable.negotiatedPrice('option', 'isError')) {
                //this._scrollTo($negotiatedPriceTable);

                return false; //eslint-disable-line consistent-return
            }*/

            $expirationEl = $('[name="quote[expiration_date]"]');

            if ($expirationEl.length) {
                expirationDate = $expirationEl.datepicker('getDate');
            }

            if (expirationDate) {
                expirationDateValue = $.datepicker.formatDate('yy-mm-dd', expirationDate);
            }
            formData.append('quote[expiration_period]', expirationDateValue);

            if (this._checkShippingMethod()) {
                $(this.options.updateEl).trigger('remoteQuery', {
                    url: this.options.saveUrl,
                    data: formData,
                    needReload: this.options.reload
                });
            }
        }
    });

    return $.mage.saveAsDraft;
});
