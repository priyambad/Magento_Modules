/**
 * Copyright © Redington. All rights reserved.
 * 
 */
define([
    'jquery',
    'uiRegistry',
    'underscore',
    'Magento_Ui/js/modal/modal',
    'hierarchyTreePopup',
    'mage/translate'
], function ($, registry, _) {
    'use strict';

    $.widget('mage.userEdit', {

        options: {
            popup: '[data-role="add-customer-dialog"]',
            statusSelect: '[data-role="status-select"]',
            roleSelect: '[data-role="role-select"]',
            isAjax: false,
            gridProvider: '',
            adminUserRoleId: 0,
            getUserUrl: '',
            additionalFields: {
                create: '[data-role="create-additional-fields"]',
                edit: '[data-role="edit-additional-fields"]'
            }
        },

        /**
         * Create widget
         *
         * @private
         */
        _create: function () {
            this._setModal();
            this._bind();
        },

        /**
         * Bind listeners on elements
         *
         * @private
         */
        _bind: function () {
            this._on({
                'editUser': 'editUser',
                'click': 'editUser',
                'reloadTheTree': '_reloadGrid'
            });
        },

        /**
         * Callback for edit event
         *
         * @param {Object} e
         * @public
         */
        editUser: function (e) {
            var title = this.options.id ? $.mage.__('Edit User') : $.mage.__('Add New User');

            if (e) {
                e.preventDefault();
            }
            this.options.popup.modal('setTitle', title);
            this.options.popup.modal('openModal');
            this._populateForm();
            this._setIdFields();

            if (!this.options.id) {
                this._filterRoles('role');
            }
        },

        /**
         * Toggle show addition fields
         *
         * @param {Boolean} isRegisterForm
         * @private
         */
        showAdditionalFields: function (isRegisterForm) {
            $(this.options.additionalFields.create).toggleClass('_hidden', isRegisterForm)
                .find('[name]').prop('disabled', isRegisterForm);
            $(this.options.additionalFields.edit).toggleClass('_hidden', !isRegisterForm)
                .find('[name]').prop('disabled', !isRegisterForm);
        },

        /**
         * Callback for reload event
         *
         * @private
         */
        _reloadGrid: function () {
            this._getGridProvider().reload({
                refresh: true
            });
        },

        /**
         * Get provider
         *
         * @private
         */
        _getGridProvider: function () {
            if (!this.gridProvider) {
                this.gridProvider = registry.get(this.options.gridProvider);
            }

            return this.gridProvider;
        },

        /**
         * Set id customer to field in form
         *
         * @private
         */
        _setIdFields: function () {
            this.options.popup.find('[name="customer_id"]').val(this.options.id);
        },

        /**
         * Set modal for edit customer
         *
         * @private
         */
        _setModal: function () {
            var self = this;

            this.options.popup = $(this.options.popup).hierarchyTreePopup({
                popupTitle: self.options.popupTitle,
                treeSelector: self.element,
                buttons: [{
                    text: $.mage.__('Save'),
                    'class': 'action save primary',

                    /** @inheritdoc */
                    click: function () {
                        self.options.popup.trigger('sendForm');
                    }
                }, {
                    text: $.mage.__('Cancel'),
                    'class': 'action cancel secondary',

                    /** @inheritdoc */
                    click: function () {
                        this.closeModal();
                    }
                }]
            });
        },

        /**
         * Set data to popup form fields
         *
         * @param {String} name
         * @param {String} value
         * @private
         */
        _setPopupFields: function (name, value) {
            var self = this;

            if (name === 'role') {
                self._filterRoles(name, value);
            }
            this.options.popup.find('form [name="' + name + '"]').val(value);
        },

        /**
         * Fill roles input field.
         *
         * @param {String} name
         * @param {String} value
         * @private
         */
        _filterRoles: function (name, value) {
            var selectRoles = this.options.popup.find(this.options.roleSelect),
                statusSelect = this.options.popup.find(this.options.statusSelect),
                optionsRole = selectRoles.find('option'),
                adminRole = selectRoles.find('[value=' + this.options.adminUserRoleId + ']'),
                condition = value === this.options.adminUserRoleId;

            selectRoles.prop('disabled', condition);
            statusSelect.prop('disabled', condition);
            optionsRole.toggle(!condition);
            adminRole.toggle(condition);
            adminRole.attr('disabled', condition ? 'disabled' : '');

            if (_.isUndefined(value)) {
                optionsRole.first().attr('selected', 'selected');
            }
        },

        /**
         * Populate form
         *
         * @private
         */
        _populateForm: function () {
            var self = this;

            this.showAdditionalFields(!this.options.id);
            this.options.popup.find('input').val('');

            if (!this.options.isAjax && this.options.id) {
                this.options.isAjax = true;

                this.options.popup.addClass('unpopulated');
                this.options.popup.find('input').attr('disabled', true);

                $.ajax({
                    url: self.options.getUserUrl,
                    type: 'get',
                    showLoader: true,

                    /**
                     * @callback
                     */
                    success: $.proxy(function (data) {
                        var that = this;

                        this.options.popup.find('input').attr('disabled', false);

                        if (data.status === 'ok') {
                            $.each(data.data, function (idx, item) {
                                if (idx === 'custom_attributes') {
                                    $.each(item, function (name, itemData) {
                                        that._setPopupFields(itemData['attribute_code'], itemData.value);
                                    });
                                }
                                that._setPopupFields(idx, item);
                            });
                            this.options.popup.removeClass('unpopulated');
                        }
                    }, this),

                    /**
                     * @callback
                     */
                    complete: function () {
                        self.options.isAjax = false;
                    }
                });
            }
        }
    });

    return $.mage.userEdit;
});
