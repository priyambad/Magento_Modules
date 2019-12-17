/**
 * Copyright © Redington. All rights reserved.
 * 
 */

define([
    'jquery',
    'mage/template',
    'text!Magento_Company/templates/message.html',
    'jquery/ui',
    'jquery/validate',
    'mage/validation',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, mageTpl, errorTpl) {
    'use strict';

    $.widget('mage.hierarchyTreePopup', {
        options: {
            popupTitle: $.mage.__('Popup title'),
            modalClass: '',
            url: '',
            treeSelector: '[data-role="hierarchy-tree"]',
            saveButton: '[data-action="save"]',
            validateFields: {
                email: '[data-role="email"]',
                notification: '[data-role="notification-message"]'
            },
            additionalFields: {
                create: '[data-role="create-additional-fields"]',
                edit: '[data-role="edit-additional-fields"]'
            },
            popupForm: null,
            buttons: [],
            isAjax: false
        },

        /**
         * Create widget
         *
         * @private
         */
        _create: function () {
            this.messageBlockTpl = mageTpl(errorTpl);
            this._setElements();
            this._setModal();
            this._bind();
        },

        /**
         * Set modal dialog
         *
         * @private
         */
        _setModal: function () {
            var self = this,
                options = {
                type: 'popup',
                modalClass: 'popup-tree',
                responsive: true,
                innerScroll: true,
                title: $.mage.__(this.options.popupTitle),
                buttons: this.options.buttons,

                /**
                 * Clear validation and notification messages.
                 */
                closed: function () {
                    $(this).find('form').validation('clearError');
                    $(self.options.saveButton).prop({
                        disabled: false
                    });
                    self._clearNotificationMessage();
                }
            };

            this.element.modal(options);
            this.options.modalClass = options.modalClass;
        },

        /**
         * Set DOM elements
         *
         * @private
         */
        _setElements: function () {
            this.emailBlock = this.element.find(this.options.validateFields.email);
            this.form = this.element.find('form');
        },

        /**
         * Bind listeners on elements
         *
         * @private
         */
        _bind: function () {
            var handlers = {
                sendForm: '_validationForm',
                onShow: '_onShow'
            };

            this._on(handlers);

            this.form.on('submit', this._triggerSubmit.bind(this));
            this.emailBlock.on('change', this._checkEmail.bind(this));
        },

        /**
         * Trigger submit event for form
         *
         * @private
         */
        _triggerSubmit: function (e) {
            e.preventDefault();
            this.element.trigger('sendForm');
        },

        /**
         * Open modal dialog
         *
         * @public
         */
        _onShow: function (e, customerIdField) {
            var popupForm = this.element.find('form');

            this._clearFields(popupForm);
            this._showAdditionalFields($(customerIdField).val() !== '');
        },

        /**
         * Toggle show addition fields
         *
         * @param {Boolean} condition
         * @private
         */
        _showAdditionalFields: function (condition) {
            $(this.options.additionalFields.create).toggleClass('_hidden', condition)
                .find('[name]').prop('disabled', condition);
            $(this.options.additionalFields.edit).toggleClass('_hidden', !condition)
                .find('[name]').prop('disabled', !condition);
        },

        /**
         * Clear value fields for form
         *
         * @private
         */
        _clearFields: function (form) {
            var selectFields = form.find('select');

            if (selectFields.length) {
                selectFields.find('option:first-child').attr('selected', 'selected');
            }
            form.find('input:not([name="target_id"])').val('');
            form.find('textarea').val('');

        },

        /**
         * Clear notification message for form
         *
         * @private
         */
        _clearNotificationMessage: function () {
            $(this.options.validateFields.notification).remove();
        },

        /**
         * Validation form
         *
         * @private
         */
        _validationForm: function () {
            if (this.form.valid()) {
                this.ajaxSubmit(this.form);
            }
        },

        /**
         * Close modal dialog
         *
         * @public
         */
        closeModal: function () {
            this.element.modal('closeModal');
        },

        /**
         * Set validation on field and check.
         *
         * @param {Object} el - (field)
         * @param {Object} text - (text error message)
         *
         * @private
         */
        _setValidation: function (el, text) {
            var element = $(el),
                callValid = true;

            $.validator.addMethod('errorField', function () {
                return !callValid;
            }, $.validator.format(text));

            element.rules('add', {
                errorField: true
            });

            element.valid();
            $(this.options.saveButton).prop({
                disabled: callValid
            });
            callValid = false;
        },

        /**
         * Render notification message.
         *
         * @param {Object} el - (an element which is inserted after the notification)
         * @param {Object} text - (text message)
         *
         * @private
         */
        _renderMessage: function (el, text) {
            if (this.messageBlock) {
                this.messageBlock.remove();
            }
            this.messageBlock = $(this.messageBlockTpl({
                data: text
            }));

            $(el).after(this.messageBlock[this.messageBlock.length - 1]);
        },

        /**
         * Check email on server.
         *
         * @private
         */
        _checkEmail: function () {
            var data = $(this.options.validateFields.email).val(),
                self = this;

            this._clearNotificationMessage();
            $(this.options.saveButton).prop({
                disabled: false
            });

            if (!this.options.isAjax && $(this.options.validateFields.email).valid()) {
                this.options.isAjax = true;
                $.ajax({
                    url: $(this.options.validateFields.email).data('urlValidate'),
                    data: {
                        email: data
                    },
                    type: 'post',
                    dataType: 'json',
                    showLoader: true,
                    success: $.proxy(function (res) {
                        if (res.status === 'error') {
                            this._setValidation(this.options.validateFields.email, res.message);
                        } else if (res.message && res.status === 'ok') {
                            this._renderMessage(this.options.validateFields.email, res.message);
                            $.each(res.data, function (idx, item) {
                                self.form.find('[name="' + idx + '"]').val(item);
                            });
                        }
                        this.options.isAjax = false;
                    }, this),
                    error: $.proxy(function () {
                        this.options.isAjax = false;
                    }, this)
                });
            }
        },

        /**
         * Check type error
         *
         * @private
         */
        _checkError: function (res) {
            if (res.payload && res.payload.field && res.payload.field === 'email') {
                this._setValidation(this.options.validateFields.email, res.message);
            } else {
                $(this.element).modal('closeModal');
                $(this.options.treeSelector).trigger('alertPopup', {
                    content: res.message
                });
            }
        },

        /**
         * Create new node by ajax
         *
         * @private
         */
        ajaxSubmit: function (form) {
            if (!this.options.isAjax) {
                this.options.isAjax = true;
                $.ajax({
                    url: form.attr('action'),
                    data: form.serialize(),
                    type: 'post',
                    dataType: 'json',
                    showLoader: true,
                    success: $.proxy(function (res) {

                        if (res.status === 'error') {
                            this._checkError(res);
                        } else {
                            this.element.modal('closeModal');
                            $(this.options.treeSelector).trigger('reloadTheTree');
                        }
                        this.options.isAjax = false;
                    }, this),
                    error: $.proxy(function () {
                        location.reload();
                        this.options.isAjax = false;
                    }, this)
                });
            }
        }
    });

    return $.mage.hierarchyTreePopup;
});
