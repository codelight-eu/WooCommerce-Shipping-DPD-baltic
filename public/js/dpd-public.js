(function( $, window, document ) {
	'use strict';

    function parcelChange() {
        let $wc_shipping_dpd_parcels_terminal = $('#wc_shipping_dpd_parcels_terminal');
        let cod = 0;

        cod = $wc_shipping_dpd_parcels_terminal.find(':selected').data('cod');

        $(document).on('change','#wc_shipping_dpd_parcels_terminal', function(){

            let $this = $(this);
            cod = $this.find(':selected').data('cod');

            set_session(cod);

        });
    }

    function shipping_method_change() {

        $(document.body).on('click', 'input[name="shipping_method[0]"]', function() {

            set_session(1);

        });

    }

    function payment_method_change() {

        $(document.body).on('change', "[name='payment_method']", function() {
            $(document.body).trigger("update_checkout");
        });

    }

    function set_session( cod ) {
        let data = {
            'action': 'set_checkout_session',
            'cod': cod
        };

        let obj = null;

        if (typeof wc_checkout_params !== 'undefined') {
            obj = wc_checkout_params;
        } else if (typeof wc_cart_params !== 'undefined') {
            obj = wc_cart_params;
        }

        if (obj !== null) {
            $.post(obj.ajax_url, data, function() {
                setTimeout(function () {
                    $(document.body).trigger("update_checkout");
                }, 300);
            });
        }
    }

    function timeShiftChange(){
        $(document.body).on('change', "[name='wc_shipping_dpd_home_delivery_shifts']", function() {
            $(document.body).trigger("update_checkout");
        });
    }

	$(function() {
    	parcelChange();
        shipping_method_change();
        // payment_method_change();
        timeShiftChange();
    });

})( window.jQuery, window, document );
