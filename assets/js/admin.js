(($) => {
    $( document ).ready( () => {
        let availabilityField = $('#rtcl-availability');
        let pricingField      = $('#rtcl-pricing-price');
        let validityField     = $('#visible');

        if( adminListing?.isFree == 1 ) {
            availabilityField.prop('disabled', true);
            pricingField.prop('disabled', true);
            validityField.prop('disabled', true);
        }

        $('.form-check-input-free-package').on('click', (e) => {
            if( $('.form-check-input-free-package').is(":checked") ) {
                availabilityField.val(0);
                pricingField.val(0);
                validityField.val(0);
                $('.form-check-input-free-package').val(1);
                $('.form-check-with-validity input').attr('disabled', true);
                $('.form-check-with-validity input').val(0);
                $('.form-check-with-validity input').prop('checked', false);
            } else {
                availabilityField.prop('disabled', false);
                pricingField.prop('disabled', false);
                validityField.prop('disabled', false);
                $('.form-check-input-free-package').val(0);
                $('.form-check-with-validity input').removeAttr('disabled');
            }
        });

        $('.form-check-with-validity input').on('click', (e) => { //free package that will change the price, availability, validity will not be affected
            if( $('.form-check-with-validity input').is(":checked") ) {
                pricingField.val(0);
                $('.form-check-with-validity input').val(1);
                $('.form-check-input-free-package').attr('disabled', true);
                $('.form-check-input-free-package').val(0);
                $('.form-check-input-free-package').prop('checked', false);
            } else {
                pricingField.attr('disabled', false);
                $('.form-check-with-validity input').val(0);
                $('.form-check-input-free-package').removeAttr('disabled');
            }
        });
    });
})(jQuery);