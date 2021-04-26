<?php
/**
 * DPD General Settings
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'DPD_Settings_General', false ) ) {
	return new DPD_Settings_General();
}

class DPD_Settings_General extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'dpd';
		$this->label = __( 'DPD', 'woo-shipping-dpd-baltic' );

		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		return array(
			''           => __( 'General', 'woo-shipping-dpd-baltic' ),
			'parcels'    => __( 'Parcels configurations', 'woo-shipping-dpd-baltic' ),
			'warehouses' => __( 'Warehouses', 'woo-shipping-dpd-baltic' ),
			'manifests'  => __( 'Manifests', 'woo-shipping-dpd-baltic' ),
			'collect'    => __( 'Collection request', 'woo-shipping-dpd-baltic' ),
		);
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section name.
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		if ( 'parcels' === $current_section ) {
			return array(
				array(
					'title' => '',
					'type'  => 'title',
					'id'    => 'dpd_parcels',
				),

				array(
					'title'   => __( 'Default label format', 'woo-shipping-dpd-baltic' ) . ' *',
					'id'      => 'dpd_label_size',
					'default' => 'A4',
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'options' => [
						'A4' => __( 'A4', 'woo-shipping-dpd-baltic' ),
						'A6' => __( 'A6', 'woo-shipping-dpd-baltic' ),
					],
				),

				array(
					'title'   => __( 'Parcel distribution', 'woo-shipping-dpd-baltic' ) . ' *',
					'id'      => 'dpd_parcel_distribution',
					'default' => '3',
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'options' => [
						1 => __( 'All products in same parcel', 'woo-shipping-dpd-baltic' ),
						2 => __( 'Each product as separate parcel', 'woo-shipping-dpd-baltic' ),
						3 => __( 'Each product quantity as separate parcel', 'woo-shipping-dpd-baltic' ),
					],
				),

				array(
					'title'   => __( 'Return labels', 'woo-shipping-dpd-baltic' ),
					'desc'    => __( 'Enable printing return labels', 'woo-shipping-dpd-baltic' ),
					'id'      => 'dpd_return_labels',
					'default' => 'no',
					'type'    => 'checkbox',
				),

				array(
					'title'   => __( 'ROD / document return service', 'woo-shipping-dpd-baltic' ),
					'desc'    => __( 'Enable ROD / document return service', 'woo-shipping-dpd-baltic' ),
					'id'      => 'dpd_rod_service',
					'default' => 'no',
					'type'    => 'checkbox',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'dpd_parcels',
				),
			);
		} elseif ( 'warehouses' === $current_section ) {

			return [
				[
					'title' => '',
					'type'  => 'title',
					'id'    => 'dpd_warehouses',
				],
				[
					'type' => 'sectionend',
					'id'   => 'dpd_warehouses',
				],
			];

		} elseif ( 'manifests' === $current_section ) {

			return [
				[
					'title' => '',
					'type'  => 'title',
					'id'    => 'dpd_manifests',
				],
				[
					'type' => 'sectionend',
					'id'   => 'dpd_manifests',
				],
			];

		} elseif ( 'collect' === $current_section ) {

			return [
				[
					'title' => '',
					'type'  => 'title',
					'id'    => 'dpd_collect',
				],
				[
					'type' => 'sectionend',
					'id'   => 'dpd_collect',
				],
			];

		} else {
		    $countries = [
                'BE' => __( 'Belgium', 'woocommerce' ),
                'CH' => __( 'Czech Republic', 'woocommerce' ),
                'DE' => __( 'Germany', 'woocommerce' ),
                'DK' => __( 'Denmark', 'woocommerce' ),
                'EE' => __( 'Estonia', 'woocommerce' ),
                'ES' => __( 'Spain', 'woocommerce' ),
                'FI' => __( 'Finland', 'woocommerce' ),
                'FR' => __( 'France', 'woocommerce' ),
                'GB' => __( 'United Kingdom (UK)', 'woocommerce' ),
                'LU' => __( 'Luxembourg', 'woocommerce' ),
                'LT' => __( 'Lithuania', 'woocommerce' ),
                'LV' => __( 'Latvia', 'woocommerce' ),
                'NL' => __( 'Netherlands', 'woocommerce' ),
                'PT' => __( 'Portugal', 'woocommerce' ),
                'SE' => __( 'Sweden', 'woocommerce' ),
            ];
            asort( $countries );

			return array(

				array(
					'title' => __( 'DPD API options', 'woo-shipping-dpd-baltic' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'dpd_api',
				),

				array(
					'title'    => __( 'DPD API country', 'woo-shipping-dpd-baltic' ) . ' *',
					'id'       => 'dpd_api_service_provider',
					'desc_tip' => __( 'Select the DPD office that provided your API credentials.', 'woo-shipping-dpd-baltic' ),
					'default'  => 'lt',
					'type'     => 'select',
					'class'    => 'wc-enhanced-select',
					'options'  => [
						'lt' => __( 'Lithuania', 'woocommerce' ),
						'lv' => __( 'Latvia', 'woocommerce' ),
						'ee' => __( 'Estonia', 'woocommerce' ),
					],
				),

				array(
					'title'   => __( 'API username', 'woo-shipping-dpd-baltic' ) . ' *',
					'desc'    => '',
					'id'      => 'dpd_api_username',
					'default' => '',
					'type'    => 'text',
				),

				array(
					'title'   => __( 'API password', 'woo-shipping-dpd-baltic' ) . ' *',
					'desc'    => '',
					'id'      => 'dpd_api_password',
					'default' => '',
					'type'    => 'password',
				),

				array(
					'title'    => __( 'Test mode', 'woo-shipping-dpd-baltic' ),
					'desc'     => __( 'Enable DPD test mode', 'woo-shipping-dpd-baltic' ),
					'id'       => 'dpd_test_mode',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => __( 'Enable this if you want your requests to go to DPD\'s test server. Test server has separate credentials, ask from your local DPD office if required.', 'woo-shipping-dpd-baltic' ),
				),

                array(
                    'title'   => __( 'Fetch pickup points lists for these countries', 'woo-shipping-dpd-baltic' ),
                    'desc'    => '',
                    'id'      => 'dpd_parcels_countries',
                    'css'     => 'min-width: 350px;',
                    'default' => [ 'LT', 'LV', 'EE' ],
                    'options' => $countries,
                    'type'    => 'multi_select_countries',
                ),

				array(
					'title'    => __( 'Google Maps API key', 'woo-shipping-dpd-baltic' ),
					'desc'     => '<br><a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">' . __( 'Get your key', 'woo-shipping-dpd-baltic' ) . '</a>',
					'desc_tip' => __( 'Google Maps will be used to suggest a postcode to your customer based on their address and to display a map of Pickup Points in checkout.', 'woo-shipping-dpd-baltic' ),
					'id'       => 'dpd_google_map_key',
					'default'  => '',
					'type'     => 'text',
				),

				array(
					'title'   => __( 'COD fee', 'woo-shipping-dpd-baltic' ) . ' *',
					'desc'    => '',
					'desc_tip' => __( 'This will add provided fee if cash on delivery is selected as the payment method in checkout.', 'woo-shipping-dpd-baltic' ),
					'id'      => 'dpd_cod_fee',
					'default' => '0',
					'type'    => 'text',
				),

				array(
					'title'   => __( 'COD percentage fee', 'woo-shipping-dpd-baltic' ) . ' *',
					'desc'    => '',
					'desc_tip' => __( 'This will add provided fee + percentage fee from goods if cash on delivery will be selected as payment method.', 'woo-shipping-dpd-baltic' ),
					'id'      => 'dpd_cod_fee_percentage',
					'default' => '0',
					'type'    => 'text',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'dpd_api',
				),

			);
		}
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section, $hide_save_button;

		$settings = $this->get_settings( $current_section );

		if ( in_array( $current_section, [ 'manifests', 'collect' ] ) ) {
			$hide_save_button = true;
		}

		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		if ( 'warehouses' === $current_section ) {

			$this->custom_save( $_REQUEST['warehouses'], $current_section );

		} else {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::save_fields( $settings );
		}

		if ( '' === $current_section ) {
			$parcels = Dpd_Baltic_Admin::http_client( 'parcelShopSearch_', [
				'country'          => 'LT',
				'city'             => 'Vilnius',
				'fetchGsPUDOpoint' => 1,
			] );

			// Check API credentials
			if ( $parcels && $parcels->status == 'err' ) {
				dpd_baltic_add_flash_notice( $parcels->errlog, 'error', true );
			} else {
                wp_schedule_single_event( time() + 10, 'dpd_parcels_updater' );
			}
		}
	}

	/**
	 * Custom settings fields save
	 *
	 * @param array $request Request comming from POST
	 * @param string $current_section Option section name
	 *
	 * @return bool True if updated, false if not.
	 */
	public function custom_save( $request, $current_section = '' ) {

		$updated         = false;
		$current_section = ( ! empty( $current_section ) ? $current_section . '_' : '' );

		foreach ( $request as $k => $value ) {
			$option  = $current_section . $k;
			$updated = update_option( $option, $value );
		}

		if ( $updated ) {
			return true;
		}

		return false;
	}
}

return new DPD_Settings_General();
