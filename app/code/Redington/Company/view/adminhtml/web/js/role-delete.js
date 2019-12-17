/**
 * Copyright © Redington. All rights reserved.
 * 
 */
define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, confirm, alert) {
    'use strict';

    $.widget('mage.roleDelete', {

        options: {
            deletable: false,
            deleteUrl: ''
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
                'deleteRole': '_deleteRole'
            });
        },

        /**
         * Set popup for delete
         *
         * @private
         */
        _deleteRole: function (e) {
            var self = this,
                options;

            e.preventDefault();

            if (this.options.deletable) {
                options = {
                    modalClass: 'modal-slide popup-tree',
                    buttons: [{
                        text: $.mage.__('Delete'),
                        'class': 'action primary delete',

                        /** @inheritdoc */
                        click: function (event) {
                            this.closeModal(event);
                            location.href = self.options.deleteUrl;
                        }
                    }, {
                        text: $.mage.__('Cancel'),
                        'class': 'action secondary cancel',

                        /** @inheritdoc */
                        click: function (event) {
                            this.closeModal(event);
                        }
                    }],
                    title: $.mage.__('Delete This Role?'),
                    content: $.mage.__('This action cannot be undone. Are you sure you want to delete this role?')
                };
                confirm(options);
            } else {
                alert({
                    modalClass: 'restriction-modal-quote',
                    responsive: true,
                    innerScroll: true,
                    title: $.mage.__('Cannot Delete Role'),
                    content: $.mage.__('This role cannot be deleted because users are assigned to it. Reassign the users to another role to continue.') // eslint-disable-line max-len
                });
            }
        }
    });

    return $.mage.roleDelete;
});
