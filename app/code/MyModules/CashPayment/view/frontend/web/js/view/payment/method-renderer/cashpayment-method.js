/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_CashPayment
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function ($, Component, urlBuilder) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Redington_CashPayment/payment/cashpayment'
            },
			getBankDetails: function(){
				var html = $('.trasnferdetails').html(window.checkoutConfig.bankTransfer);
				
			},
            /** Upload file to blob storage */
            uploadImage: function (fileVal, inputId) {
                if(fileVal)
                {
                    var messageSection = inputId + '_message_section';
                    var blobDocumentContainerId = inputId + "-preview";
                    $('#' + blobDocumentContainerId).hide();

                    $('#fileupload-new').html('');
                    $('#otpdocument_blob_url').val();
                  
                    var reg = /(.jpg|.pdf|.png|.JPG|.PDF|.PNG)$/;
                    var fileError = false;
                    if (fileVal.size >= 8388608) {
                        $('#' + messageSection).html('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">File size should be less than 8 MB.</div>');
                        fileError = true;
                    }
                    if (!reg.test(fileVal.name)) {
                        $('#' + messageSection).html('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">Only pdf, jpg, jpeg & png format are supported.</div>');
                        fileError = true;
                    }
                    if (fileError) {
                        $('#' + inputId).val('');
                    } else {
                        var formData = $('#cash_document_upload');
                        var fileData = new FormData();
                        fileData.append(fileVal.name, fileVal, fileVal.name);
                        fileData.append('file_type', inputId);
                        fileData.append('companyName', 'companyname');
                        this.customAjaxCall(fileData);
                    }
                }
            },
            /** Dekete document from blob storage */
            deleteUploadedRefdoc: function () {
                $('#cash_document').val('');
                var fileTypeVal = "cash_document";
                var blobUrlInputId = fileTypeVal + "_blob_url";
                var blobDocumentContainerId = fileTypeVal + "_preview";
                var blobDocumentDocname = fileTypeVal + "_docname";
                var messageSection = fileTypeVal + '_message_section';
                $('#' + blobUrlInputId).val('');
                $('#' + blobDocumentContainerId).hide();
                $('#' + blobDocumentDocname).text('');
                $('#' + messageSection).html('');
            },
            /** Call Ajax to upload file */
            customAjaxCall: function (fileData) {
				
                var uploadDocUrl = urlBuilder.build('rgcheckout/documents/uploaddoc');
                var fileTypeVal = "cash_document";
                var blobUrlInputId = fileTypeVal + "_blob_url";
                var blobDocumentContainerId = fileTypeVal + "_preview";
                var blobDocumentDocname = fileTypeVal + "_docname";
                var messageSection = fileTypeVal + '_message_section';

                $.ajax({
                    showLoader: true,
                    url: uploadDocUrl,
                    data: fileData,
                    type: "POST",
                    processData: false,
                    contentType: false,
                }).done(function (result) {
                    if (result.success) {
                        var uploadedDocUrl = result.doc_url;

                        $('#' + blobUrlInputId).val(uploadedDocUrl);

                        var uploadedDocUrlArr = uploadedDocUrl.split("/");
                        var documentName = "";
                        if (uploadedDocUrlArr.length) {
                            var lastItem = uploadedDocUrlArr.length - 1;
                            documentName = uploadedDocUrlArr[lastItem];
                        }
                        $('#' + blobDocumentContainerId).show();
                        $('#' + blobDocumentDocname).text(documentName);
                        $('#' + messageSection).html('');

                    } else {
                        $('#' + blobUrlInputId).val('');
                        $('#' + blobDocumentContainerId).hide();
                        $('#' + blobDocumentDocname).text('');
                        $('#' + messageSection).html('');
                    }
                })
                    .fail(function (jqXHR, exception) {
                        // Our error logic here
                        var msg = '';
                        if (jqXHR.status === 0) {
                            msg = 'Not connect.\n Verify Network.';
                        } else if (jqXHR.status == 404) {
                            msg = 'Requested page not found. [404]';
                        } else if (jqXHR.status == 500) {
                            msg = 'Internal Server Error [500].';
                        } else if (exception === 'parsererror') {
                            msg = 'Requested JSON parse failed.';
                        } else if (exception === 'timeout') {
                            msg = 'Time out error.';
                        } else if (exception === 'abort') {
                            msg = 'Ajax request aborted.';
                        } else {
                            msg = 'Uncaught Error.\n' + jqXHR.responseText;
                        }
                        console.log(msg);
                        $('#' + blobUrlInputId).val('');
                        $('#' + blobDocumentContainerId).hide();
                        $('#' + blobDocumentDocname).text('');
                        $('#' + messageSection).html('');
                    });
            },
            /** Returns send check to info */
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },


        });
});