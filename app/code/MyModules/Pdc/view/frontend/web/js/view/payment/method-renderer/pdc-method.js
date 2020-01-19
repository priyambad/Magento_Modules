/**
 * @author Redington Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Pdc
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
                template: 'Redington_Pdc/payment/pdc'
            },
			
			getPaymentTerm: function(){
				return  window.checkoutConfig.PdcTerm;
			},	
			getBankDetails: function(){
				return  window.checkoutConfig.accountName;
			},
            /** Upload file to blob storage */
            uploadImage: function (fileVal, inputId) {
                if(fileVal){
                    var messageSection = inputId + '_message_section';
                    var blobDocumentContainerId = inputId + "_preview";
                    $('#' + blobDocumentContainerId).hide();

                    $('#fileupload-new').html('');
                    $('#otpdocument_blob_url').val();
                
                    var reg = /(.jpg|.pdf|.png|.JPG|.PNG|.PDF)$/;
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
                        var formData = $('#pdc_document_upload');
                        var fileData = new FormData();
                        fileData.append(fileVal.name, fileVal, fileVal.name);
                        fileData.append('file_type', inputId);
                        fileData.append('companyName', 'companyname');
                        this.customAjaxCall(fileData);
                    }
                }
            },
            /** Delete file from blob storage */
            deleteUploadedRefdoc: function () {
                $('#pdc_document').val('');
                var fileTypeVal = "pdc_document";
                var blobUrlInputId = "pdc_document_blob_url";
                var blobDocumentContainerId = "pdc_document_preview";
                var blobDocumentDocname = "pdc_document_docname";
                var messageSection = 'pdc_document_message_section';
                $('#' + blobUrlInputId).val('');
                $('#' + blobDocumentContainerId).hide();
                $('#' + blobDocumentDocname).text('');
                $('#' + messageSection).html('');
            },
            /** Call ajax to upload blob storage */
            customAjaxCall: function (fileData) {
               
                var uploadDocUrl = urlBuilder.build('rgcheckout/documents/uploaddoc');
                var fileTypeVal = "pdc_document";
                var blobUrlInputId = "pdc_document_blob_url";
                var blobDocumentContainerId = "pdc_document_preview";
                var blobDocumentDocname = "pdc_document_docname";
                var messageSection = 'pdc_document_message_section';

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
                        
                        $('#' + blobUrlInputId).val('');
                        $('#' + blobDocumentContainerId).hide();
                        $('#' + blobDocumentDocname).text('');
                        $('#' + messageSection).html('');
                    });
            },
        });
});