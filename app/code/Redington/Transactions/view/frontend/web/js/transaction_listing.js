require([
    "jquery"
    ], function($){
        $( document ).ready(function() {   
            $('.admin__data-grid-outer-wrap').eq(1).css('display','none');
            $('.credit-request').click(function(e){
                $('.admin__data-grid-outer-wrap').eq(1).css('display','none');
                $('.admin__data-grid-outer-wrap').eq(0).css('display','block');
                $('.credit-request').addClass('active');
                $('.payment-history').removeClass('active');
                e.preventDefault();
            });
            $('.payment-history').click(function(e){
                $('.admin__data-grid-outer-wrap').eq(1).css('display','block');
                $('.admin__data-grid-outer-wrap').eq(0).css('display','none');
                $('.payment-history').addClass('active');
                $('.credit-request').removeClass('active');
                e.preventDefault();
            });
        });
    }
);
