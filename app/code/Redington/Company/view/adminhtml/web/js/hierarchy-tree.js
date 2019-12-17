/**
 * Copyright © Redington. All rights reserved.
 * 
 */

/**
 * @api
 */
define([
    'jquery',
    'underscore',
    'jquery/ui',
    'mage/template',
    'text!Magento_Company/templates/tooltip.html',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'Redington_Company/js/jstree',
    'hierarchyTreePopup',
    'mage/translate',
    'mage/mage'
], function ($, _, ui, mageTemplate, nodeTpl, alert, confirm) {
    'use strict';

    $.widget('mage.hierarchyTree', {
        options: {
            selectionLimit: 0,
            adminUserRoleId: 0,
            draggable: true,
            pluginsList: ['types'],
            moveUrl: '',
            roleSelect: '[data-role="role-select"]',
            buttons: {
                expandAll: '[data-action="expand-tree"]',
                collapseAll: '[data-action="collapse-tree"]',
                addUser: '[data-action="add-user"]',
                addTeam: '[data-action="add-team"]',
                editSelected: '[data-action="edit-selected-node"]',
                deleteSelected: '[data-action="delete-selected-node"]'
            },
            statusSelect: '[data-role="status-select"]',
            popups: {
                user: '[data-role="add-customer-dialog"]',
                team: '[data-role="add-team-dialog"]'
            },
            buttonClass: [
                'action save primary',
                'action cancel secondary'
            ],
            popupInstance: {},
            teamIdField: '',
            userIdField: '',
            targetIdField: [],
            initData: [],
            initDataProcessed: [],
            wrapperClass: 'block-dashboard-company',
            treeSelector: '[data-role="hierarchy-tree"]',
            deleteData: {},
            tooltipTpl: '[data-role="tooltip"]',
            isAjax: false
        },

        /**
         * Create tree
         *
         * @private
         */
        _create: function () {
            this.nodeTextTemplate = mageTemplate(nodeTpl);
            this._initTree();
            this._bindTreeEvents();
            this._bindWidgetEvents();
            this._initPopups();
        },

        /**
         * @private
         */
        _initTree: function () {
            $.ajax({
                url: this.options.initData,
                type: 'get',
                showLoader: true,

                success: $.proxy(function (data) {
                    this._prepareTreeNodeData(data.data);
                    this.options.initDataProcessed = data.data;

                    if (this.options.draggable) {
                        this.options.pluginsList.push('dnd');
                        this.element.addClass('jstree-draggable');
                    }

                    this.element.jstree({
                        'plugins': this.options.pluginsList,
                        'core': {
                            data: this.options.initDataProcessed,

                            /**
                             * @param {*} operation
                             * @param {*} node
                             * @param {*} nodeParent
                             * @param {*} nodePosition
                             * @param {Object} more
                             * @return {Boolean}
                             */
                            'check_callback': function (operation, node, nodeParent, nodePosition, more) {
                                return !(typeof more.ref !== 'undefined' && more.ref.id === 'j1_1');
                            }
                        },
                        'themes': {
                            theme: 'default',
                            icons: true
                        },
                        'dnd': {
                            'check_while_dragging': true
                        },
                        'types': {
                            'icon-company': {
                                'icon': 'icon-company'
                            },
                            'icon-customer': {
                                'icon': 'icon-customer'
                            }
                        }
                    });
                }, this)
            });
        },

        /**
         * Reload tree
         *
         * @private
         */
        _reloadTheTree: function () {
            this.element.jstree('destroy');
            this._initTree();
            this._bindTreeEvents();
        },

        /**
         * Bind jstree events
         *
         * @private
         */
        _bindTreeEvents: function () {
            var self = this;

            this.element.on('loaded.jstree', function () {
                if (!$('.company-admin').length) {
                    $('#company-tree li a').first().addClass('company-admin');
                }

                self.element.trigger('contentUpdated');
            });

            this.element.on('after_open.jstree', function () {
                self.element.trigger('contentUpdated');
            });

            /* jscs:disable requireCamelCaseOrUpperCaseIdentifiers */
            this.element.on('move_node.jstree', function (e, data) {
                var movedNodeID = self.element.jstree(true).get_node(data.node.id).original,
                    movedToNodeID = self.element.jstree(true).get_node(data.parent).original;

                if (typeof self.element.jstree(true).get_node(data.parent).original === 'undefined') {
                    self._reloadTheTree();

                    return false;
                }

                movedNodeID = movedNodeID.attr['data-tree-id'];
                movedToNodeID = movedToNodeID.attr['data-tree-id'];

                if (!self.options.isAjax) {
                    self.options.isAjax = true;

                    if (!$('.company-admin').length) {
                        $('#company-tree li a').first().addClass('company-admin');
                    }

                    $.ajax({
                        url: self.options.moveUrl,
                        data: {
                            'structure_id': movedNodeID,
                            'target_id': movedToNodeID
                        },
                        type: 'post',
                        dataType: 'json',
                        context: $('body'),
                        showLoader: true,

                        /**
                         * @callback
                         */
                        success: $.proxy(function (res) {

                            if (res.status === 'error') {
                                self._reloadTheTree();
                            }

                            self.options.isAjax = false;
                        }, this),

                        /**
                         * @callback
                         */
                        complete: function () {
                            self.options.isAjax = false;
                            self.element.trigger('contentUpdated');
                        }
                    });
                }
            });
        },

        /**
         * Bind global widget events
         *
         * @private
         */
        _bindWidgetEvents: function () {
            $(this.options.buttons.expandAll).on('click', $.proxy(this._expandTree, this));
            $(this.options.buttons.collapseAll).on('click', $.proxy(this._collapseTree, this));
            $(this.options.buttons.addUser).on('click', $.proxy(this._addUser, this));
            $(this.options.buttons.addTeam).on('click', $.proxy(this._addTeam, this));
            $(this.options.buttons.editSelected).on('click', $.proxy(this._checkSelectedNode, this));
            $(this.options.buttons.deleteSelected).on('click', $.proxy(this._checkSelectedNode, this));
            this.element.on('reloadTheTree', $.proxy(this._reloadTheTree, this));
            this.element.on('alertPopup', $.proxy(this._openAlert, this));
        },

        /**
         * Prepare tree data
         *
         * @param {Object} node
         * @private
         */
        _prepareTreeNodeData: function (node) {
            this._prepareTreeNodeText(node);

            if (_.isArray(node.children)) {
                _.each(node.children, function (childNode) {
                    this._prepareTreeNodeData(childNode);
                }, this);
            }

            return node;
        },

        /**
         * Prepare tree node text
         *
         * @param {Object} node
         * @returns {*}
         * @private
         */
        _prepareTreeNodeText: function (node) {
            if (node.description) {
                node.text = mageTemplate(nodeTpl, this._getNodeTextTemplateData(node));
            }

            return node;
        },

        /**
         * Get node text template data
         *
         * @param {Object} node
         * @returns {Object}
         * @private
         */
        _getNodeTextTemplateData: function (node) {
            return {
                name: node.text,
                description: node.description
            };
        },

        /**
         * Init popups
         *
         * @private
         */
        _initPopups: function () {
            var self = this;

            $.each(this.options.popups, function (i, el) {
                self.options.popups[i] = $(el).hierarchyTreePopup({
                    buttons: [{
                        text: $.mage.__('Save'),
                        class: 'action save primary',
                        attr: {
                            'data-action': 'save'
                        },

                        /**
                         * Click action.
                         */
                        click: function () {
                            self.options.popups[i].trigger('sendForm');
                        }
                    }, {
                        text: $.mage.__('Cancel'),
                        class: 'action cancel secondary',

                        /**
                         * Click action.
                         */
                        click: function () {
                            this.closeModal();
                        }
                    }]
                });
                self.options.targetIdField.push(self.options.popups[i].find('[name="target_id"]'));
            });
            this.options.teamIdField = this.options.popups.team.find('[name="team_id"]');
            this.options.userIdField = this.options.popups.user.find('[name="customer_id"]');
        },

        /**
         * Callback for add user
         *
         * @param {Object} params
         * @private
         */
        _addUser: function (params) {
            var options = {
                    popup: this.options.popups.user,
                    title: $.mage.__('Add User')
                };

            $.extend(options, params);
            this._filterRoles('role');
            this._openPopup(options);
        },

        /**
         * Callback for add team
         *
         * @param {Object} params
         * @private
         */
        _addTeam: function (params) {
            var options = {
                popup: this.options.popups.team,
                title: $.mage.__('Add Team')
            };

            $.extend(options, params);
            this._openPopup(options);
        },

        /**
         * Open alert modal
         *
         * @param {Object} params
         * @param {Object} data
         * @private
         */
        _openAlert: function (params, data) {
            var options = {
                modalClass: 'popup-tree',
                responsive: true,
                innerScroll: true,
                title: 'Alert',
                content: 'Alert'
            };

            $.extend(options, data || params);
            alert(options);
        },

        /**
         * Open confirm modal
         *
         * @param {Object} params
         * @private
         */
        _openConfirm: function (params) {
            var options = {
                modalClass: 'popup-tree modal-slide',
                buttons: [{
                    text: $.mage.__('Delete'),
                    'class': 'action primary action-dismiss',

                    /**
                     * @param {jQuery.Event} event
                     */
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }, {
                    text: $.mage.__('Cancel'),
                    'class': 'action secondary action-accept',

                    /**
                     * @param {jQuery.Event} event
                     */
                    click: function (event) {
                        this.closeModal(event);
                    }
                }]
            };

            $.extend(options, params);
            confirm(options);
        },

        /**
         * Open popup
         *
         * @param {Object} options
         * @private
         */
        _openPopup: function (options) {
            this._setIdFields();
            options.popup.modal({
                focus: options.popup.selector + ' .input-text:first'
            });
            options.popup.modal('setTitle', options.title);
            options.popup.modal('openModal');
            options.popup.trigger('onShow', this.options.userIdField);
        },

        /**
         * Get tree params
         *
         * @returns {Object} tree params
         * @private
         */
        _getTreeParams: function () {
            var getSelectedID = this.element.jstree('get_selected'),
                getSelected = this.element.jstree(true).get_node(getSelectedID),
                nodeAttributes = getSelected ? this.element.jstree(true).get_node(getSelectedID).original.attr : false,
                isParent = getSelected ? getSelected.parents.length === 1 : false;

            /* jscs:enable requireCamelCaseOrUpperCaseIdentifiers */
            return {
                selectedNodeId: getSelectedID,
                selectElement: $(getSelectedID),
                selectedNode: getSelected,
                attrs: nodeAttributes,
                id: nodeAttributes['data-entity-id'],
                targetId: nodeAttributes['data-tree-id'],
                type: parseFloat(nodeAttributes['data-entity-type']),
                isParent: isParent
            };
        },

        /**
         * Set id fields for popup
         *
         * @private
         */
        _setIdFields: function () {
            var params = this._getTreeParams();

            this.options.teamIdField.val(params.id);
            this.options.userIdField.val(params.id);
            $.each(this.options.targetIdField, function (i, el) {
                el.val(params.targetId);
            });
        },

        /**
         * Expand tree
         *
         * @private
         */
        _expandTree: function () {
            this.element.jstree('open_all');
        },

        /**
         * Collapse tree
         *
         * @private
         */
        _collapseTree: function () {
            var $element = this.element,
                $root = $element.jstree(true)['get_json']()[0];

            $.each($root.children, function (id, item) {
                $element.jstree('close_all', item);
            });
        },

        /**
         * Edit selected node
         *
         * @param {Object} e
         * @private
         */
        _checkSelectedNode: function (e) {
            var treeParams = this._getTreeParams();

            e.preventDefault();

            if (!treeParams.selectedNode) {
                this._openAlert({
                    title: 'Please select user or team',
                    content: 'Please select a user or team first.'
                });

                return false;
            }

            treeParams.isParent ? this._editSelf(e, treeParams) : this._defineSelectedType(e, treeParams);
            this._setIdFields();
        },

        /**
         * Edit selected node
         *
         * @param {Object} e
         * @param {Object} params
         * @private
         */
        _editSelf: function (e, params) {

            if (!this._isEdit(e)) {
                this._openAlert({
                    title: $.mage.__('Cannot delete the user'),
                    content: $.mage.__('This user cannot be deleted because he/she is a company admin.')
                });

                return false;
            }

            this._addUser({
                title: $.mage.__('Edit User')
            });
            this._populateForm(params, this.options.popups.user);
            this.options.popups.user.find(this.options.statusSelect).attr('disabled', params.isParent);
        },

        /**
         * Edit selected node
         *
         * @param {Object} e
         * @param {Object} params
         * @private
         */
        _defineSelectedType: function (e, params) {
            params.type ? this._defineTeamEvent(e, params) : this._defineUserEvent(e, params);
        },

        /**
         * Edit selected node
         *
         * @param {Object} e
         * @returns {Boolean} edit popup or not
         * @private
         */
        _isEdit: function (e) {
            return $(e.target).data('action') === 'edit-selected-node';
        },

        /**
         * Edit selected node
         *
         * @param {Object} e
         * @param {Object} params
         * @private
         */
        _defineUserEvent: function (e, params) {
            this._isEdit(e) ? this._editUser(params) : this._deleteUser(e, params);
        },

        /**
         * Edit selected node
         *
         * @param {Object} e
         * @param {Object} params
         * @private
         */
        _defineTeamEvent: function (e, params) {
            this._isEdit(e) ? this._editTeam(params) : this._deleteTeam(e, params);
        },

        /**
         * Edit selected node
         *
         * @param {Object} params
         * @private
         */
        _editUser: function (params) {
            this._addUser({
                title: $.mage.__('Edit User')
            });
            this.options.popups.user.find(this.options.statusSelect).attr('disabled', params.isParent);
            this._populateForm(params, this.options.popups.user);
        },

        /**
         * Edit selected node
         *
         * @param {Object} e
         * @param {Object} params
         * @private
         */
        _deleteUser: function (e, params) {
            var self = this,
                url = $(e.target).data('delete-customer-url'),
                data = {
                    send: {
                        'customer_id': params.id
                    },
                    element: params.selectElement,
                    type: 'customer'
                };

            if (params.selectedNode.children.length) {
                this._openAlert({
                    title: $.mage.__('Cannot Delete User'),
                    content: $.mage.__('This user cannot be deleted because child users are assigned to it. ' +
                        'You must re-assign the child users before you can delete this user.')
                });

                return false;
            }

            this._openConfirm({
                title: $.mage.__('Delete this user?'),
                content: $.mage.__('This action cannot be undone. Are you sure you want to delete this user?'),
                actions: {
                    /**
                     * Confirm action.
                     */
                    confirm: function () {
                        self._deleteSelectedNode(url, data);
                    }
                }
            });
        },

        /**
         * Edit selected node
         *
         * @param {Object} params
         * @private
         */
        _editTeam: function (params) {
            this._addTeam({
                title: $.mage.__('Edit Team')
            });
            this._populateForm(params, this.options.popups.team);
        },

        /**
         * Edit selected node
         *
         * @param {Object} e
         * @param {Object} params
         * @private
         */
        _deleteTeam: function (e, params) {
            var self = this,
                url = $(e.target).data('delete-team-url'),
                data = {
                    send: {
                        'team_id': params.id
                    },
                    element: params.selectElement,
                    type: 'team'
                };

            if (params.selectedNode.children.length) {
                this._openAlert({
                    title: $.mage.__('Cannot Delete This Team'),
                    content: $.mage.__('This team has child users or teams aligned to it and cannot be deleted. ' +
                        'Please re-align the child users or teams first.')
                });

                return false;
            }

            this._openConfirm({
                title: $.mage.__('Delete this team?'),
                content: $.mage.__('This action cannot be undone. Are you sure you want to delete this team?'),
                actions: {
                    /**
                     * Confirm action.
                     */
                    confirm: function () {
                        self._deleteSelectedNode(url, data);
                    }
                }
            });
        },

        /**
         * Delete selected node
         *
         * @param {String} url
         * @param {Object} data
         * @private
         */
        _deleteSelectedNode: function (url, data) {
            var self = this;

            if (!this.options.isAjax) {
                this.options.isAjax = true;

                $.ajax({
                    url: url,
                    data: data.send,
                    type: 'post',
                    dataType: 'json',
                    showLoader: true,

                    /**
                     * @param {Object} res
                     */
                    success: function (res) {

                        if (res.status === 'error') {
                            self._openAlert({
                                title: 'Cannot Delete ' + data.type,
                                content: res.message
                            });
                        } else {
                            self.element.jstree('delete_node', data.element);
                            self._reloadTheTree();
                        }
                    },

                    /**
                     * Complete callback.
                     */
                    complete: function () {
                        self.options.isAjax = false;
                    }
                });
            }
        },

        /**
         * Set data to popup form fields
         *
         * @param {Object} popup
         * @param {String} name
         * @param {String} value
         * @private
         */
        _setPopupFields: function (popup, name, value) {
            if (name == 'role') { //eslint-disable-line eqeqeq
                this._filterRoles(name, value);
            }
            popup.find('form [name="' + name + '"]').val(value);
        },

        /**
         * Fill roles input field.
         *
         * @param {String} name
         * @param {String} value
         * @private
         */
        _filterRoles: function (name, value) {
            var selectRoles =  this.options.popups.user.find(this.options.roleSelect),
                optionsRole = selectRoles.find('option'),
                adminRole = selectRoles.find('[value=' + this.options.adminUserRoleId + ']'),
                condition = value === this.options.adminUserRoleId;

            selectRoles.prop('disabled', condition);
            optionsRole.toggle(!condition);
            adminRole.toggle(condition);
            adminRole.attr('disabled', condition ? 'disabled' : '');

            this._setSelectedRole(name, value, selectRoles);
        },

        /**
         * Set selected role.
         *
         * @param {String} name
         * @param {String} value
         * @param {Object} select
         * @private
         */
        _setSelectedRole: function (name, value, select) {
            var oldSelectedOptions = select.find(':selected'),
                newSelectedOption = select.find('[value="' + value + '"]');

            oldSelectedOptions.removeAttr('selected');
            newSelectedOption.attr('selected', 'selected');
        },

        /**
         * Populate form
         *
         * @param {Object} params
         * @param {Object} popup
         * @private
         */
        _populateForm: function (params, popup) {
            var self = this,
                nodeType = params.type === 0 ? 'customer' : 'team',
                url = $('#edit-selected').data('edit-' + nodeType + '-url') + '?' + nodeType + '_id=' + params.id;

            if (!this.options.isAjax) {
                this.options.isAjax = true;

                popup.addClass('unpopulated');
                popup.find('input').val('').attr('readonly', true);

                $.ajax({
                    url: url,
                    type: 'get',
                    showLoader: true,

                    /**
                     * @callback
                     */
                    success: $.proxy(function (data) {
                        var that = this;

                        popup.find('input').attr('readonly', false);

                        if (data.status === 'ok') {
                            $.each(data.data, function (idx, item) {
                                if (idx === 'custom_attributes') {
                                    $.each(item, function (name, itemData) {
                                        that._setPopupFields(popup, itemData['attribute_code'], itemData.value);
                                    });
                                }
                                that._setPopupFields(popup, idx, item);
                            });
                            popup.removeClass('unpopulated');
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

    return $.mage.hierarchyTree;
});
