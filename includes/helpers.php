<?php

/**
 * Add a flash notice to {prefix}options table until a full page refresh is done
 *
 * @param string $notice our notice message
 * @param string $type This can be "info", "warning", "error" or "success", "warning" as default
 * @param boolean $dismissible set this to TRUE to add is-dismissible functionality to your notice
 *
 * @return void
 */
function dpd_baltic_add_flash_notice( $notice = '', $type = 'warning', $dismissible = true ) {
	// Here we return the notices saved on our option, if there are not notices, then an empty array is returned
	$notices          = get_option( 'dpd_baltic_flash_notices', [] );
	$dismissible_text = ( $dismissible ) ? 'is-dismissible' : '';

	// We add our new notice.
	array_push( $notices, array(
		'notice'      => $notice,
		'type'        => $type,
		'dismissible' => $dismissible_text
	) );

	// Then we update the option with our notices array
	update_option( 'dpd_baltic_flash_notices', $notices );
}

/**
 * Function executed when the 'admin_notices' action is called, here we check if there are notices on
 * our database and display them, after that, we remove the option to prevent notices being displayed forever.
 * @return void
 */
function dpd_baltic_display_flash_notices() {
	$notices = get_option( 'dpd_baltic_flash_notices', array() );

	// Iterate through our notices to be displayed and print them.
	foreach ( $notices as $notice ) {
		printf( '<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
			$notice['type'],
			$notice['dismissible'],
			$notice['notice']
		);
	}

	// Now we reset our options to prevent notices being displayed forever.
	if ( ! empty( $notices ) ) {
		delete_option( 'dpd_baltic_flash_notices' );
	}
}

/*
 * Helper function to convert weight to kg.
 */
function dpd_baltic_weight_in_kg($cart_weight) {
	$shop_weight_unit = get_option( 'woocommerce_weight_unit' );

	if ( $shop_weight_unit === 'oz' ) {
		$divider = 35.274;
	} elseif ( $shop_weight_unit === 'lbs' ) {
		$divider = 2.20462;
	} elseif ( $shop_weight_unit === 'g' ) {
		$divider = 1000;
	} else {
		$divider = 1;
	}

	return $cart_weight / $divider;
}
