/**
 * Copyright © Redington. All rights reserved.
 * 
 */

/**
 * @api
 */
define([
    'jquery',
    'jquery/ui',
    'Redington_Company/js/jstree',
    'mage/translate',
    'mage/mage'
], function ($) {
    'use strict';

    $.widget('mage.roleTree', {
        options: {
            data: {},
            selectionLimit: 0,
            draggable: false,
            pluginsList: ['checkbox'],
            checkbox: {
                'three_state': false,
                cascade: ''
            },
            moveUrl: '',
            buttons: {
                expandAll: '[data-action="expand-tree"]',
                collapseAll: '[data-action="collapse-tree"]'
            },
            dataElement: '[name="role_permissions"]',
            form: '#role-edit-form'
        },

        /**
         * Create tree
         * @private
         */
        _create: function () {
            this._initTree();
            this._bind();
        },

        /** @inheritdoc */
        _initTree: function () {
            this.element.jstree({
                'plugins': this.options.pluginsList,
                'core': {
                    data: this.options.data
                },
                'checkbox': this.options.checkbox,
                'themes': {
                    theme: 'default',
                    icons: false
                }
            });
        },

        /**
         * Bind events
         * @private
         */
        _bind: function () {
            $(this.options.buttons.expandAll).on('click', $.proxy(this._expandTree, this));
            $(this.options.buttons.collapseAll).on('click', $.proxy(this._collapseTree, this));
            $(this.options.form).on('submit', $.proxy(this._populateData, this));
            $(this.element).on('changed.jstree', $.proxy(this._cascadeTree, this));
        },

        /**
         * Cascade tree
         *
         * @private
         */
        _cascadeTree: function (e, data) {
            var childrenNodes, node;

            if (data && data.action && data.node) {
                childrenNodes = $.makeArray(this.element.jstree('get_children_dom', data.node));

                switch (data.action) {
                    case 'select_node':
                        node = data.node;

                        do { //eslint-disable-line max-depth
                            node = this.element.jstree('get_parent', node);
                            this.element.jstree('select_node', node, true);
                        } while (node);
                        this.element.jstree('select_node', childrenNodes);
                        break;

                    case 'deselect_node':
                        this.element.jstree('deselect_node', childrenNodes);
                        break;
                    default:
                }
            }
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
            this.element.jstree('close_all');
        },

        /**
         * Populate data
         *
         * @private
         */
        _populateData: function () {
            $(this.options.dataElement).val(this.element.jstree('get_selected'));
        }
    });

    return $.mage.roleTree;
});
