(($) => {
    $( document ).ready( () => {
        $('.pricing-table .rtcl-pricing-option .rtcl-checkout-pricing').on('click', (e) => {
            if( e?.currentTarget?.className?.includes('disabled') ) {
                $('.pricing-table .rtcl-pricing-option .rtcl-checkout-pricing').prop('checked', false);
            }
        });
    });


})(jQuery);