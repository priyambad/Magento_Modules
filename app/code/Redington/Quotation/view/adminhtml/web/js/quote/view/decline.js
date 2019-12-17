/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/template',
    'text!Magento_NegotiableQuote/template/quote/form.html',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'jquery/validate',
    'mage/validation'
], function ($, mageTemplate, formTpl) {
    'use strict';

    $.widget('mage.decline', {
        options: {
            url: '',
            eventType: 'click',
            popupTitle: 'Decline Quote?',
            declineMessage: $.mage.__('Please specify the reason for decline and click on confirm.'), //eslint-disable-line max-len
            formKey: window.FORM_KEY,
            blockToShow: '[data-role="items-errors"]',
            formDeclineMessageBlock: '[data-role="modal-decline-notice"]',
            formDeclineMessageWarningBlock: '[data-role="modal-textarea-error"]',
            formDeclineInputsBlock: '[data-role="modal-decline-inputs"]',
            textareaLabel: $.mage.__('Please specify the reason')
        },

        /**
         * Build widget
         *
         * @private
         */
        _create: function () {
            this._setModal();
            this._bind();
        },

        /**
         * Set events
         *
         * @private
         */
        _bind: function () {
            this.element.on(this.options.eventType, $.proxy(this._showModal, this));
        },

        /**
         * Set modal window.
         *
         * @private
         */
        _setModal: function () {
            var self = this,
                popupOptions = {
                    'type': 'popup',
                    'modalClass': 'popup-tree-decline',
                    'responsive': true,
                    'innerScroll': true,
                    'title': $.mage.__(this.options.popupTitle),
                    'buttons': [{
                        class: 'action-primary cancel action-accept',
                        type: 'button',
                        text: 'Cancel',

                        /** Click action */
                        click: function () {
                            this.closeModal();
                            self.options.form.validation('clearError');
                        }
                    }, {
                        class: 'action-primary confirm action-accept',
                        type: 'button',
                        text: 'Confirm',

                        /** Click action */
                        click: function () {
                            self._sendForm();
                        }
                    }]
                },
                dataForm = {
                    url: this.options.url,
                    key: this.options.formKey,
                    message: this.options.declineMessage,
                    textarealabel: this.options.textareaLabel
                };

            this.options.form = $(mageTemplate(formTpl)({
                data: dataForm
            }));
            this.options.form = $(this.options.form[this.options.form.length - 1]);
            this.options.form.validation();
            this.options.form.find(this.options.formDeclineInputsBlock).show();
            this.options.form.find(this.options.formDeclineMessageBlock).hide();
            this.options.form.find(this.options.formDeclineMessageWarningBlock).hide();
            this.options.form.modal(popupOptions);
        },

        /**
         * Show modal window.
         *
         * @private
         */
        _showModal: function () {
            if ($(this.options.blockToShow).children().length) {
                $(this.options.blockToShow).trigger('notification');

                return;
            }
            this.options.form.modal('openModal');
        },

        /**
         * Send form to server.
         *
         * @private
         */
        _sendForm: function () {
            this.options.form.submit();
        }
    });

    return $.mage.decline;
});
