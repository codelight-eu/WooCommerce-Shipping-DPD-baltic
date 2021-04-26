<?php

class DpdSameDayParcels extends WC_Shipping_Method {
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

	/**
	 * DpdShippingMethod constructor.
	 *
	 * @param int $instance_id
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct();

		$this->id                 = 'dpd_sameday_parcels';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'DPD same day to Pickup points (parcel lockers and -shops)', 'woo-shipping-dpd-baltic' );
		$this->method_description = __( 'DPD same day to Pickup points shipping method (Available only in Riga)', 'woo-shipping-dpd-baltic' );
		$this->supports           = [
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		];

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

		$this->terminal_field_name = 'wc_shipping_' . $this->id . '_terminal';

		$this->i18n_selected_terminal = esc_html__( 'Selected Pickup Point:', 'woo-shipping-dpd-baltic' );
	}

	public function init_actions_and_filters() {
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'woocommerce_cart_shipping_method_full_label' ), 10, 2 );

		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'review_order_after_shipping' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_save_order_terminal' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_selected_terminal' ), 10, 2 );

		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'show_selected_terminal_in_order_details' ), 20, 3 );

		if ( is_admin() ) {
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'show_selected_terminal' ), 20 );
			add_filter( 'woocommerce_admin_order_preview_get_order_details', array( $this, 'show_selected_terminal_in_order_preview' ), 20, 2 );
		}
	}

	/**
	 * Define settings field for this shipping
	 * @return void
	 */
	public function init_form_fields() {
		$this->instance_form_fields = [
			'title'                  => [
				'title'       => __( 'Method title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'DPD same day to Pickup points', 'woo-shipping-dpd-baltic' ),
				'desc_tip'    => true,
			],
			'tax_status'             => [
				'title'   => __( 'Tax status', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => [
					'taxable' => __( 'Taxable', 'woocommerce' ),
					'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
				],
			],
			'cost'                   => [
				'title'       => __( 'Cost', 'woocommerce' ),
				'type'        => 'text',
				'placeholder' => '',
				'description' => '',
				'default'     => '0',
				'desc_tip'    => true,
			],
			'free_min_amount'        => [
				'title'       => __( 'Minimum order amount for free shipping', 'woo-shipping-dpd-baltic' ),
				'type'        => 'price',
				'placeholder' => '',
				'description' => __( 'Users have to spend this amount to get free shipping.', 'woo-shipping-dpd-baltic' ),
				'default'     => '',
				'desc_tip'    => true,
			],
			'type'                   => [
				'title'   => __( 'Calculation type', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'order',
				'options' => [
					'order'  => __( 'Per order', 'woo-shipping-dpd-baltic' ),
					'weight' => __( 'Weight based', 'woo-shipping-dpd-baltic' ),
				],
			],
			'cost_rates'             => [
				'title'       => __( 'Rates', 'woo-shipping-dpd-baltic' ),
				'type'        => 'textarea',
				'placeholder' => '',
				'description' => __( 'Example: 5:10.00,7:12.00 Weight:Price,Weight:Price, etc...', 'woo-shipping-dpd-baltic' ),
				'default'     => '',
				'desc_tip'    => true,
			],
			[
				'title' => 'Shifts messages',
				'type'  => 'title',
			],
			'delivery_same_day_text' => [
				'title'   => __( 'Same day text', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => __( 'Today', 'woo-shipping-dpd-baltic' ),
			],
			'delivery_next_day_text' => [
				'title'   => __( 'Next day text', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => __( 'Tomorrow', 'woo-shipping-dpd-baltic' ),
			],
			[
				'title' => 'Monday',
				'type'  => 'title',
			],
			'monday'                 => [
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			],
			'monday_am_from'         => [
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			],
			'monday_am_to'           => [
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '12:30',
			],
			'monday_pm_from'         => [
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			],
			'monday_pm_to'           => [
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
			],
			[
				'title' => 'Tuesday',
				'type'  => 'title',
			],
			'tuesday'                => [
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			],
			'tuesday_am_from'        => [
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			],
			'tuesday_am_to'          => [
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '12:30',
			],
			'tuesday_pm_from'        => [
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			],
			'tuesday_pm_to'          => [
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
			],
			[
				'title' => 'Wednesday',
				'type'  => 'title',
			],
			'wednesday'              => [
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			],
			'wednesday_am_from'      => [
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			],
			'wednesday_am_to'        => [
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '12:30',
			],
			'wednesday_pm_from'      => [
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			],
			'wednesday_pm_to'        => [
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
			],
			[
				'title' => 'Thursday',
				'type'  => 'title',
			],
			'thursday'               => [
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			],
			'thursday_am_from'       => [
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			],
			'thursday_am_to'         => [
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '12:30',
			],
			'thursday_pm_from'       => [
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			],
			'thursday_pm_to'         => [
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
			],
			[
				'title' => 'Friday',
				'type'  => 'title',
			],
			'friday'                 => [
				'title'   => __( 'Enabled', 'woo-shipping-dpd-baltic' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Yes', 'woo-shipping-dpd-baltic' ),
			],
			'friday_am_from'         => [
				'title'   => __( 'Morning from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '00:00',
			],
			'friday_am_to'           => [
				'title'   => __( 'Morning to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '12:30',
			],
			'friday_pm_from'         => [
				'title'   => __( 'Noon from', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '15:00',
			],
			'friday_pm_to'           => [
				'title'   => __( 'Noon to', 'woo-shipping-dpd-baltic' ),
				'type'    => 'text',
				'default' => '23:59',
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
	 * @param array $package
	 *
	 * @return bool
	 */
	public function is_available( $package ) {
		$available                 = false;
		$cart_weight               = WC()->cart === null ? 0 : WC()->cart->get_cart_contents_weight();
		$shipping_country          = WC()->customer === null ? strtolower( get_option( 'woocommerce_default_country' ) ) : strtolower( WC()->customer->get_shipping_country() );
		$shipping_city             = sanitize_title( WC()->customer->get_shipping_city() );
		$base_city                 = sanitize_title( WC()->countries->get_base_city() );
		$base_country              = wc_get_base_location()['country'] ? wc_get_base_location()['country'] : '';
		$base_country              = strtolower( $base_country );
		$parcels_delivery_limit_kg = 20;

		$cart_weight_kg = dpd_baltic_weight_in_kg($cart_weight);

		if ( ! in_array( $shipping_country, [ 'lt', 'lv', 'ee' ] ) ) {
			return false;
		}

		$countries_lv           = [ 'Rīga' ];
		$countries_lv_sanitized = array_map( function ( $el ) {
			return sanitize_title( $el );
		}, $countries_lv );

		if ( $base_country !== 'lv' || $shipping_country !== 'lv' || ! in_array( $shipping_city, $countries_lv_sanitized ) || $base_city !== $shipping_city ) {
			return false;
		}

		if ( $cart_weight_kg > $parcels_delivery_limit_kg ) {
			return false;
		}

		$current_day_name    = strtolower( current_time( 'l' ) );
		$current_time        = current_time( 'H:i' );
		$option_day_name     = $this->get_option( $current_day_name );
		$current_day_enabled = ! empty( $option_day_name ) && $option_day_name == 'yes';
		$am_from             = $this->get_option( $current_day_name . '_am_from', '00:00' );
		$am_to               = $this->get_option( $current_day_name . '_am_to', '12:30' );
		$pm_from             = $this->get_option( $current_day_name . '_pm_from', '15:00' );
		$pm_to               = $this->get_option( $current_day_name . '_pm_to', '23:59' );
		$global_avail        = parent::is_available( $package );

		$am_avail = ( ( $current_time > $am_from ) && ( $current_time < $am_to ) );
		$pm_avail = ( ( $current_time > $pm_from ) && ( $current_time < $pm_to ) );

		if ( $current_day_enabled && $am_avail && $global_avail ) {
			$available = true;
		}

		if ( $current_day_enabled && $pm_avail && $global_avail ) {
			$available = true;
		}

		return $available;
	}

	/**
	 * Zinute kuria rodo priklausomai nuo einamos valandos
	 * Pristatymas siandien
	 * Pristatymas rytoj
	 *
	 * @param $label
	 * @param $method
	 *
	 * @return string
	 */
	public function woocommerce_cart_shipping_method_full_label( $label, $method ) {
		if ( $method->method_id === $this->id && $this->sameDayDeliveryMsg( $method->instance_id ) ) {
			return $label . ' (' . $this->sameDayDeliveryMsg( $method->instance_id ) . ') ';
		}

		return $label;
	}

	private function sameDayDeliveryMsg( $instance_id = 0 ) {
		$this->instance_id = $instance_id;

		$current_day_name = strtolower( current_time( 'l' ) );
		$current_time     = current_time( 'H:i' );
		$am_from          = $this->get_option( $current_day_name . '_am_from', '00:00' );
		$am_to            = $this->get_option( $current_day_name . '_am_to', '12:30' );
		$pm_from          = $this->get_option( $current_day_name . '_pm_from', '15:00' );
		$pm_to            = $this->get_option( $current_day_name . '_pm_to', '23:59' );

		$am = ( ( $current_time > $am_from ) && ( $current_time < $am_to ) );
		$pm = ( ( $current_time > $pm_from ) && ( $current_time < $pm_to ) );

		if ( $am ) {
			return $this->get_option( 'delivery_same_day_text' );
		}

		if ( $pm ) {
			return $this->get_option( 'delivery_next_day_text' );
		}

		return false;
	}

	function review_order_after_shipping() {
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( ! empty( $chosen_shipping_methods ) && substr( $chosen_shipping_methods[0], 0, strlen( $this->id ) ) === $this->id ) {
			$selected_terminal = WC()->session->get( $this->terminal_field_name );

			$template_data = array(
				'terminals'  => $this->get_grouped_terminals( $this->get_terminals( WC()->customer->get_shipping_country() ) ),
				'field_name' => $this->terminal_field_name,
				'field_id'   => $this->terminal_field_name,
				'selected'   => $selected_terminal ? $selected_terminal : ''
			);

			do_action( $this->id . '_before_terminals' );

			$google_map_api = get_option( 'dpd_google_map_key' );

			if ( $google_map_api != '' ) {
				$template_data['selected_name'] = $this->get_terminal_name( $selected_terminal );

				wc_get_template( 'checkout/form-shipping-dpd-terminals-with-map.php', $template_data );
			} else {
				wc_get_template( 'checkout/form-shipping-dpd-terminals.php', $template_data );
			}

			do_action( $this->id . '_after_terminals' );
		}
	}

	function checkout_save_order_terminal( $order_id, $data ) {
		if ( isset( $_POST[ $this->terminal_field_name ] ) ) {
			update_post_meta( $order_id, $this->terminal_field_name, $_POST[ $this->terminal_field_name ] );
		}
	}

	public function validate_selected_terminal( $posted, $errors ) {
		// Check if terminal was submitted
		if ( isset( $_POST[ $this->terminal_field_name ] ) && $_POST[ $this->terminal_field_name ] == '' ) {
			// Be sure shipping method was posted
			if ( isset( $posted['shipping_method'] ) && is_array( $posted['shipping_method'] ) ) {
				// Check if it is this shipping method
				if ( substr( $posted['shipping_method'][0], 0, strlen( $this->id ) ) === $this->id ) {
					$errors->add( 'shipping', __( 'Please select a Pickup Point', 'woo-shipping-dpd-baltic' ) );
				}
			}
		}
	}

	function show_selected_terminal( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {
			$terminal_id   = $this->get_order_terminal( $order->get_id() );
			$terminal_name = $this->get_terminal_name( $terminal_id );

			$terminal = '<div class="selected_terminal">';
			$terminal .= '<div><strong>' . $this->i18n_selected_terminal . '</strong></div>';
			$terminal .= esc_html( $terminal_name );
			$terminal .= '</div>';

			echo $terminal;
		}
	}

	function show_selected_terminal_in_order_preview( $order_details, $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {
			$terminal_id   = $this->get_order_terminal( $order->get_id() );
			$terminal_name = $this->get_terminal_name( $terminal_id );

			if ( isset( $order_details['shipping_via'] ) ) {
				$order_details['shipping_via'] = sprintf( '%s: %s', $order->get_shipping_method(), esc_html( $terminal_name ) );
			}
		}

		return $order_details;
	}

	function show_selected_terminal_in_order_details( $total_rows, $order, $tax_display ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->has_shipping_method( $this->id ) ) {
			$terminal_id   = $this->get_order_terminal( $order->get_id() );
			$terminal_name = $this->get_terminal_name( $terminal_id );

//			$total_rows['shipping_terminal'] = [
//				'label' => $this->i18n_selected_terminal,
//				'value' => $terminal_name
//			];

			$this->array_insert( $total_rows, 'shipping', [
				'shipping_terminal' => [
					'label' => $this->i18n_selected_terminal,
					'value' => $terminal_name
				]
			] );
		}

		return $total_rows;
	}

	public function get_grouped_terminals( $terminals ) {
//		usort( $terminals, function ( $a, $b ) {
//			strcmp( $a->company, $b->company );
//		} );

		$grouped_terminals = [];

		foreach ( $terminals as $terminal ) {
			if ( ! isset( $grouped_terminals[ $terminal->city ] ) ) {
				$grouped_terminals[ $terminal->city ] = [];
			}

			$grouped_terminals[ $terminal->city ][] = $terminal;
		}

		ksort( $grouped_terminals );

		foreach ( $grouped_terminals as $group => $terminals ) {
			foreach ( $terminals as $terminal_key => $terminal ) {
				$grouped_terminals[ $group ][ $terminal_key ]->name = $this->get_formatted_terminal_name( $terminal );
			}
		}

		return $grouped_terminals;
	}

	public function get_terminals( $country = false ) {
		global $wpdb;

		$shipping_country = '';

		if ( $country ) {
			$shipping_country = "WHERE country = '{$country}'";
		}

		$terminals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_terminals {$shipping_country} ORDER BY city" );

		return $terminals;
	}

	function get_order_terminal( $order_id ) {
		return get_post_meta( $order_id, $this->terminal_field_name, true );
	}

	function get_terminal_name( $terminal_id ) {
		$terminals = $this->get_terminals();

		foreach ( $terminals as $terminal ) {
			if ( $terminal->parcelshop_id == $terminal_id ) {
				return $this->get_formatted_terminal_name( $terminal );

				break;
			}
		}

		return false;
	}

	function get_formatted_terminal_name( $terminal ) {
		return $terminal->company . ', ' . $terminal->street;
	}

	private function array_insert( &$array, $position, $insert ) {
		if ( is_int( $position ) ) {
			array_splice( $array, $position, 0, $insert );
		} else {
			$pos   = array_search( $position, array_keys( $array ) );
			$array = array_merge(
				array_slice( $array, 0, $pos + 1 ),
				$insert,
				array_slice( $array, $pos + 1 )
			);
		}
	}
}