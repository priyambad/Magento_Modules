/**
 * Copyright © Redington. All rights reserved.
 * 
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'uiRegistry',
    'mage/translate'
], function ($, confirm, alert, registry) {
    'use strict';

    $.widget('mage.userDelete', {
        options: {
            isAjax: false,
            id: '',
            deleteUrl: '',
            setInactiveUrl: '',
            gridProvider: '',
            inactiveClass: ''
        },

        /**
         * Create widget
         *
         * @private
         */
        _create: function () {
            this._bind();
        },

        /**
         * Bind listeners on elements
         *
         * @private
         */
        _bind: function () {
            this._on({
                'deleteUser': '_deleteUser'
            });
        },

        /**
         * Ajax for delete customer
         *
         * @private
         */
        _sendAjax: function (url) {
            var self = this,
                data = {
                    'customer_id': this.options.id
                };

            if (!this.options.isAjax) {
                this.options.isAjax = true;

                $.ajax({
                    url: url,
                    data: data,
                    type: 'post',
                    dataType: 'json',
                    showLoader: true,

                    /** @inheritdoc */
                    success: function (res) {

                        if (res.status === 'error') {
                            alert({
                                modalClass: 'restriction-modal-quote',
                                responsive: true,
                                innerScroll: true,
                                title: $.mage.__('Cannot Delete Customer'),
                                content: res.message
                            });
                        } else {
                            registry.get(self.options.gridProvider).reload({
                                refresh: true
                            });
                        }
                    },

                    /** @inheritdoc */
                    complete: function () {
                        self.options.isAjax = false;
                    }
                });
            }
        },

        /**
         * Set popup for delete
         *
         * @private
         */
        _deleteUser: function (e) {
            var self = this,
                options;

            e.preventDefault();
            options = {
                modalClass: 'modal-slide popup-tree',
                buttons: [{
                    text: $.mage.__('Delete'),
                    'class': 'action primary delete',

                    /** @inheritdoc */
                    click: function (event) {
                        self._sendAjax(self.options.deleteUrl);
                        this.closeModal(event);
                    }
                }, {
                    text: $.mage.__('Set Inactive'),
                    'class': 'action ' + this.options.inactiveClass,

                    /** @inheritdoc */
                    click: function (event) {
                        self._sendAjax(self.options.setInactiveUrl);
                        this.closeModal(event);
                    }
                }, {
                    text: $.mage.__('Cancel'),
                    'class': 'action secondary cancel',

                    /** @inheritdoc */
                    click: function (event) {
                        this.closeModal(event);
                    }
                }],
                title: $.mage.__('Delete this user?'),
                content: $.mage.__('Select Delete to permanently delete the user account and content. User\'s orders and quotes are still visible for the merchant. Select Set Inactive to temporarily lock the user. The user’s content is still available to parent users.') //eslint-disable-line max-len
            };

            confirm(options);
        }
    });

    return $.mage.userDelete;
});
