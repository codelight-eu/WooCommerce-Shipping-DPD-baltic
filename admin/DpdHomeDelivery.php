<?php

class DpdHomeDelivery extends WC_Shipping_Method {
	/**
	 * Min amount for free shipping.
	 */
	public $free_min_amount = '';

	/**
	 * Price calculation type.
	 */
	public $type = 'order';

	/**
	 * Price cost rates.
	 */
	public $cost_rates = '';

	protected $shifts_field_name;

	/**
	 * DpdShippingMethod constructor.
	 *
	 * @param int $instance_id
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct();

		$this->id                 = 'dpd_home_delivery';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'DPD home delivery', 'woo-shipping-dpd-baltic' );
		$this->method_description = __( 'DPD home delivery shipping method', 'woo-shipping-dpd-baltic' );
		$this->supports           = [
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		];

		$this->shifts_field_name = 'wc_shipping_' . $this->id . '_shifts';

		$this->init();
	}

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	function init() {
		$this->init_form_fields();
		$this->init_settings();

		$this->title           = $this->get_option( 'title', $this->method_title );
		$this->tax_status      = $this->get_option( 'tax_status' );
		$this->cost            = $this->get_option( 'cost' );
		$this->free_min_amount = $this->get_option( 'free_min_amount', '' );

		$this->type       = $this->get_option( 'type', 'order' );
		$this->cost_rates = $this->get_option( 'cost_rates' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	public function init_actions_and_filters() {
		add_action( 'woocommerce_review_order_after_shipping', [ $this, 'review_order_after_shipping' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'checkout_save_order_timeshifts' ], 10, 2 );

		if ( is_admin() ) {
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'show_selected_timeshift' ), 20 );
			add_filter( 'woocommerce_admin_order_preview_get_order_details', array( $this, 'show_selected_timeshift_in_order_preview' ), 20, 2 );
		}
	}

	/**
	 * Define settings field for this shipping
	 * @return void
	 */
	function init_form_fields() {
		$this->instance_form_fields = [
			'title'           => [
				'title'       => __( 'Method title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'DPD home delivery', 'woo-shipping-dpd-baltic' ),
				'desc_tip'    => true,
			],
			'tax_status'      => [
				'title'   => __( 'Tax status', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => [
					'taxable' => __( 'Taxable', 'woocommerce' ),
					'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
				],
			],
			'cost'            => [
				'title'       => __( 'Cost', 'woocommerce' ),
				'type'        => 'text',
				'placeholder' => '',
				'description' => '',
				'default'     => '0',
				'desc_tip'    => true,
			],
			'free_min_amount' => [
				'title'       => __( 'Minimum order amount for free shipping', 'woo-shipping-dpd-baltic' ),
				'type'        => 'price',
				'placeholder' => '',
				'description' => __( 'Users have to spend this amount to get free shipping.', 'woo-shipping-dpd-baltic' ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'type'            => [
				'title'   => __( 'Calculation type', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'order',
				'options' => [
					'order'  => __( 'Per order', 'woo-shipping-dpd-baltic' ),
					'weight' => __( 'Weight based', 'woo-shipping-dpd-baltic' ),
				],
			],
			'cost_rates'      => [
				'title'       => __( 'Rates', 'woo-shipping-dpd-baltic' ),
				'type'        => 'textarea',
				'placeholder' => '',
				'description' => __( 'Example: 5:10.00,7:12.00 Weight:Price,Weight:Price, etc...', 'woo-shipping-dpd-baltic' ),
				'default'     => '',
				'desc_tip'    => true,
			],
		];
	}

	/**
	 * Get setting form fields for instances of this shipping method within zones.
	 *
	 * @return array
	 */
	public function get_instance_form_fields() {
		if ( is_admin() ) {
			wc_enqueue_js(
				"jQuery( function( $ ) {
					function wc" . $this->id . "ShowHideRatesField( el ) {
						var form = $( el ).closest( 'form' );
						var ratesField = $( '#woocommerce_" . $this->id . "_cost_rates', form ).closest( 'tr' );
						if ( 'weight' !== $( el ).val() || '' === $( el ).val() ) {
							ratesField.hide();
						} else {
							ratesField.show();
						}
					}

					$( document.body ).on( 'change', '#woocommerce_" . $this->id . "_type', function() {
						wc" . $this->id . "ShowHideRatesField( this );
					});

					// Change while load.
					$( '#woocommerce_" . $this->id . "_type' ).change();
					$( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
						if ( 'wc-modal-shipping-method-settings' === target ) {
							wc" . $this->id . "ShowHideRatesField( $( '#wc-backbone-modal-dialog #woocommerce_" . $this->id . "_type', evt.currentTarget ) );
						}
					} );
				});"
			);
		}

		return parent::get_instance_form_fields();
	}

	/**
	 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
	 *
	 * @access public
	 *
	 * @param mixed $package
	 *
	 * @return void
	 */
	public function calculate_shipping( $package = [] ) {
		$has_met_min_amount = false;
		$cost               = $this->cost;
		$weight             = WC()->cart ? WC()->cart->get_cart_contents_weight() : 0;

		if ( WC()->cart && ! empty( $this->free_min_amount ) && $this->free_min_amount > 0 ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( WC()->cart->display_prices_including_tax() ) {
				$total = round( $total - ( WC()->cart->get_discount_total() + WC()->cart->get_discount_tax() ), wc_get_price_decimals() );
			} else {
				$total = round( $total - WC()->cart->get_discount_total(), wc_get_price_decimals() );
			}

			if ( $total >= $this->free_min_amount ) {
				$has_met_min_amount = true;
			}
		}

		if ( $this->type == 'weight' ) {
			$rates = explode( ',', $this->cost_rates );

			foreach ( $rates as $rate ) {
				$data = explode( ':', $rate );

				if ( $data[0] >= $weight ) {
					if ( isset( $data[1] ) ) {
						$cost = str_replace( ',', '.', $data[1] );
					}

					break;
				}
			}
		}

		$rate = array(
			'id'      => $this->get_rate_id(),
			'label'   => $this->title,
			'cost'    => $has_met_min_amount ? 0 : $cost,
			'package' => $package,
		);

		$this->add_rate( $rate );

		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
	}

	/**
	 * woocommerce_review_order_after_shipping action
	 * return available time shifts based on customer city
	 */
	public function review_order_after_shipping() {

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( ! empty( $chosen_shipping_methods ) && substr( $chosen_shipping_methods[0], 0, strlen( $this->id ) ) === $this->id ) {

			$shipping_city    = sanitize_title( WC()->customer->get_shipping_city() );
			$shipping_country = strtolower( WC()->customer->get_shipping_country() );
			$base_country     = wc_get_base_location()['country'] ? wc_get_base_location()['country'] : '';
			$base_country     = strtolower( $base_country );

			$countries_lt = [
				'Vilnius',
				'Kaunas',
				'Klaipėda',
				'Šiauliai',
				'Panevežys',
				'Alytus',
				'Marijampolė',
				'Telšiai',
				'Tauragė'
			];
			$countries_lv = [
				'Rīga',
				'Talsi',
				'Liepāja',
				'Jelgava',
				'Jēkabpils',
				'Daugavpils',
				'Rēzekne',
				'Valmiera',
				'Gulbene',
				'Cēsis',
				'Saldus',
				'Ventspils'
			];

			$countries_lt_sanitized = array_map( function ( $el ) {
				return sanitize_title( $el );
			}, $countries_lt );

			$countries_lv_sanitized = array_map( function ( $el ) {
				return sanitize_title( $el );
			}, $countries_lv );

			$select_data = [];

			if ( $base_country == 'lt' && $shipping_country === 'lt' && in_array( $shipping_city, $countries_lt_sanitized ) ) {
				$select_data = [ '08:00 - 18:00', '08:00 - 14:00', '14:00 - 18:00', '18:00 - 22:00' ];
			}

			if ( $base_country == 'lv' && $shipping_country === 'lv' && in_array( $shipping_city, $countries_lv_sanitized ) ) {
				$select_data = [ '08:00 - 18:00', '18:00 - 22:00' ];
			}

			$selected_terminal = WC()->session->get( $this->shifts_field_name );

			$template_data = [
				'shifts'     => $select_data,
				'field_name' => $this->shifts_field_name,
				'selected'   => $selected_terminal ? $selected_terminal : ''
			];

			if ( ! empty( $select_data ) ) {
				do_action( $this->id . '_before_timeframes' );
				wc_get_template( 'checkout/time-frames.php', $template_data );
				do_action( $this->id . '_after_timeframes' );
			}

		}

	}

	public function checkout_save_order_timeshifts( $order_id ) {

		$selected_shift = $_POST[ $this->shifts_field_name ] ? $_POST[ $this->shifts_field_name ] : false;

		if ( $selected_shift ) {
			update_post_meta( $order_id, $this->shifts_field_name, filter_var( $selected_shift, FILTER_SANITIZE_STRING ) );
		}

	}

	public function show_selected_timeshift( $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {
			echo '<p>';
			echo '<strong>Deliver between:</strong><br>';
			echo get_post_meta( $order->get_id(), 'wc_shipping_dpd_home_delivery_shifts', true );
			echo '</p>';
		}

	}

	public function show_selected_timeshift_in_order_preview( $order_details, $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {

			$shift_between = __( 'Deliver between: ', 'woo-shipping-dpd-baltic' ) . get_post_meta( $order->get_id(), 'wc_shipping_dpd_home_delivery_shifts', true );

			if ( isset( $order_details['shipping_via'] ) ) {
				$order_details['shipping_via'] = sprintf( '%s: %s', $order->get_shipping_method(), esc_html( $shift_between ) );
			}
		}

		return $order_details;

	}
}