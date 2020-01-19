require([
    "jquery"
    ], function($){
        $( document ).ready(function() {
            $('#country option:first').text('Please select country');

            var url = location.href;
            if(url.indexOf('edit/id/')>0){
                $('.tabnumber:first').css('display','none');
                $('.check-ic:first').removeAttr('style');
            }else{
                $('#country option:first').attr('selected','selected');
            }
            $('#postalCode').keypress(function(evt){
                postalInputVal = $('#postalCode').val();
                if(postalInputVal.length >= 6){
                    return false;
                }else{
                    if (evt.which != 8 && evt.which != 0 && evt.which < 48 || evt.which > 57)
                    {
                        evt.preventDefault();
                    }
                }
            });
          
             $('#telephone').keypress(function(e){
                telephoneInputVal = $('#telephone').val();
                if(telephoneInputVal.length >= 10 ||  e.keyCode == 45 || e.keyCode < 48 || e.keyCode > 57){
                    
                    return false;
                }
            });

            $('.street').keypress(function(e){
                var char = String.fromCharCode(e.which);
                if(!(/^[a-zA-Z0-9-( ).,'/& ]*$/.test(char))){
                    e.preventDefault();
                    return;
                }
            });

            $('.street').bind("paste", function(e){
                // access the clipboard using the api
                var pastedData = e.originalEvent.clipboardData.getData('text');
                var validValue = pastedData.replace(/[^a-z0-9-( )/.,&]/gi, '');
                e.preventDefault();
                var previousVal = $(this).val();
                var finalVal = previousVal +  validValue;
                $(this).val(finalVal.substr(0,40));
            });

            $('#company-input').keypress(function(e){
                var char = String.fromCharCode(e.which);
                if(!(/^[a-zA-Z0-9-( )\\/.,&]*$/.test(char))){
                    e.preventDefault();
                    return;
                }
            });

            $('#company-input').bind("paste", function(e){
                // access the clipboard using the api
                var pastedData = e.originalEvent.clipboardData.getData('text');
                var validValue = pastedData.replace(/[^a-z0-9-( )\\/.,&]/gi, '');
                e.preventDefault();
                var previousVal = $(this).val();
                var finalVal = previousVal +  validValue;
                $(this).val(finalVal.substr(0,70));
            });
            
            countryVal = $('#country').val();

            if(countryVal){
                if(countryVal=="AE"){
                    $('#company-input').attr('placeholder','As per trade license');
                }
                else{
                    if(countryVal=="KE"){
                        $('#company-input').attr('placeholder','As per certificate of incorporation');
                    }
                }
                $.ajax({
                    showLoader: true,
                    url: window.getCountryCodeUrl,
                    type: "GET"
                }).done(function(result){
                    if(result.success) {
                        $('#country-flag').attr('src',window.imageUrl+'/countryFlag_'+countryVal+'.png');
                        $('.countryCode').text('(+'+result.data[countryVal]+')');
                    }else{
                        $('#country-flag').attr('src','');
                        $('.countryCode').text('');
                    }
                });
                $.ajax({
                    showLoader: true,
                    url: window.regionDataUrl,
                    type: "GET"
                }).done(function(result){
                    if(result.success) {
                        var regionArray = JSON.parse(result.data);
                        var regionData = regionArray[countryVal];
                        var keys = Object.keys(regionData);
                        keys.forEach(function(region){
                            $('#region_code').append('<option value='+region+' >'+regionData[region]+'</option>');
                        });
                        var number = window.regionId;
                        if(number){
                            numberString = number.toString();
                            for(i=0;i<= 3-numberString.length;i++){
                                numberString = "0"+numberString;
                            }
                            $('#region_code').val(numberString);
                        }else{
                            $('#region_code').val('');
                        }
                    }else{
                        //console.log('error');
                    }
                });
            }
            
            $('#country').change(function(e){
                countryCode = this.value;
                if(countryCode){
                    if(countryCode=="AE"){
                        $('#company-input').attr('placeholder','As per trade license');
                    }
                    else{
                        if(countryCode=="KE"){
                            $('#company-input').attr('placeholder','As per certificate of incorporation');
                        }
                    }
                    $.ajax({
                        showLoader: true,
                        url: window.getCountryCodeUrl,
                        type: "GET"
                    }).done(function(result){
                        if(result.success) {
                            $('#country-flag').attr('src',window.imageUrl+'/countryFlag_'+countryCode+'.png');
                            $('.countryCode').text('(+'+result.data[countryCode]+')');
                        }else{
                            $('#country-flag').attr('src','');
                            $('.countryCode').text('');
                        }
                    });
                    $('#region_code')
                        .empty()
                        .append('<option selected="" value="">Please select region</option>');
                    $.ajax({
                    showLoader: true,
                    url: window.regionDataUrl,
                    type: "GET"
                }).done(function(result){
                    if(result.success) {
                        var regionArray = JSON.parse(result.data);
                        var regionData = regionArray[countryCode];
                        var keys = Object.keys(regionData);
                        keys.forEach(function(region){
                            $('#region_code').append('<option value='+region+'>'+regionData[region]+'</option>');
                        });
                    }else{
                        //ole.log('error')
                    }
                });
                }else {
                    $('#country-flag').attr('src','');
                    $('.countryCode').text('');
                }
            });
            //Set street values in appropriate fields
            $('#street_1').val($('#street1').val());
            $('#street_2').val($('#street2').val());
            
            //Disable country drop-down if address is being edited
            url = location.href;
            if(url.indexOf("/edit/id/")>0){
                $countryId = $('#country').val();
                $('#country').prop('disabled','disabled');
                $('#country').attr('name','');
                $('#country').attr('id','');
                $('.form-address-edit ').append('<input type="hidden" id="country" name="country_id" value='+$countryId+'>')
            }else{
                $('#comment-button').css('visibility','hidden');
            }
            
            
            
            
        });
        
        
        var formData = $('.form-address-edit');
        formData.mage('validation', {});
        $('#next-address').click(function(event){
            if (formData.valid()){
                formValidity = true;
                $countryId = $('#country').val();


                postalArray = [];
                postalPatternArray = window.postalPattern.split('|');
                postalPatternArray.forEach(function(data){
                    postal = data.split(':');
                    postalArray[postal[0]] = postal[1];
                });
                postalRegex = new RegExp(postalArray[$countryId]);
                postalCode = $('#postalCode').val();
                if(postalCode){
                    validity = postalRegex.test(postalCode);
                    if(!validity || (parseInt(postalCode)==0)) {
                        formValidity = false;
                        $('#postalCode').closest('.custom-form').append('<div for="postalCode" generated="true" class="mage-error" id="postalCode-error">Please enter valid postal code.</div>');
                    }
                }

                phoneRegex = new RegExp('^'+window.phonePattern+'$');
                phone = $('input.mobilenumber').val();
                validity = phoneRegex.test(phone);
                if(!validity) {
                    formValidity = false;
                    $('input.mobilenumber').closest('.custom-form').append('<div for="postalCode" generated="true" class="mage-error" id="postalCode-error">Please enter valid mobile number.</div>');
                }
                if(!formValidity){
                    return;
                }

                event.preventDefault();
                param = formData.serialize();
                AjaxUrl = formData[0].action;
                $.ajax({
                    showLoader: true,
                    url: AjaxUrl,
                    data: param,
                    type: "POST"
                }).done(function (data) {
                    if(data.success) {
                        $('.tabnumber:first').css('display','none');
                        $('.check-ic:first').removeAttr('style');
                        var addressId = data.address_id;
                        var companyname = data.company_name;
                        //fetch list of required documents 
                        
                        var countryId = $('#country').val();
                        var url = window.documentFetchApi+'?CountryIsoCode='+countryId+'&DocUploadType='+window.docConfigurationType;
                        
                        //call Ajax to get documents data
                        
                        $.ajax({
                            showLoader: true,
                            url: url,
                            type: "GET"
                        }).done(function(docData){
                            var documentForm =  $('#document-form');
                            docData.forEach(function(data){
                                $elemCode = getCodeFromName(data.DocumentName);
                                if(!$('div #'+$elemCode).length)
                                    appendDocumetnToForm(data, addressId);

                                    $('.docUpload-wrapper').each(function(){
                                        if($(this).hasClass('active')){
                                            clear = $(this).find('.clear-doc-data');
                                            fileUpload = $(this).find('.fileupload-preview');
                                            target = $(this).find('.fileupload');
                                            fileUpload.appendTo(target);
                                            clear.appendTo(target);
                                        }else {
                                            clear = $(this).find('.clear-doc-data');
                                            fileUpload = $(this).find('.fileupload-preview');
                                            target = $(this).find('.cs-uploadContainer');
                                            fileUpload.appendTo(target);
                                            clear.appendTo(target);
                                        }
                                    });

                                    /*$('#'+$elemCode+'_number').keypress(function(e){
                                        var char = String.fromCharCode(e.which);
                                        initialVal = $('.documentNumber').val();
                                        if(!(/^[a-zA-Z0-9- ]*$/.test(char) || char == '-' || char == '/')){
                                            e.preventDefault();
                                            return;
                                        }
                                    });*/

                                    $('#'+$elemCode+'_number').bind("paste", function(e){
                                        // access the clipboard using the api
                                        var pastedData = e.originalEvent.clipboardData.getData('text');
                                        var validValue = pastedData.replace(/[^a-z0-9-/ ]/gi, '');
                                        e.preventDefault();
                                        var previousVal = $(this).val();
                                        var finalVal = previousVal +  validValue;   
                                        $(this).val(finalVal.substr(0,15));
                                    });
                                    
                                    $('.docUpload-wrapper').click(function(e){
                                        if(!($(e.target).is("input"))){
                                            $('.docUpload-wrapper').removeClass('active');
                                            $('.document-form-content').css('display','none');
                                            $(this).find('.document-form-content').css('display','block');
                                            $(this).addClass('active');
                                            $('.docUpload-wrapper').each(function(){
                                                if($(this).hasClass('active')){
                                                    clear = $(this).find('.clear-doc-data');
                                                    fileUpload = $(this).find('.fileupload-preview');
                                                    target = $(this).find('.fileupload');
                                                    fileUpload.appendTo(target);
                                                    clear.appendTo(target);
                                                }else {
                                                    clear = $(this).find('.clear-doc-data');
                                                    fileUpload = $(this).find('.fileupload-preview');
                                                    target = $(this).find('.cs-uploadContainer');
                                                    fileUpload.appendTo(target);
                                                    clear.appendTo(target);
                                                }
                                            });
                                        }
                                    });
                                    $('.clear-doc-data').click(function(e){
                                        parentForm = $(this).closest('.row');
                                        parentForm.find('.tabs-title.required').removeClass('cs-uploaded');
                                        parentForm.find('.document-file').val('');
                                        parentForm.find('.documentNumber').val('');
                                        parentForm.find('.documentIssue').val('');
                                        parentForm.find('.documentExpiry').val('');
                                        $(this).closest('.form-group').find('.fileName').val('');
                                        parentForm.find('.fileupload-preview').empty();
                                        parentForm.find('#doc-url-input').val('');
                                        $(this).css('display','none');
                                        e.preventDefault();
                                    });
                                    $(".docUpload-wrapper:not(:eq(0))").removeClass('active');
                                    $('.document-form-content').first().css('display','block');
                                    $('.documentIssue').calendar({
                                        changeYear: true,
                                        changeMonth: true,
                                        yearRange: "1970:2050",
                                        buttonText: "Select Issue Date",
                                    });
                                    $('.documentExpiry').calendar({
                                        changeYear: true,
                                        changeMonth: true,
                                        yearRange: "1970:2050",
                                        buttonText: "Select Expiry Date",
                                    });
                            });
                            changeDocuemntsOrder(docData);
                            appendButtonToForm(addressId);
                            $('#document-submit').click(function(e){
                                $('.doc-form-val-error').remove();
                                var validity = validateDocumentSubmit();
                                if(validity) {
                                    window.location.href = window.submitUrl;
                                }else {
                                    return;
                                }
                            });
                            jQuery('.next-document').click(function(e){
                                e.preventDefault();
                                parentNode = jQuery(this).parents('.form-group');
                                divData = jQuery('#'+parentNode[0].id+' :input');
                                serializedDocData = divData.serialize();
                                $('.doc-form-val-error').remove();
                                nextForm = $(this).closest('.form-group').next().find('.document-form-content');
                                closestFormFiled = $(this).closest('.docUpload-wrapper');
                                nextFormField = $(this).closest('.form-group').next().find('.docUpload-wrapper');
                                if(validateDocumentInputs(parentNode)) {
                                    $.ajax({
                                        showLoader: true,
                                        url: window.setDocUrl,
                                        // url: 'http://armstrong-partnerwebapi-qa01.azurewebsites.net/api/DocumentAPI/UploadFileOnBlob',
                                        type: "POST",
                                        data: serializedDocData,
                                        // data : file,
                                        success: function (result) {
                                            closestFormFiled.removeClass('active');
                                            closestFormFiled.find('.tabs-title').addClass('cs-uploaded');
                                            nextFormField.addClass('active');
                                            $('.document-form-content').css('display','none');
                                            nextForm.css('display','block');

                                            $('.docUpload-wrapper').each(function(){
                                                if($(this).hasClass('active')){
                                                    clear = $(this).find('.clear-doc-data');
                                                    fileUpload = $(this).find('.fileupload-preview');
                                                    target = $(this).find('.fileupload');
                                                    fileUpload.appendTo(target);
                                                    clear.appendTo(target);
                                                }else {
                                                    clear = $(this).find('.clear-doc-data');
                                                    fileUpload = $(this).find('.fileupload-preview');
                                                    target = $(this).find('.cs-uploadContainer');
                                                    fileUpload.appendTo(target);
                                                    clear.appendTo(target);
                                                }
                                            });
                                        }
                                    });
                                }else{
                                    return;
                                }
                            });
                            $('input[type="file"]').change(function(e){
                                if(e.target.files[0]){
                                    parentForm = $(this).closest(".row");
                                    parentForm.find(".documentExpiry").val("");
                                    parentForm.find(".documentNumber").val("");
                                    parentForm.find(".documentIssue").val("");
                                    var file = e.target.files[0];
                                    $('.doc-form-val-error').remove();
                                    $(this).parent('.btn-file').next().next().css('display','none');
                                    $(this).parent('.btn-file').next().text('');
                                    var reg=/(.jpg|.jpeg|.pdf|.png|.JPG|.JPEG|.PDF|.PNG)$/;
                                    if (!reg.test(file.name)) {
                                        this.value = '';
                                        $(this).closest('.fileupload-new').append('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">Only pdf, jpg, jpeg & png format are supported.</div>');
                                        return false;
                                    }else {
                                        if(file.size >= 8388608) {
                                            this.value = '';
                                            $(this).closest('.fileupload-new').append('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">File size should be less than 8 MB.</div>');
                                            return false;
                                        }
                                    }
                                    fileData = new FormData();
                                    fileData.append(file.name,file,file.name);
                                    fileData.append('addressId',addressId);
                                    fileData.append('companyName',companyname);
                                    targetElement = this;
                                    $.ajax({
                                    showLoader: true,
                                    url: window.uploadDocUrl,
                                    // url: 'http://armstrong-partnerwebapi-qa01.azurewebsites.net/api/DocumentAPI/UploadFileOnBlob',
                                    type: "POST",
                                    data: fileData,
                                    // data : file,
                                    processData: false,
                                    contentType: false,
                                    success: function (result) {
                                        $(targetElement).next().val(result.doc_url);
                                        if(file.name.length > 20){
                                            formatedFileName =  file.name.substring(0, 16)+'....';
                                        }else{
                                            formatedFileName = file.name;
                                        }
                                        $(targetElement).closest('.form-group').find('.fileName').val(file.name);
                                        $(targetElement).parent('.btn-file').next().text(formatedFileName);
                                        $(targetElement).parent('.btn-file').next().next().css('display','inline-block');
                                        $(targetElement).closest('.fileupload').find('.fileupload-preview').attr('href',result.doc_url);
                                        }
                                    });
                                }
                            });
                        });
                        $('#nav-information').css('display','none');
                        $('#nav-documents').css('display','block');
                        $('#nav-information-tab').removeClass('active');
                        $('#nav-tabContent').removeClass('show active');
                        $('#nav-documents-tab').addClass('active');
                        $('#nav-documents').addClass('show active');
                    }
                    else {
                        console.log(data.message);
                    }
                });
            }
        });
        function validateDocumentSubmit(){
            var validity = true;
            var requiredDocuments = $('#document-form .row .tabs-title.required');
            requiredDocuments.each(function(index){
                var requiredElement = $(this);
                if(!requiredElement.hasClass('cs-uploaded')){
                    requiredElement.after('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">This is a required Document, please upload the document.</div>');
                    validity = false;
//                     return false;
                }
            });
            return validity;
        }
        function validateDocumentInputs($parentNode) {
            validity = true;
			var element,parent;
			var value,reuired;
            requiredError = '<div generated="true" class="mage-error doc-form-val-error" style="display: block;">This is a required field.</div>';
            elementCode = 'doc-url-input';
            element = jQuery('#'+parentNode[0].id).find('#'+elementCode);
            value = element[0].value;
            required = element[0].required;
            if(required && value=='') {
               validity = false;
               var parent = element.closest('.fileupload-new');
               parent.append(requiredError);
            }
            
            elementCode = 'documentName';
            element = jQuery('#'+parentNode[0].id).find('.'+elementCode);
            value = element[0].value;
            required = element[0].required;
            if(required && value=='') {
               validity = false;
               parent = element.closest('.custom-form');
               parent.append(requiredError);
            }
            elementCode = 'documentNumber';
            element = jQuery('#'+parentNode[0].id).find('.'+elementCode);
            value = element[0].value;
            required = element[0].required;
            if(required && value=='') {
               validity = false;
               parent = element.closest('.custom-form');
               parent.append(requiredError);
            }
            
            elementCode = 'documentIssue';
            element = jQuery('#'+parentNode[0].id).find('.'+elementCode);
            value = element[0].value;
            required = element[0].required;
            parent = element.closest('.custom-form');

            if(required && value=='') {
               validity = false;
               parent.append(requiredError);
            }else{
                if(value !='') {
                    var dateParts = value.split("/");
                    if(!isDate(value)){
                        validity = false;
                        parent.append('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">Please enter valid date.</div>')
                    }
                    else {
                        try{
                            var dateObject = new Date(dateParts[2],+dateParts[0]-1,+dateParts[1]);
                            dateObjectTimeStamp = dateObject.setHours(0,0,0,0);
                            var currentDate = new Date();
                            currentDateTimeStamp = currentDate.setHours(0,0,0,0);
                            validDate = dateObjectTimeStamp < currentDateTimeStamp;
                            if(!validDate){
                                validity = false;
                                parent.append('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">Issue date should be less than today.</div>')
                            }
                        }catch(e){
                            validity = false;
                            parent.append('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">Please enter valid date.</div>')
                        }
                    }
                }
            }
            
            elementCode = 'documentExpiry';
            element = jQuery('#'+parentNode[0].id).find('.'+elementCode);
            value = element[0].value;
            required = element[0].required;
            parent = element.closest('.custom-form');
            if(required && value=='') {
               validity = false;
               parent.append(requiredError);
            }else{
                if(value !='') {
                    var dateParts = value.split("/");
                    if(!isDate(value)){
                        validity = false;
                        parent.append('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">Please enter valid date.</div>')
                    }
                    else {
                        try{
                            var dateObject = new Date(dateParts[2],+dateParts[0]-1,+dateParts[1]);
                            dateObjectTimeStamp = dateObject.setHours(0,0,0,0);
                            var currentDate = new Date();
                            currentDateTimeStamp = currentDate.setHours(0,0,0,0);
                            validDate = dateObjectTimeStamp > currentDateTimeStamp;
                            if(!validDate){
                                validity = false;
                                parent.append('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">Expiry date should be greater than today.</div>')
                            }
                        }catch(e){
                            validity = false;
                            parent.append('<div generated="true" class="mage-error doc-form-val-error" style="display: block;">Please enter valid date.</div>')
                        }
                    }
                }
            }
            return validity;
        }
        function isDate(txtDate)
        {
            var currVal = txtDate;
            if(currVal == '')
                return false;

            var rxDatePattern = /^(\d{1,2})(\/|-)(\d{1,2})(\/|-)(\d{4})$/; //Declare Regex
            var dtArray = currVal.match(rxDatePattern); // is format OK?

            if (dtArray == null) 
                return false;

            //Checks for dd/mm/yyyy format.
            dtMonth = dtArray[1];
            dtDay= dtArray[3];
            dtYear = dtArray[5];        

            if (dtMonth < 1 || dtMonth > 12) 
                return false;
            else if (dtDay < 1 || dtDay> 31) 
                return false;
            else if ((dtMonth==4 || dtMonth==6 || dtMonth==9 || dtMonth==11) && dtDay ==31) 
                return false;
            else if (dtMonth == 2) 
            {
                var isleap = (dtYear % 4 == 0 && (dtYear % 100 != 0 || dtYear % 400 == 0));
                if (dtDay> 29 || (dtDay ==29 && !isleap)) 
                    return false;
            }
            return true;
        }
        function getCodeFromName(name) {
            var matches = name.match(/\b(\w)/g); // ['J','S','O','N']
            var acronym = matches.join('_'); // JSON
            return acronym.toLowerCase();
        }

        function changeDocuemntsOrder(docData){
            var lastElement = '';
            docData.forEach(function(data,index){
                elemCode = getCodeFromName(data.DocumentName);
                if(index == 0 ){
                    $('#'+elemCode).prependTo('#document-form');
                    lastElement = elemCode;
                }else{
                    $("#"+elemCode).detach().insertAfter("#"+lastElement);
                }
            });
            $(".docUpload-wrapper:not(:eq(0))").removeClass('active');
            $(".docUpload-wrapper").first().addClass('active');
            $('.document-form-content').css('display','none');
            $('.document-form-content').first().css('display','block').addClass('active');
            $('.docUpload-wrapper').each(function(){
                if($(this).hasClass('active')){
                    clear = $(this).find('.clear-doc-data');
                    fileUpload = $(this).find('.fileupload-preview');
                    target = $(this).find('.fileupload');
                    fileUpload.appendTo(target);
                    clear.appendTo(target);
                }else {
                    clear = $(this).find('.clear-doc-data');
                    fileUpload = $(this).find('.fileupload-preview');
                    target = $(this).find('.cs-uploadContainer');
                    fileUpload.appendTo(target);
                    clear.appendTo(target);
                }
            });
        }

        function appendButtonToForm(addressId){
            var buttonDom ='<div class="row">\
            <div class="col-md-12">\
                <div class="margin-btm">\
                    <a class="action back btn btn btn-outline-success btn-outline-green" href="'+window.bookUrl+'id/'+addressId+'">\
                        <span>Back</span>\
                    </a>&nbsp;&nbsp;&nbsp;&nbsp;\
                    <a  id="document-submit" class="btn btn-primary btn-green-filled btn-success">Submit</a>\
                </div>\
            </div>\
        </div>'
            var documentForm =  $('#document-form');
            documentForm.append(buttonDom);
        }


        function appendDocumetnToForm(documentData, addressId) {
            var documentForm =  $('#document-form');
            var docRequired = documentData.IsMandatory;
            var datesRequired = documentData.ExpiryDateNeeded;
            var formId = getCodeFromName(documentData.DocumentName);
            var docRequiredClass = docRequired ? 'required' : '';
            var dateRequiredClass = datesRequired ? 'required' : '';
            var isForwarder;
            var docAttribute = documentData.DocumentAttribute;
            var isDocAttributeRequired = docAttribute == "Trade license number" ? 'required' : '' ;
            if(window.location.href.indexOf('forwarder')>0) {
                isForwarder = true;
            }else{
                isForwarder = false;
            }

            var docForm = '<div class="form-group" id='+formId+'>\
                            <input type="hidden" name="documentCode" value='+getCodeFromName(documentData.DocumentName)+'>\
                            <input type="hidden" name="documentName" value="'+documentData.DocumentName+'">\
                            <input type="hidden" name="isDocumentRequired" value = '+docRequired+'>\
                            <input type="hidden" name="isDateRequired" value='+datesRequired+'>\
                            <input type="hidden" name="addressId" value='+addressId+'>\
                            <input type="hidden" name="fileName" class="fileName" >\
                            <input type="hidden" name="is_forwarder" value='+isForwarder+'>\
                            <input type="hidden" name="docAttribute" value="'+docAttribute+'">\
                            <div class="row">\
                                <div class="col-md-8">\
                                    <div class="docUpload-wrapper active">\
                                        <div class= "tabs-title '+docRequiredClass+'">\
                                            <span class="tabnumber" style="display: none">1 </span>\
                                            <span class="check-ic">\
                                            <i class="fa fa-check"></i>\
                                            '+documentData.DocumentName+'\
                                            </span>\
                                            <div class="cs-uploadContainer"></div>\
                                        </div>\
                                        <div class="document-form-content" style="display:none">\
                                        <div class="fileupload fileupload-new" data-provides="fileupload">\
                                            <div class="upload-txt">Upload document</div>\
                                              <span class="btn btn-primary btn-file">\
                                              <img src="img/upload-pin-icon.png" alt="" width="20" height="20">\
                                              <span class="fileupload-new">Choose document</span>\
                                              <span class="fileupload-exists">Upload document</span>\
                                              <input id='+getCodeFromName(documentData.DocumentName)+' class="document-file" name="documentFile" type="file">\
                                              <input type="hidden" name="docUrl" id="doc-url-input"  '+docRequiredClass+'>\
                                            </span>\
                                            <a class="fileupload-preview" target="blank" href="#"></a>\
                                            <a href="#" class="clear-doc-data" style="display: none" style="display:none">X</a>\
                                        </div>\
                                            <div class="row">\
                                                <div class="col-md-6">\
                                                    <div class="custom-form">\
                                                        <label class="control-label">Document caption</label>\
                                                        <input name="documentCaption" type="name" class="form-control documentName" title="" placeholder=""'+docRequiredClass+' value="'+documentData.DocumentName+'" readonly>\
                                                    </div>\
                                                </div>\
                                                <div class="col-md-6">\
                                                    <div class="custom-form">\
                                                        <label class="control-label '+isDocAttributeRequired+'">'+docAttribute+'</label>\
                                                        <input name="documentNumber" id="'+formId+'_number" '+isDocAttributeRequired+' maxlength="15" type="name" class="form-control documentNumber" title="" placeholder="">\
                                                    </div>\
                                                </div>\
                                                <div class="col-md-6">\
                                                    <div class="custom-form">\
                                                        <label class="control-label '+dateRequiredClass+'">Issue date</label>\
                                                        <input name="documentIssue" '+dateRequiredClass+' type="name" class="form-control documentIssue" title="" placeholder="mm/dd/yyyy">\
                                                    </div>\
                                                </div>\
                                                <div class="col-md-6">\
                                                    <div class="custom-form">\
                                                        <label class="control-label '+dateRequiredClass+'">Expiry date</label>\
                                                        <input name="documentExpiry" '+dateRequiredClass+' type="name" class="form-control documentExpiry" title="" placeholder="mm/dd/yyyy">\
                                                    </div>\
                                                </div>\
                                            </div>\
                                            <div class="row">\
                                                <div class="col-md-12">\
                                                    <div class="pull-right">\
                                                    <button class="next-document btn btn btn-outline-success btn-outline-green" type="button"> Upload </button>\
                                                    </div>\
                                                </div>\
                                            </div>\
                                        </div>\
                                    </div>\
                                </div>\
                            </div>\
                        </div>';
            documentForm.append(docForm);
        }
    }
);
