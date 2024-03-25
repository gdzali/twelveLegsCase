jQuery(document).ready(function($) {
    $(document).on('submit', '.woocommerce-checkout', function(event) {
        var button = $(this);
        var form = button.closest('.wc-block-components-form');
        var postcodeField = form.find('#billing_postcode');
        var enteredPostcode = postcodeField.val();
        var allowedPostcodes = [];

        $.ajax({
            type: 'POST',
            url: custom_wc_frontend_validation_params.ajax_url,
            data: {
                action: 'custom_wc_get_allowed_postcodes',
                nonce: custom_wc_frontend_validation_params.nonce
            },
            success: function(response) {
                if (response && response.success) {
                    allowedPostcodes = response.data.allowed_postcodes;

                    // Check if entered postcode is allowed
                    if (allowedPostcodes.indexOf(enteredPostcode) === -1) {
                        // Prevent default button action
                        event.preventDefault();
                        // Show error message
                        postcodeField.addClass('woocommerce-invalid');
                        postcodeField.after('<p class="woocommerce-error">Sorry, we do not deliver to this location.</p>');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    });
});
