/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'mage/template',
    'text!Redington_Quotation/template/price.html',
    'text!Redington_Quotation/template/quote/table-row.html',
    'text!Magento_NegotiableQuote/template/quote/messages.html',
    'text!Magento_NegotiableQuote/template/quote/files.html',
    'text!Magento_NegotiableQuote/template/error-no-items.html',
    'Magento_NegotiableQuote/js/quote/actions/notification-modal',
    'Magento_NegotiableQuote/quote/actions/validate-field',
    'Magento_NegotiableQuote/quote/actions/delete-item',
    'mage/translate'
], function ($, _, mageTemplate, priceTpl, rowTpl, messageTpl, filesTpl, noItemsErrorTpl) {
    'use strict';
	
	window.proposedPrice =0;
    $.widget('mage.updateQuote', {
        options: {
            isAjax: false,
            reload: false,
            disabled: false,
            highlightedClass: '_highlighted',
            displayMessageChanges: false,
            updateUrl: 'updateQtyUrl',
            updateOnOpenUrl: '',
            updateErrorsUrl: 'updateErrorsUrl',
            needUpdate: '',
            negotiableQuoteUpdateFlag: 'negotiable_quote_update_flag',
            errorText: {
                text: '',
                tableError: true
            },
            url: '',
            totals: '[data-role="data-table"]',
            qty: '[data-role="qty-amount"]',
            qtyOld: '[data-role="qty-amount-old"]',
            sku: '[data-role="sku-name"]',
            config: '[data-role="config-options"]',
            quoteId: '[name="quote_id"]',
            totalInput: '[name*="quote[negotiated_price_value]"]',
            totalType: '[name="quote[negotiated_price_type]"]',
            totalValue: '[name^="quote[negotiated_price_value]"]',
            totalShipping: '[data-role="shipping-price"]',
            productItem: '[data-role="items-quoted-body"]',
            draftTable: '.negotiated_price_type .data-table',
            messagesSelector: '#quote-changed-message',
            totalsTableSelector: '.quote-subtotal-table',
            shippingWrap: '[data-role="quote-shipping-method-wrap"]',
            shippingMethodSelector: '[name="quote[shipping_method]"]:checked',
            addQuoteForm: '[data-role="add-quote"]',
            quoteRow: '[data-role="wrap"]',
            productSku: '[data-role="product-sku"]',
			oldproposedPrice: '[data-role="oldproposedprice"]',
			proposed: '[data-role="proposed"]',
            productQty: '[data-role="product-qty"]',
            itemsQuotedTable: '[data-role="items-quoted-table"]',
            itemsQuotedBody: '[data-role="items-quoted-body"]',
            itemQuoted: '[data-role="item-quoted"]',
            messagesWrap: '[data-role="message-block"]',
            messages: '[data-role="info-messages"], #messages',
            wrapFiles: '[data-role="history-added-files"]',
            delFile: '[data-role="delete-button"]',
            attachFiles: '[data-role="send-files"]',
            attachedFile: '[data-role="attached-item"]',
            wrapFile: '[data-role="wrap-file"]',
            updateBtn: '[data-role="update-quote"]',
            errorSkuBlock: '[data-role="items-errors"]',
            configureBtn: '[data-role="configure-sku"]',
            updatePriceBtn: '[data-role="price-quote"]',
            modalTitle: $.mage.__('Update Proposed Prices'),
            modalText: $.mage.__('Do you want to update the proposed prices.'), //eslint-disable-line max-len
            labelText: $.mage.__('Delete'),
            focusEl: '',
            dataSend: {},
            defaultAjax: {
                processData: true,
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            translate: {
                del: $.mage.__('Delete Action'),
                sku: $.mage.__('SKU'),
                configure: $.mage.__('Configure')
            },
            noItemsText: $.mage.__('No ordered items'),
            noItemsBlockSelector: '#negotiable-quote-error-no-items',
            actionsBlockSelector: '.quote-actions',
            skuConfig: {},
            statusSelector: '#quote_status',
            currencyLabelSelector: 'tr.currency-rate th',
            currencyRateSelector: '#quote_rate'
        },

        /**
         * Build widget.
         *
         * @private
         */
        _create: function () {
            this.priceBlockTmpl = mageTemplate(priceTpl);
            this.tableRowBlockTmpl = mageTemplate(rowTpl);
            this.messageBlockTmpl = mageTemplate(messageTpl);
            this.filesTemplate = mageTemplate(filesTpl);
            this.noItemsErrorBlockTmpl = mageTemplate(noItemsErrorTpl);
            this._setModal();
            this._bind();
            this._setDefaultData();
            this._processEmptyGrid();

            if (this.options.needUpdate) {
                this._updateOnOpen(this.options.updateOnOpenUrl);
            }
        },

        /**
         * Bind events
         *
         * @private
         */
        _bind: function () {
            this._on(this.element, {
                'click': this._updateTotal,
                'addbysku': this._addBySku,
                'remoteQuery': this._setUrl,
                'blockSend': this.blockSend,
                'updateTotal': this._updateTotal,
                'processEmptyGrid': this._processEmptyGrid
            });
            $(this.options.updatePriceBtn).on('click', this._openModal.bind(this));
            $(this.options.totalInput).on('change', this._updateItems.bind(this));
            $(this.options.shippingWrap).on('updateShipping', this._updateShipping.bind(this));
            $(this.options.shippingWrap).on('focus', this.options.totalShipping, this._checkAjax.bind(this));
            $(this.options.totalInput).on('focus', this._checkAjax.bind(this));
            $(this.options.itemsQuotedTable).on('click', this.options.configureBtn, this._configureSku.bind(this))
                .on('change', this.options.config, this._enableBtn.bind(this));
        },

        /**
         * Count items in the quote items grid.
         *
         * @private
         * @returns integer
         */
        _getItemsInGrid: function () {
            var self = this;

            return $(self.options.itemQuoted).size();
        },

        /**
         * Process empty quote items grid.
         *
         * @private
         */
        _processEmptyGrid: function () {
            var self = this,
                errorBlock;

            if (self._getItemsInGrid() == 0) { //eslint-disable-line eqeqeq
                errorBlock = $(this.noItemsErrorBlockTmpl({
                    data: {
                        message: self.options.noItemsText
                    }
                }));
                $(self.options.actionsBlockSelector).hide();
                $(self.options.itemsQuotedTable).append(errorBlock);
            } else {
                $(self.options.actionsBlockSelector).show();
                $(self.options.noItemsBlockSelector).remove();
            }
        },

        /**
         * Update quote on open action.
         *
         * @private
         */
        _updateOnOpen: function (url) {
            this.options.displayMessageChanges = $(this.options.totalsTableSelector).hasClass('_highlighted');
            this.options.url = url;
            this.options.reload = false;
            this._sendAjax(
                {
                    'quote_id': $(this.options.quoteId).val()
                },
                false,
                url
            );
        },

        /**
         * Set default data for ajax send.
         *
         * @private
         */
        _setDefaultData: function () {
            var self = this;
			
            this.options.url = this.element.data(this.options.updateUrl);
            this.options.dataSend = {
                'quote_id': $(this.options.quoteId).val(),
                'quote': {
                    'items': []
                }
            };
            $(this.options.productItem).each(function () {
                self.options.dataSend.quote.items.push({
                    'id': $(this).data('productId') || 0,
                    'qty': $(this).find(self.options.qty).val(),
                    'sku': $(this).find(self.options.sku).val(),
                    'productSku': $(this).find(self.options.productSku).val(),
                    'config': $(this).find(self.options.config).val(),
					'proposed': $("input[name='item["+$(this).data('productId')+"][proposedprice]']").val()
                });
            });
        },

        /**
         * Add price in table cell.
         *
         * @param {Object} data
         * @param {Object} el
         *
         * @private
         */
        addPrice: function (data, el) {
            var errorBlock = $(this.priceBlockTmpl({
                data: data
            }));

            el.html('');
            el.append(errorBlock);
        },

        /**
         * Render table row.
         *
         * @param {Object} data
         *
         * @private
         */
        _addRow: function (data) {
            var self = this;

            $(this.options.itemsQuotedBody).remove();
            this.options.dataSend.quote.items = [];
            this.options.skuConfig = {};
            $.each(data.items, function (i) {
                var itemsQuote = $(self.tableRowBlockTmpl({
                    data: {
                        item: data.items[i],
                        disabledClass: self.options.disabled,
                        disabled: self.options.disabled ? 'disabled' : '',
                        translate: self.options.translate
                    }
                }));

                self.options.skuConfig['item[' + data.items[i].id + '][config]'] = data.items[i].config;
                $(self.options.itemsQuotedTable).append(_.last(itemsQuote));

                self.options.dataSend.quote.items.push({
                    'id': data.items[i].id || 0,
                    'qty': data.items[i].qty,
                    'sku': data.items[i].sku,
                    'productSku': data.items[i].productSku,
                    'config': data.items[i].config,
					'proposed': $("input[name='item["+data.items[i].id+"][proposedprice]']").val()
					
                });
            });

            $(this.options.itemsQuotedTable)
                .find(this.options.qty)
                .validateField();

            $(this.options.itemsQuotedTable)
                .find(this.options.itemQuoted)
                .deleteItem();

            $(this.options.itemsQuotedTable)
                .find(this.options.config)
                .on('change', this._enableBtn.bind(self));

            this._processEmptyGrid();
        },

        /**
         * Enable update button
         *
         * @param {Object} e
         *
         * @private
         */
        _enableBtn: function (e) {
            var condition = $(e.target).val().indexOf(this.options.skuConfig[$(e.target).attr('name')]);

            if (!this.element.hasClass('enabled')) {
                $(this.options.updateBtn).prop('disabled', condition > 0);
            }
        },

        /**
         * Render message.
         *
         * @param {Object} data
         *
         * @private
         */
        _renderMessage: function (data) {
            var self = this;

            $(this.options.messages).remove();

            if (data && data.messages && data.messages.length) {
                $.each(data.messages, function (i) {
                    var message = $(self.messageBlockTmpl({
                        data: data.messages[i]
                    }));

                    $(self.options.messagesWrap).append(_.last(message));
                });
            }
        },

        /**
         * Set notification modal.
         *
         * @private
         */
        _setModal: function () {
            var self = this,
                options = {
                text: this.options.modalText,
                modalOptions: {
                    'type': 'popup',
                    'modalClass': 'restriction-modal-quote',
                    'responsive': true,
                    'innerScroll': true,
                    'title': this.options.modalTitle,
                    'buttons': [{
                        class: 'action-primary confirm action-accept proceed',
                        type: 'button',
                        text: 'Ok',

                        /** Click action */
                        click: function () {
                            this.closeModal();
                            self.options.dataSend.quote.recalcPrice = 1;
                            self.options.dataSend.quote.update = 1;
                            self.options.displayMessageChanges = true;
                            self._sendAjax(self.options.dataSend);
							$('.update-order-prices-button').prop('disabled', true);
                        }
                    }, {
                        class: 'action-primary cancel action-accept',
                        type: 'button',
                        text: 'Cancel',

                        /** Click action */
                        click: function () {
                            this.closeModal();
							$('.update-order-prices-button').prop('disabled', false);
                        }
                    }]
                }
            };

            this.element.notificationModal(options);
        },

        /**
         * Configure SKU.
         *
         * @param {Object} e
         *
         * @private
         */
        _configureSku: function (e) {
            var $element = $(e.target).attr('data-role') ? $(e.target) : $(e.target).parent(),
                productId = $element.data('product-id'),
                productSku = $element.data('product-sku'),
                elementId = $element.data('id');

            window.addBySku.configure(productId, productSku, elementId);
        },

        /**
         * Open notification modal.
         *
         * @private
         */
        _openModal: function () {
            this.element.trigger('notification');
        },

        /**
         * Public method for _updateFailedSkus()
         */
        updateFailedSkus: function () {
            this._updateFailedSkus();
        },

        /**
         * Get URL for update failed skus action
         *
         * @returns {String}
         *
         * @private
         */
        _getUpdateFailedSkusUrl: function () {
            return this.element.data(this.options.updateErrorsUrl);
        },

        /**
         * Update failed skus action
         *
         * @private
         */
        _updateFailedSkus: function () {
            var url = this._getUpdateFailedSkusUrl();

            this._sendAjax(this.options.dataSend, false, url);
        },

        /**
         * Render failed skus block
         *
         * @param {Object} data
         *
         * @private
         */
        _renderFailedSkus: function (data) {
            if (data) {
                $(this.options.errorSkuBlock).html(data.errors);
                $(this.options.errorSkuBlock).trigger('contentUpdated');
            }
        },

        /**
         * Set data in totals table.
         *
         * @param {Object} data
         *
         * @private
         */
        _setTotals: function (data) {
            var $totals = $(this.options.totals),
                $negotiatedPrice = $(this.options.draftTable);

            $totals.trigger('updateTotalTable', data);
            $negotiatedPrice.trigger('setCatalogPrice', data.catalogPriceValue);
        },

        /**
         * Public method for _addBySku()
         */
        addBySku: function () {
            this._addBySku();
        },

        /**
         * Collection and send sku data
         *
         * @private
         */
        _addBySku: function () {
            var row = $(this.options.addQuoteForm).find(this.options.quoteRow),
                self = this,
                items = [];

            row.each(function () {
                items.push({
                    'sku': $(this).find(self.options.productSku).val(),
                    'qty': $(this).find(self.options.productQty).val()
                });
            });
            self.options.dataSend.quote.addItems = items;
            self.options.dataSend.quote.recalcPrice = 1;
            self.options.dataSend.quote.update = 1;
            this.options.displayMessageChanges = true;
            this._sendAjax(
                this.options.dataSend,
                false,
                false,
                function () {
                    self._updateFailedSkus();
                }
            );
        },

        /**
         * Update proposed shipping price.
         *
         * @param {Object} e
         * @param {Object} data
         *
         * @private
         */
        _updateShipping: function (e, data) {
            this.options.dataSend.quote.shippingMethod = data.method;
            this.options.dataSend.quote.shipping = data.price;
            this._sendAjax(this.options.dataSend);
        },

        /**
         * Render files
         *
         * @param {Array} files
         *
         * @private
         */
        _renderFiles: function (files) {
            var self = this;

            $(this.options.attachFiles).find(this.options.wrapFile + ':not(:visible)').remove();
            $(this.options.attachedFile).remove();
            $.each(files, function () {
                self._addFiles(this);
            });
        },

        /**
         * Render file
         *
         * @param {Object} data
         *
         * @private
         */
        _addFiles: function (data) {
            var fileData = {
                    label: this.options.labelText,
                    name: data.name
                },
                file = $(this.filesTemplate({
                    data: fileData
                }));

            file.find(this.options.delFile)
                .deleteItem({
                    attachmentId: data.id
                });
            $(this.options.wrapFiles).append(_.last(file));
        },

        /**
         * Update negotiated price.
         *
         * @param {Object} e
         *
         * @private
         */
        _updateItems: function (e) {
            if (!$(e.target).hasClass('hasError')) {
                this.options.dataSend.quote.proposed = {
                        'type': $(this.options.totalType + ':checked').val(),
                        'value': '' + e.target.value
                    };

                this.options.displayMessageChanges = false;
                this.options.dataSend.quote.update = 0;
                this._sendAjax(this.options.dataSend);
            }
        },

        /**
         * Block send ajax
         *
         * @param {Object} e
         * @param {Object} data
         *
         * @private
         */
        blockSend: function (e, data) {
            this.options.isAjax = !data;
        },

        /**
         * Update quote totals.
         *
         * @private
         */
        _updateTotal: function () {
            var self = this,
                items = [],
                countItems = this.options.dataSend.quote.items.length,
                isChange = false;
			
            $(this.options.productItem).each(function (i, elem) {
                var qty = $(elem).find(self.options.qty).val(),
                    sku = $(elem).find(self.options.sku).val();
				
                if (qty > 0 && sku) {
					
                    items.push({
                        'id': $(elem).data('productId'),
                        'qty': $(elem).find(self.options.qty).val(),
                        'sku': $(elem).find(self.options.sku).val(),
                        'productSku': $(this).find(self.options.productSku).val(),
                        'config': $(elem).find(self.options.config).val(),
						'proposed': $("input[name='item["+elem.id+"][proposedprice]']").val()
                    });
				
                    if ($(elem).find(self.options.qty).val() != $(elem).find(self.options.qtyOld).val()) { //eslint-disable-line
                        isChange = true;
                    }
                }
            });
			
            this.options.dataSend.quote.items = items;
            this.options.dataSend.quote.recalcPrice = 1;
            this.options.dataSend.quote.update = 1;

            if (items.length != countItems || isChange) { //eslint-disable-line eqeqeq
                this.options.displayMessageChanges = true;
            }
            this._sendAjax(this.options.dataSend);
		
        },

        /**
         * Block duplication ajax send.
         *
         * @param {Object} e
         *
         * @private
         */
        _checkAjax: function (e) {
            if (this.options.isAjax) {
                this.options.focusEl = $(e.target);
                this.options.focusEl.attr('disabled', 'disabled');
            }

            if (!e && this.options.focusEl) {
                this.options.focusEl.removeAttr('disabled');
                this.options.focusEl.focus();
            }
        },

        /**
         * Set correct url for ajax.
         *
         * @param {Object} e
         * @param {Object} data
         *
         * @private
         */
        _setUrl: function (e, data) {
            var stringJson;
			
            if (e) {
                this.options.dataSend.quote.proposed = {
                    'type':  $(this.options.totalType + ':checked').val(),
                    'value': $(this.options.totalValue + ':not(:disabled)').val()
                };
                stringJson = JSON.stringify(this.options.dataSend);
                data.data.append('dataSend', stringJson);
                data.data.append(this.options.negotiableQuoteUpdateFlag, true);
                this.options.url = data.url;
                this.options.reload = data.needReload;
                this._sendAjax(
                    data.data,
                    {
                        contentType: false,
                        processData: false
                    },
                    false,
                    this._updateQuote.bind(this)
                );
            } else {
                this.options.url = this.element.data(this.options.updateUrl);
                this.options.reload = false;
            }
        },

        /**
         * Update quote data.
         *
         * @private
         */
        _updateQuote: function () {
			
            this.options.dataSend.quote.recalcPrice = 1;
            this.options.dataSend.quote.update = 1;
            this.options.displayMessageChanges = true;
            this._sendAjax(
                this.options.dataSend,
                false,
                false,
                this._updateFailedSkus.bind(this)
            );
			
        },

        /**
         * Get correct url for ajax.
         *
         * @param {String} ajaxUrl
         *
         * @returns {String}
         *
         * @private
         */
        _getAjaxUrl: function (ajaxUrl) {
            return ajaxUrl || this.options.url;
        },

        /**
         * Set quote status.
         *
         * @param {String} status
         * @private
         */
        _setStatus: function (status) {
            $(this.options.statusSelector).text(status);
        },

        /**
         * Set quote currency info.
         *
         * @param {String} rate
         * @param {String} label
         * @private
         */
        _setCurrencyInfo: function (rate, label) {
            $(this.options.currencyLabelSelector).text(label);
            $(this.options.currencyRateSelector).text(rate.toFixed(4));
        },

        /**
         * Send ajax to server.
         *
         * @param {Object} data
         * @param {Object} ajaxOptions
         * @param {String} ajaxUrl
         * @param {Function} callback
         * @private
         */
        _sendAjax: function (data, ajaxOptions, ajaxUrl, callback) {
            var setAjaxOptions, url;
			
			if(data.quote){
				$(data.quote.items).each(function(i,value){
					value.proposed = $("input[name='item["+value.id+"][proposedprice]']").val();
				});
				
				
			}
            data[this.options.negotiableQuoteUpdateFlag] = true;
            setAjaxOptions = ajaxOptions;

            if (!setAjaxOptions) {
                setAjaxOptions = this.options.defaultAjax;
            }

            if (!this.options.isAjax) {
                this.options.isAjax = true;
                url = this._getAjaxUrl(ajaxUrl);
                $.ajax({
                    url: url,
                    data: data,
                    type: 'post',
                    dataType: 'json',
                    showLoader: true,
                    contentType: setAjaxOptions.contentType,
                    processData: setAjaxOptions.processData,

                    /**
                     * @callback
                     */
                    success: $.proxy(function (resp) {
                        var length;
	
                        if (resp && resp.items) {
                            length = resp.items.length;
			
                            if (length > 0) {
                                this._addRow(resp);
                            }
                            this._setTotals(resp);

                            if (this.options.displayMessageChanges && resp.hasChanges) {
                                $(this.options.messagesSelector).removeClass('hidden');
                                $(this.options.totalsTableSelector).addClass('_highlighted');
                            } else {
                                $(this.options.messagesSelector).addClass('hidden');
                                $(this.options.totalsTableSelector).removeClass('_highlighted');
                            }
							
                        }
						
                        if (resp && resp.draftCommentFiles) {
                            this._renderFiles(resp.draftCommentFiles);
                        }

                        if (ajaxUrl && ajaxUrl === this._getUpdateFailedSkusUrl()) {
                            this._renderFailedSkus(resp);
                        } else {
                            this._renderMessage(resp);
                        }
                        this.options.isAjax = false;

                        if (resp.hasFailedItems) {
                            this._updateFailedSkus();
                        }

                        if (resp.quoteStatus) {
                            this._setStatus(resp.quoteStatus);
                        }

                        if (resp.currencyRate && resp.currencyLabel) {
                            this._setCurrencyInfo(resp.currencyRate, resp.currencyLabel);
                        }
                    }, this),

                    /**
                     * @callback
                     */
                    complete: $.proxy(function () {
                        this.options.dataSend.quote.addItems = [];
                        $(this.options.totalShipping).trigger('clearError');
                        this._checkAjax(false);

                        if (this.options.reload) {
                            window.location.reload();
							
                        }
						
                        this._setUrl();

                        if (callback && typeof callback === 'function') {
                            callback();
                        }
                    }, this)
                });
            }
        }

    });

    return $.mage.updateQuote;
});
