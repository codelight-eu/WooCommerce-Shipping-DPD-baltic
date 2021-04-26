<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://dpd.com
 * @since      1.0.0
 *
 * @package    Dpd
 * @subpackage Dpd/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Dpd
 * @subpackage Dpd/admin
 * @author     DPD
 */
class Dpd_Baltic_Admin {

	const HOME = 'dpd_home_delivery';
	const HOME_SAT = 'dpd_sat_home_delivery';
	const PARCELS = 'dpd_parcels';
	const PARCELS_SAME_DAY = 'dpd_sameday_parcels';
	const SAME_DAY = 'dpd_sameday_delivery';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	private static $version_static;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name    = $plugin_name;
		$this->version        = $version;
		self::$version_static = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dpd-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'thickbox' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script( 'repeater', plugin_dir_url( __FILE__ ) . 'js/jquery.repeater.min.js', [], $this->version, true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dpd-admin-dist.js', [
			'jquery',
			'jquery-ui-datepicker',
			'repeater'
		], $this->version, true );
		wp_localize_script( $this->plugin_name, 'wc_dpd_baltic', [
			'i18n' => [
				'request_courier' => __( 'Request DPD courier', 'woo-shipping-dpd-baltic' ),
				'close_manifest' => __( 'Close DPD manifest', 'woo-shipping-dpd-baltic' )
			]
		] );

	}

	public function get_settings_pages( $settings ) {
		$settings[] = include 'settings/class-dpd-settings.php';

		return $settings;
	}

	public static function http_client( $endpoint, $params = [] ) {
		$service_provider = get_option( 'dpd_api_service_provider' );
		$test_mode        = get_option( 'dpd_test_mode' );

		switch ( $service_provider ) {
			case 'lt':
				$service_url      = 'https://integracijos.dpd.lt/';
				$test_service_url = 'https://lt.integration.dpd.eo.pl/';
				break;
			case 'lv':
				$service_url      = 'https://integration.dpd.lv:8443/';
				$test_service_url = 'https://lv.integration.dpd.eo.pl/';
				break;
			case 'ee':
				$service_url      = 'https://integration.dpd.ee:8443/';
				$test_service_url = 'https://ee.integration.dpd.eo.pl/';
				break;
			default:
				$service_url      = 'https://integracijos.dpd.lt/';
				$test_service_url = 'https://lt.integration.dpd.eo.pl/';
		}

		$service_url      .= 'ws-mapper-rest/';
		$test_service_url .= 'ws-mapper-rest/';

		$dpd_service_url = ! empty( $test_mode ) && $test_mode == 'yes' ? $test_service_url : $service_url;
		$dpd_username    = get_option( 'dpd_api_username' );
		$dpd_pass        = get_option( 'dpd_api_password' );

		$params['PluginVersion'] = self::$version_static;
		$params['EshopVersion']  = 'WordPress ' . get_bloginfo( 'version' ) . ', WooCommerce ' . WC()->version;

		if ( $dpd_service_url && $dpd_username && $dpd_pass ) {
			$response = wp_remote_post( $dpd_service_url . $endpoint . '?username=' . $dpd_username . '&password=' . $dpd_pass . '&' . http_build_query( $params ), array(
					'method'      => 'POST',
					'timeout'     => 60,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(
						'Content-Type' => 'application/x-www-form-urlencoded'
					),
					'body'        => array(),
					'cookies'     => array(),
					'sslverify'   => false
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response->get_error_message();
			} else {
				$body = wp_remote_retrieve_body( $response );

				if ( $endpoint == 'parcelPrint_' || $endpoint == 'parcelManifestPrint_' || $endpoint == 'crImport_' ) {
					return $body;
				}

				if ( $endpoint == 'pickupOrderSave_' ) {
					if ( strcmp( substr( $body, 3, 4 ), 'DONE' ) == 0 ) {
						return 'DONE|';
					} else {
						return $body;
					}
				}

				return json_decode( $body );
			}
		}
	}

	public static function get_all_parcels_list() {
		global $wpdb;

		$countries = get_option( 'dpd_parcels_countries', [ 'LT', 'LV', 'EE' ] );

		if ( ! empty( $countries ) ) {
		    $i = 0;
		    $time = time();

            foreach ( $countries as $country ) {
                wp_schedule_single_event( $time + ( $i * 15 ), 'dpd_parcels_country_update', [ $country ] );

                if ( $i == 0 ) {
                    $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}dpd_terminals" );
                }

                $i++;
            }
        }
	}

    public static function country_parcels_list( $country ) {
	    if ( (int) ini_get( 'max_execution_time' ) < 60 && in_array( $country, [ 'DE', 'FR' ] ) ) {
	        $data = self::get_parcels_list( $country, false );
        } else {
            $data = self::get_parcels_list( $country );
        }

        if ( $data ) {
            self::update_parcels_list( $data );
        }
    }

	public function order_actions_metabox_dpd( $order_id ) {
		global $woocommerce;

		$order = wc_get_order( $order_id );

		$warehouses     = $this->get_option_like( 'warehouses' );
		$warehouses_arr = [];

		foreach ( $warehouses as $warehouse ) {
			$warehouses_arr[ $warehouse['option_name'] ] = $warehouse['option_value']['name'];
		}

		echo '<li class="wide">';

		if ( ! $this->get_order_barcode( $order_id ) ) {

			if ( ( $order->has_shipping_method( self::HOME ) || $order->has_shipping_method( self::HOME_SAT ) || $order->has_shipping_method( self::SAME_DAY ) ) ) {

				if ( get_option( 'dpd_rod_service' ) === 'yes' ) {

					woocommerce_wp_checkbox( array(
						'id'          => 'dpd_shipping_return',
						'label'       => '',
						'placeholder' => '',
						'description' => __( 'Activate document return service?', 'woo-shipping-dpd-baltic' ),
						'cbvalue'     => 'yes'
					) );

					woocommerce_wp_text_input( array(
						'id'          => 'dpd_shipping_note',
						'label'       => __( 'Document reference number', 'woo-shipping-dpd-baltic' ) . ' *',
						'placeholder' => '',
						'description' => ''
					) );

					$js = "
							jQuery('input#dpd_shipping_return').change(function() {
								if ( jQuery(this).prop('checked') ) {
									jQuery('p.dpd_shipping_note_field').show();
								} else {
									jQuery('p.dpd_shipping_note_field').hide();
								}
							}).change();";

					if ( function_exists( 'wc_enqueue_js' ) ) {
						wc_enqueue_js( $js );
					} else {
						$woocommerce->add_inline_js( $js );
					}

				}

			}

		}

		if ( $order->is_paid() && ! ( $order->has_shipping_method( self::PARCELS ) || $order->has_shipping_method( self::PARCELS_SAME_DAY ) ) && $this->get_order_barcode( $order_id ) ) {

			woocommerce_wp_select( array(
				'id'          => 'dpd_warehouse',
				'label'       => __( 'Select warehouse:', 'woo-shipping-dpd-baltic' ),
				'placeholder' => '',
				'description' => '',
				'options'     => $warehouses_arr
			) );

		}

		echo '</li>';
	}

	public function save_order_actions_meta_box( $post_id, $post ) {
		if ( ! $this->get_order_barcode( $post_id ) ) {
			$order = wc_get_order( $post_id );

			if ( ( $order->has_shipping_method( self::HOME ) || $order->has_shipping_method( self::HOME_SAT ) || $order->has_shipping_method( self::SAME_DAY ) ) && get_option( 'dpd_rod_service' ) === 'yes' ) {
				if ( isset( $_POST['dpd_shipping_return'] ) && $_POST['dpd_shipping_return'] == 'yes' ) {
					update_post_meta( $post_id, 'dpd_shipping_return', 'yes' );

					if ( isset( $_POST['dpd_shipping_note'] ) ) {
						update_post_meta( $post_id, 'dpd_shipping_note', wc_clean( $_POST['dpd_shipping_note'] ) );
					}
				} else {
					update_post_meta( $post_id, 'dpd_shipping_return', 'no' );
					update_post_meta( $post_id, 'dpd_shipping_note', '' );
				}
			}
		}
	}

	/**
	 * Callback for woocommerce_order_actions
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function add_order_actions( $actions ) {
		global $theorder;

		if ( ! $theorder->is_paid() || ! ( $theorder->has_shipping_method( self::HOME ) || $theorder->has_shipping_method( self::HOME_SAT ) || $theorder->has_shipping_method( self::PARCELS ) || $theorder->has_shipping_method( self::PARCELS_SAME_DAY ) || $theorder->has_shipping_method( self::SAME_DAY ) ) ) {
			return $actions;
		}

		$actions['dpd_print_parcel_label'] = __( 'Print DPD label', 'woo-shipping-dpd-baltic' );

		if ( $this->get_order_barcode( $theorder->get_id() ) ) {
			$actions['dpd_cancel_shipment']    = __( 'Cancel DPD shipment', 'woo-shipping-dpd-baltic' );
			$actions['dpd_parcel_status']      = __( 'Get last parcel status', 'woo-shipping-dpd-baltic' );

			if ( ! ( $theorder->has_shipping_method( self::PARCELS ) || $theorder->has_shipping_method( self::PARCELS_SAME_DAY ) ) ) {
				$actions['dpd_collection_request'] = __( 'Collection request to return from customer', 'woo-shipping-dpd-baltic' );
			}
		}

		return $actions;
	}

	public function define_orders_bulk_actions( $actions ) {
		$actions['dpd_print_parcel_label'] = __( 'Print DPD label', 'woo-shipping-dpd-baltic' );
		$actions['dpd_cancel_shipment'] = __( 'Cancel DPD shipments', 'woo-shipping-dpd-baltic' );

		return $actions;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action Action name.
	 * @param  array $ids List of ids.
	 *
	 * @return string
	 */
	public function handle_orders_bulk_actions( $redirect_to, $action, $ids ) {
		$ids     = array_map( 'absint', $ids );
		$changed = 0;

		if ( 'dpd_print_parcel_label' === $action ) {
			$report_action = 'dpd_printed_parcel_label';
			$result = $this->do_multiple_print_parcel_label( $ids );
			$changed = ( $result == null ) ? -1 : count( $ids );
		} elseif ( 'dpd_cancel_shipment' === $action ) {
			$report_action = 'dpd_canceled_shipment';

			foreach ( $ids as $id ) {
				$order = wc_get_order( $id );

				if ( $order ) {
					$this->do_cancel_shipment( $order );
					$changed ++;
				}
			}
		}

		if ( $changed ) {
			$redirect_to = add_query_arg(
				array(
					'post_type'   => 'shop_order',
					'bulk_action' => $report_action,
					'changed'     => $changed,
					'ids'         => join( ',', $ids ),
				), $redirect_to
			);
		}

		return esc_url_raw( $redirect_to );
	}

	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop order list page.
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type || ! isset( $_REQUEST['bulk_action'] ) ) { // WPCS: input var ok, CSRF ok.
			return;
		}

		$number      = isset( $_REQUEST['changed'] ) ? intval( $_REQUEST['changed'] ) : 0; // WPCS: input var ok, CSRF ok.
		$bulk_action = wc_clean( wp_unslash( $_REQUEST['bulk_action'] ) ); // WPCS: input var ok, CSRF ok.
		$message     = '';

		if ( 'dpd_printed_parcel_label' === $bulk_action ) { // WPCS: input var ok, CSRF ok.
		    if ( $number == -1 ) {
                $message = __( 'Cannot print DPD labels for these orders, because some of orders parcel is not found.', 'woo-shipping-dpd-baltic' );
            } else {
                $message = sprintf( _n( 'DPD label printed for %d order.', 'DPD labels printed for %d orders.', $number, 'woo-shipping-dpd-baltic' ), number_format_i18n( $number ) );
            }
		}

		if ( 'dpd_canceled_shipment' === $bulk_action ) { // WPCS: input var ok, CSRF ok.
			$message = sprintf( _n( 'DPD shipment cancelled for %d order.', 'DPD shipments cancelled for %d orders.', $number, 'woo-shipping-dpd-baltic' ), number_format_i18n( $number ) );
		}

		if ( ! empty( $message ) ) {
			echo '<div class="updated"><p>' . wp_kses( $message, [
					'a' => [
						'href'  => [],
						'title' => [],
					],
				] ) . '</p></div>';
		}
	}

	/**
	 * Callback for woocommerce_order_action_dpd_print_parcel_label
	 *
	 * @param \WC_Order $order
	 */
	public function do_print_parcel_label( $order ) {
		$shipments        = $this->order_shipment_creation( [ $order ] );
		$tracking_numbers = '';

		foreach ( $shipments as $order_id => $data ) {
			if ( $data['status'] == 'err' ) {
//				$error_message .= __('Error in order: ', 'woo-shipping-dpd-baltic') . $order_id . '. <strong>' . $data['errlog'] . '</strong><br />';
			} elseif ( $data['status'] == 'ok' ) {
				foreach ( $data['barcodes'] as $barcode ) {
					$tracking_numbers .= $barcode->dpd_barcode . '|';
				}
			}
		}

		if ( $tracking_numbers ) {
		    $result = $this->print_order_parcel_label( $tracking_numbers );

		    if ( $result == null ) {
                $order->add_order_note( __( 'Cannot print DPD label: Parcel not found', 'woo-shipping-dpd-baltic' ) );
            }
		}
	}

	public function do_multiple_print_parcel_label( $ids ) {
		$tracking_numbers = '';

		foreach ( $ids as $id ) {
			$order = wc_get_order( $id );

			if ( $order ) {
				$shipments = $this->order_shipment_creation( [ $order ] );

				foreach ( $shipments as $order_id => $data ) {
					if ( $data['status'] == 'err' ) {
//				$error_message .= __('Error in order: ', 'woo-shipping-dpd-baltic') . $order_id . '. <strong>' . $data['errlog'] . '</strong><br />';
					} elseif ( $data['status'] == 'ok' ) {
						foreach ( $data['barcodes'] as $barcode ) {
							$tracking_numbers .= $barcode->dpd_barcode . '|';
						}
					}
				}
			}
		}

		if ( $tracking_numbers ) {
            $this->print_order_parcel_label( $tracking_numbers );
		}

		return null;
	}

	public function do_cancel_shipment( $order ) {
		$order_barcodes   = $this->get_order_barcode( $order->get_id() );
		$tracking_numbers = '';

		foreach ( $order_barcodes as $barcode ) {
			$tracking_numbers .= $barcode->dpd_barcode . '|';
		}

		if ( $tracking_numbers ) {
			$response = self::http_client( 'parcelDelete_', [
				'parcels' => $tracking_numbers
			] );

			if ( $response && $response->status == 'ok' ) {
				$this->delete_order_barcode( $order->get_id() );

				$order->add_order_note( __( 'The DPD shipment was cancelled for this order!', 'woo-shipping-dpd-baltic' ) );
			} elseif ( $response && $response->status == 'err' ) {
				$order->add_order_note( $response->errlog );
			}
		}
	}

	public function do_get_parcel_status( $order ) {
		$order_barcodes = $this->get_order_barcode( $order->get_id() );

		if ( $order_barcodes ) {
			foreach ( $order_barcodes as $barcode ) {
				$response = self::http_client( 'parcelStatus_', [
					'parcel_number' => $barcode->dpd_barcode
				] );

				if ( $response && $response->status == 'ok' ) {
					if ( $response->parcel_status != '' ) {
						if ( $response->parcel_status == 'Pickup scan' ) {
							$status = __( 'Parcel has been picked up', 'woo-shipping-dpd-baltic' );
						} elseif ( $response->parcel_status == 'HUB-scan' ) {
							$status = __( 'Parcel is at parcel delivery centre', 'woo-shipping-dpd-baltic' );
						} elseif ( $response->parcel_status == 'Out for delivery' ) {
							$status = __( 'Parcel is out for delivery', 'woo-shipping-dpd-baltic' );
						} elseif ( $response->parcel_status == 'Infoscan' ) {
							$status = __( 'Additional information added', 'woo-shipping-dpd-baltic' );
						} elseif ( $response->parcel_status == 'Delivered' ) {
							$status = __( 'Parcel is successfully delivered', 'woo-shipping-dpd-baltic' );
						} elseif ( $response->parcel_status == 'Delivery obstacle' ) {
							$status = __( 'Delivery obstacle', 'woo-shipping-dpd-baltic' );
						} else {
							$status = __( 'The parcel is not scanned by DPD yet', 'woo-shipping-dpd-baltic' );
						}

						$order->add_order_note( $barcode->dpd_barcode . ' ' . $status . '. <a href="https://tracking.dpd.de/status/en_US/parcel/' . $barcode->dpd_barcode . '" target="_blank">' . __( 'Track parcel', 'woo-shipping-dpd-baltic' ) . '</a>' );
					} else {
						$order->add_order_note( $barcode->dpd_barcode . ' ' . __( 'The parcel is not scanned by DPD yet', 'woo-shipping-dpd-baltic' ) . '. <a href="https://tracking.dpd.de/status/en_US/parcel/' . $barcode->dpd_barcode . '" target="_blank">' . __( 'Track parcel', 'woo-shipping-dpd-baltic' ) . '</a>' );
					}
				} elseif ( $response && $response->status == 'err' ) {
					$order->add_order_note( $response->errlog );
				}
			}
		} else {
			$order->add_order_note( 'Parcel number not found' );
		}
	}

	private function order_shipment_creation( $orders = [] ) {
	    global $wpdb;

		$tracking_barcodes = [];

		if ( get_option( 'dpd_return_labels' ) === 'yes' ) {
			$pickup_parcel_type     = 'PS-RETURN';
			$pickup_cod_parcel_type = 'PS-COD-RETURN';

			$pickup_same_parcel_type     = '274-RETURN';
			$pickup_same_cod_parcel_type = '274-COD-RETURN';

			$courier_parcel_type     = 'D-B2C-RETURN';
			$courier_cod_parcel_type = 'D-B2C-COD-RETURN';

			$courier_parcel_rod_type     = 'D-B2C-DOCRET-RETURN';
			$courier_cod_parcel_rod_type = 'D-COD-B2C-DOCRET-RETURN';

			// Saturday services
			$courier_sat_parcel_type     = 'D-B2C-SAT-RETURN';
			$courier_sat_cod_parcel_type = 'D-B2C-SAT-COD-RETURN';

			$courier_sat_parcel_rod_type     = 'D-B2C-SAT-DOCRET-RETURN'; // @TODO: is this right? D-B2C-DOCRET-SAT-RETURN
			$courier_cod_sat_parcel_rod_type = 'D-COD-B2C-SAT-DOCRET-RETURN'; // @TODO: is this right? D-COD-B2C-DOCRET-SAT-RETURN

			$courier_same_parcel_type     = 'SD-RETURN';
			$courier_same_cod_parcel_type = 'SD-COD-RETURN';
		} else {
			$pickup_parcel_type     = 'PS';
			$pickup_cod_parcel_type = 'PS-COD';

			$pickup_same_parcel_type     = '274';
			$pickup_same_cod_parcel_type = '274-COD';

			$courier_parcel_type     = 'D-B2C';
			$courier_cod_parcel_type = 'D-B2C-COD';

			$courier_parcel_rod_type     = 'D-B2C-DOCRET';
			$courier_cod_parcel_rod_type = 'D-COD-B2C-DOCRET';

			// Saturday services
			$courier_sat_parcel_type     = 'D-B2C-SAT';
			$courier_sat_cod_parcel_type = 'D-B2C-SAT-COD';

			$courier_sat_parcel_rod_type     = 'D-B2C-SAT-DOCRET'; // @TODO: is this right? D-B2C-DOCRET-SAT
			$courier_cod_sat_parcel_rod_type = 'D-COD-B2C-SAT-DOCRET'; // @TODO: is this right? D-COD-B2C-DOCRET-SAT

			$courier_same_parcel_type     = 'SD';
			$courier_same_cod_parcel_type = 'SD-COD';

			// If in options selected country is lituanian these need to change
			if ( get_option( 'dpd_api_service_provider' ) === 'lt' ){
				$courier_same_parcel_type     = 'SDB2C';
				$courier_same_cod_parcel_type = 'SDB2C-COD';
				}
		}

		foreach ( $orders as $order ) {
			$order_id = $order->get_id();
			$products = $order->get_items();

			// Fixing params for DPD
			$name1  = $this->custom_length( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(), 40 ); // required 1, max length 40
			$name2  = $this->custom_length( $order->get_shipping_company(), 40 ); // required 1, max length 40
			$street = $this->custom_length( $order->get_shipping_address_1(), 40 ); // required 1, max length 40
			$city   = $this->custom_length( $order->get_shipping_city(), 40 ); // required 1, max length 40

			$country_code = $order->get_shipping_country();
			if ( strtoupper( $country_code ) == 'LT' || strtoupper( $country_code ) == 'LV' || strtoupper( $country_code ) == 'EE' ) {
				$pcode = preg_replace( '/[^0-9,.]/', '', $order->get_shipping_postcode() );
			} else {
				$pcode = $order->get_shipping_postcode();
			}

			$dial_code_helper = new Dpd_Baltic_Dial_Code_Helper();
			$correct_phone    = $dial_code_helper->separatePhoneNumberFromCountryCode( $order->get_billing_phone(), $country_code );
			$phone            = $correct_phone['dial_code'] . $correct_phone['phone_number'];

			$email           = $order->get_billing_email();
			$order_comment   = $this->custom_length( $order->get_customer_note(), 40 ); // required 0, max length 40
			$shipping_labels = get_post_meta( $order_id, 'dpd_shipping_labels', true );
			$num_of_parcel   = $shipping_labels ? $shipping_labels : 0;

			// If documents should be return
			$shipping_return = get_post_meta( $order_id, 'dpd_shipping_return', true );
			$shipping_note   = get_post_meta( $order_id, 'dpd_shipping_note', true );

			if ( $order->has_shipping_method( self::HOME ) || $order->has_shipping_method( self::HOME_SAT ) || $order->has_shipping_method( self::PARCELS ) || $order->has_shipping_method( self::PARCELS_SAME_DAY ) || $order->has_shipping_method( self::SAME_DAY ) ) {

				$order_barcode = $this->get_order_barcode( $order_id );

				if ( ! $order_barcode ) {
					$shop_weight_unit         = get_option( 'woocommerce_weight_unit' );
					$product_weight           = 0;
					$total_order_quantity     = 0;
					$total_different_products = 0;

					if ( $shop_weight_unit === 'oz' ) {
						$divider = 35.274;
					} elseif ( $shop_weight_unit === 'lbs' ) {
						$divider = 2.20462;
					} elseif ( $shop_weight_unit === 'g' ) {
						$divider = 1000;
					} else {
						$divider = 1;
					}

					foreach ( $products as $product ) {
						$product_data              = $product->get_product();
						$product_weight           += ($product_data->get_weight() / $divider) * $product->get_quantity();
						$total_order_quantity     += $product->get_quantity();
						$total_different_products += 1;
					}

					// How many labels print
					$labels_setting = get_option( 'dpd_parcel_distribution' );

					if ( $num_of_parcel == 0 ) { // was 1
						// All products in same parcel
						if ( $labels_setting == 1 ) {
							$num_of_parcel = 1;
							// Each product in seperate shipment
						} else if ( $labels_setting == 2 ) {
							$num_of_parcel = $total_different_products;
							// Each product quantity as separate parcel
						} else if ( $labels_setting == 3 ) {
							$num_of_parcel = $total_order_quantity;
						} else {
							$num_of_parcel = 1;
						}
					} else if ( $num_of_parcel > 1 ) {

					} else {
						$num_of_parcel = 1;
					}

					$params = [
						'name1'            => $name1,
						'name2'            => $name2,
						'street'           => $street,
						'city'             => $city,
						'country'          => $country_code,
						'pcode'            => $pcode,
						'num_of_parcel'    => $num_of_parcel,
						'weight'           => round( $product_weight / $num_of_parcel, 3 ),
						'phone'            => $phone,
						'idm_sms_number'   => $phone,
						'email'            => $email,
						'order_number'     => 'DPD #' . $order->get_order_number(),
						'fetchGsPUDOpoint' => 1
					];

					// Home delivery
					if ( $order->has_shipping_method( self::HOME ) ) {
						$params['remark'] = $order_comment;

						// If ROD services used
						if ( $shipping_return == 'yes' ) {
							$params['parcel_type']     = $courier_parcel_rod_type;
							$params['dnote_reference'] = $shipping_note;
						} else {
							$params['parcel_type'] = $courier_parcel_type;
						}

						// If order is COD
						if ( $order->get_payment_method() == 'cod' ) {
							$params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

							if ( $shipping_return == 1 ) {
								$params['parcel_type'] = $courier_cod_parcel_rod_type;
							} else {
								$params['parcel_type'] = $courier_cod_parcel_type;
							}
						}

						// Time frame
						$shipping_timeframe = get_post_meta( $order_id, 'wc_shipping_' . self::HOME . '_shifts', true );

						if ( $shipping_timeframe && ! empty( $shipping_timeframe ) ) {
							$shipping_timeframe = explode( ' - ', $shipping_timeframe );

							$params['timeframe_from'] = $shipping_timeframe[0];
							$params['timeframe_to']   = $shipping_timeframe[1];
						}
					}

					// Home delivery saturday
					if ( $order->has_shipping_method( self::HOME_SAT ) ) {
						$params['remark'] = $order_comment;

						// If ROD services used
						if ( $shipping_return == 'yes' ) {
							$params['parcel_type']     = $courier_sat_parcel_rod_type;
							$params['dnote_reference'] = $shipping_note;
						} else {
							$params['parcel_type'] = $courier_sat_parcel_type;
						}

						// If order is COD
						if ( $order->get_payment_method() == 'cod' ) {
							$params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

							if ( $shipping_return == 1 ) {
								$params['parcel_type'] = $courier_cod_sat_parcel_rod_type;
							} else {
								$params['parcel_type'] = $courier_sat_cod_parcel_type;
							}
						}
					}

					// Same day delivery
					if ( $order->has_shipping_method( self::SAME_DAY ) ) {
						$params['remark'] = $order_comment;

						$params['parcel_type'] = $courier_same_parcel_type;

						// If order is COD
						if ( $order->get_payment_method() == 'cod' ) {
							$params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

							$params['parcel_type'] = $courier_same_cod_parcel_type;
						}
					}

					// Parcelshop services
					if ( $order->has_shipping_method( self::PARCELS ) ) {
					    $parcel_shop_id = get_post_meta( $order_id, 'wc_shipping_' . self::PARCELS . '_terminal', true );
                        $terminal = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE parcelshop_id = '{$parcel_shop_id}'" );

						$params['city']          = $terminal->city;
						$params['country']       = $terminal->country;
						$params['pcode']         = $terminal->pcode;
						$params['street']        = $terminal->street;
						$params['parcel_type']   = $pickup_parcel_type;
						$params['parcelshop_id'] = $parcel_shop_id;

						// If order is COD
						if ( $order->get_payment_method() == 'cod' ) {
							$params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

							$params['parcel_type'] = $pickup_cod_parcel_type;
						}
					}

					// Parcelshop same day services
					if ( $order->has_shipping_method( self::PARCELS_SAME_DAY ) ) {
                        $parcel_shop_id = get_post_meta( $order_id, 'wc_shipping_' . self::PARCELS_SAME_DAY . '_terminal', true );
                        $terminal = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}dpd_terminals WHERE parcelshop_id = '{$parcel_shop_id}'" );

                        $params['city']          = $terminal->city;
                        $params['country']       = $terminal->country;
                        $params['pcode']         = $terminal->pcode;
                        $params['street']        = $terminal->street;
						$params['parcel_type']   = $pickup_same_parcel_type;
						$params['parcelshop_id'] = $parcel_shop_id;

						// If order is COD
						if ( $order->get_payment_method() == 'cod' ) {
							$params['cod_amount'] = number_format( $order->get_total(), 2, '.', '' );

							$params['parcel_type'] = $pickup_same_cod_parcel_type;
						}
					}

					$response = self::http_client( 'createShipment_', $params );

					if ( $response && $response->status == 'ok' ) {
						$tracking_barcodes[ $order_id ]['status']   = 'ok';
						$tracking_barcodes[ $order_id ]['barcodes'] = $response->pl_number;

						if ( $response->pl_number ) {
							foreach ( $response->pl_number as $number ) {
								$this->set_order_barcode( $order_id, $number, $order );
							}

							$tracking_barcodes[ $order_id ]['status']   = 'ok';
							$tracking_barcodes[ $order_id ]['barcodes'] = $this->get_order_barcode( $order_id );
						}
					} elseif ( $response && $response->status == 'err' ) {
						$tracking_barcodes[ $order_id ]['status'] = 'err';
						$tracking_barcodes[ $order_id ]['errlog'] = $response->errlog;

						$order->add_order_note( $response->errlog );
					}
				} else {
					$tracking_barcodes[ $order_id ]['status']   = 'ok';
					$tracking_barcodes[ $order_id ]['barcodes'] = $order_barcode;
				}

			} else {
				$tracking_barcodes[ $order_id ]['status'] = 'err';
				$tracking_barcodes[ $order_id ]['errlog'] = __( 'Shipping method is not DPD', 'woo-shipping-dpd-baltic' );
			}
		}

		return $tracking_barcodes;
	}

	private function print_order_parcel_label( $tracking_number = null ) {
		$label_size = get_option( 'dpd_label_size' );

		$response = self::http_client( 'parcelPrint_', [
			'parcels'     => $tracking_number,
			'printType'   => 'PDF',
			'printFormat' => $label_size ? $label_size : 'A4'
		] );

		$json_response = json_decode( $response );

		if ( $json_response && $json_response->status == 'err' ) {
			return null;
		} else {
			$this->get_labels_output( $response );
		}
	}

	private function get_labels_output( $pdf, $file_name = 'dpdLabels' ) {
		$name = $file_name . '-' . date( 'Y-m-d' ) . '.pdf';

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Connection: Keep-Alive' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );

		echo $pdf;

		die;
	}

	private function get_order_barcode( $order_id ) {
		global $wpdb;

		if ( $order_id ) {
			return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_barcodes WHERE order_id = {$order_id}" );
		}

		return null;
	}

	private function set_order_barcode( $order_id, $barcode, $order ) {
		global $wpdb;

		if ( $order_id && $barcode ) {
			$wpdb->insert( $wpdb->prefix . 'dpd_barcodes', [
				'order_id'    => $order_id,
				'dpd_barcode' => $barcode
			] );

			$message = sprintf( __( 'DPD Tracking number: %s', 'woo-shipping-dpd-baltic' ), $barcode );
			$order->add_order_note( $message, false, true );
		}
	}

	private function delete_order_barcode( $order_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'dpd_barcodes', [
			'order_id' => $order_id
		] );
	}

	private static function get_parcels_list( $country = 'LT', $opening_hours = true ) {
		$parcel_data = [];
		$parcels     = self::http_client( 'parcelShopSearch_', [
			'country'              => $country,
			'fetchGsPUDOpoint'     => 1,
			'retrieveOpeningHours' => $opening_hours ? 1 : 0,
		] );

		$cod_pudo = [];
		$lenght   = 3;

		switch ( $country ) {
			case 'LT':
				$cod_pudo = [ 'LT900' ];
				$lenght   = 5;
				break;

			case 'LV':
				$cod_pudo = [];
				break;

			case 'EE':
				$cod_pudo = [ 'EE90', 'EE10' ];
				$lenght   = 4;
				break;
		}

		if ( $parcels && $parcels->status == 'ok' ) {
			foreach ( $parcels->parcelshops as $parcelshop ) {
				$parcel_id     = substr( $parcelshop->parcelshop_id, 0, $lenght );
				$cod_available = 0;

				if ( in_array( $parcel_id, $cod_pudo ) ) {
					$cod_available = 1;
				}

				$data = [
					'parcelshop_id' => $parcelshop->parcelshop_id,
					'company'       => $parcelshop->company,
					'country'       => $parcelshop->country,
					'city'          => $parcelshop->city,
					'pcode'         => $parcelshop->pcode,
					'street'        => $parcelshop->street,
					'email'         => $parcelshop->email,
					'phone'         => $parcelshop->phone,
					'distance'      => $parcelshop->distance,
					'longitude'     => $parcelshop->longitude,
					'latitude'      => $parcelshop->latitude,
					'cod'           => $cod_available,
				];

				if ( isset( $parcelshop->openingHours ) ) {
                    foreach ( $parcelshop->openingHours as $day ) {
                        $morning   = $day->openMorning . '-' . $day->closeMorning;
                        $afternoon = $day->openAfternoon . '-' . $day->closeAfternoon;

                        $data[ strtolower( $day->weekday ) ] = $morning . '|' . $afternoon;
                    }
                }

				$parcel_data[] = $data;
			}
		}

		return $parcel_data;
	}

	private static function update_parcels_list( $data = [] ) {
		global $wpdb;

		foreach ( $data as $parcelshop ) {
			$wpdb->insert( $wpdb->prefix . 'dpd_terminals', $parcelshop );
		}
	}

	private function custom_length( $string, $length ) {
		if ( strlen( $string ) <= $length ) {
			return $string;
		} else {
			return substr( $string, 0, $length );
		}
	}

	/**
	 * Renders werehouses repeater
	 */
	public function settings_dpd_warehouses() {
		$warehouses    = $this->get_option_like( 'warehouses' );
		$countries_obj = new WC_Countries();
		$countries     = $countries_obj->__get( 'countries' );

		ob_start();
		require_once plugin_dir_path( __FILE__ ) . 'partials/dpd-admin-warehouses-display.php';
		$output = ob_get_clean();

		echo $output;
	}

	public function settings_dpd_collect() {

		$fields_prefix = 'dpd_collect';
		$countries_obj = new WC_Countries();
		$countries     = $countries_obj->__get( 'countries' );
		$dayofweek     = current_time( 'w' );
		$current_time  = current_time( 'H:i:s' );

		if ( $dayofweek == 6 ) {
			// If its saturday
			$date = date( "Y-m-d", strtotime( "+ 2 days", strtotime( $current_time ) ) );
		} else if ( $dayofweek == 7 ) {
			// If its sunday
			$date = date( "Y-m-d", strtotime( "+ 1 day", strtotime( $current_time ) ) );
		} else if ( $dayofweek == 5 ) {
			// If its friday
			$date = date( "Y-m-d", strtotime( "+ 3 days", strtotime( $current_time ) ) );
		} else {
			$date = date( "Y-m-d", strtotime( "+ 1 days", strtotime( $current_time ) ) );
		}

		ob_start();
		require_once plugin_dir_path( __FILE__ ) . 'partials/dpd-admin-collect-display.php';
		$output = ob_get_clean();

		echo $output;

	}

	/**
	 * Renders manifests table
	 */
	public function settings_dpd_manifests() {

		global $wpdb;

		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_manifests ORDER BY id DESC" );

		ob_start();
		require_once plugin_dir_path( __FILE__ ) . 'partials/dpd-admin-manifests-display.php';
		$output = ob_get_clean();

		echo $output;

	}

	public function download_manifest() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		global $wpdb;

		$manifest_id_to_download = isset( $_GET['download_manifest'] ) ? filter_var( $_GET['download_manifest'], FILTER_SANITIZE_NUMBER_INT ) : false;

		if ( $manifest_id_to_download ) {
			$results = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}dpd_manifests WHERE id={$manifest_id_to_download}" );

			$data = base64_decode( $results->pdf );
			$name = 'manifest_' . str_replace( '-', '_', $results->date ) . '.pdf';

			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/pdf' );
			header( 'Content-Disposition: attachment; filename="' . $name . '"' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Connection: Keep-Alive' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );

			ob_clean();
			flush();

			echo $data;
		}

	}

	public function get_option_like( $segment ) {

		global $wpdb;

		$data = [];

		$results = $wpdb->get_results( "SELECT option_id, option_name, option_value FROM {$wpdb->prefix}options WHERE option_name LIKE '{$segment}%'" );

		foreach ( $results as $result ) {
			$data[] = [
				'option_id'    => $result->option_id,
				'option_name'  => $result->option_name,
				'option_value' => maybe_unserialize( $result->option_value ),
			];
		}

		return $data;

	}

	public function delete_warehouse() {

		global $wpdb;

		$option_id = filter_var( $_POST['option_id'], FILTER_SANITIZE_NUMBER_INT );

		if ( is_numeric( $option_id ) && wp_doing_ajax() ) {
			$wpdb->query( "DELETE FROM {$wpdb->prefix}options WHERE option_id = {$option_id}" );
		}

		die();

	}

	public function courier_popup() {
		$current_time = current_time( 'H:i:s' );

		// Pick up from
		$customer_time = date( 'G', strtotime( date( 'H:i:s', strtotime( "+18 minutes", strtotime( current_time( 'H:i:s' ) ) ) ) ) );
		$pickup_until = '18:00';

		if ( $customer_time >= 7 && $customer_time < 15 ) {
			// Pick up times
			$pickup_from  = date( 'H:i', strtotime( "+18 minutes", strtotime( $current_time ) ) );
		} else {
			$pickup_from  = '10:00';
		}

		$dayofweek = current_time( 'w' );
		$time_cut_off = strtotime( '15:00:00' );

		if ( $dayofweek == 6 ) {
			// If its saturday
			$date = date( "Y-m-d", strtotime( "+ 2 days", strtotime( $current_time ) ) );
		} else if ( $dayofweek == 7 ) {
			// If its sunday
			$date = date( "Y-m-d", strtotime( "+ 1 day", strtotime( $current_time ) ) );
		} else if ( $dayofweek == 5 ) {
			// If its more or equal 15, request go for tommorow
			if ( strtotime( $current_time ) >= $time_cut_off or date( 'H:m:s', strtotime( $pickup_from ) ) >= $time_cut_off ) {
				$date = date( "Y-m-d", strtotime( "+ 3 days", strtotime( $current_time ) ) );
			} else {
				$date = current_time( "Y-m-d" );
			}
		} else {
			if ( strtotime( $current_time ) >= $time_cut_off or date( 'H:m:s', strtotime( $pickup_from ) ) >= $time_cut_off ) {
				$date = date( "Y-m-d", strtotime( "+ 1 days", strtotime( $current_time ) ) );
			} else {
				$date = current_time( "Y-m-d" );
			}
		}

		$warehouses      = $this->get_option_like( 'warehouse' );
		$warehouses_html = '';

		foreach ( $warehouses as $warehouse ) {
			$warehouses_html .= '<option value="' . $warehouse['option_name'] . '">' . $warehouse['option_value']['name'] . '</option>';
		}

		echo '
<div id="request-dpd-courier" style="display:none;">
	<div class="panel woocommerce_options_panel">
		<form action="' . admin_url( 'admin-ajax.php' ) . '" method="get">
			<input type="hidden" name="action" value="dpd_request_courier">
			' . wp_nonce_field( 'dpd-request-courier' ) . '
			<div class="options_group">
				<p class="form-field">
					<label for="dpd_warehouse">' . __( 'Select warehouse', 'woo-shipping-dpd-baltic' ) . ' *</label>
					<select id="dpd_warehouse" name="dpd_warehouse" class="select short" required style="width: 100%;">
						' . $warehouses_html . '
					</select>
				</p>
				<p class="form-field">
					<label for="dpd_note">' . __( 'Comment for courier', 'woo-shipping-dpd-baltic' ) . '</label>
					<textarea name="dpd_note" id="dpd_note" rows="2" cols="20" style="width: 100%;"></textarea>
				</p>
				<p class="form-field">
					<label for="dpd_pickup_date">' . __( 'Pickup date', 'woo-shipping-dpd-baltic' ) . ' *</label>
					<input type="text" name="dpd_pickup_date" id="dpd_pickup_date" class="dpd_datepicker" value="' . $date . '" required style="width: 100%;">
				</p>
				<p class="form-field">
					<label for="dpd_pickup_from">' . __( 'Pickup time from', 'woo-shipping-dpd-baltic' ) . ' *</label>
					<input type="text" name="dpd_pickup_from" id="dpd_pickup_from" value="' . $pickup_from . '" required style="width: 100%;">
				</p>
				<p class="form-field">
					<label for="dpd_pickup_until">' . __( 'Pickup time until', 'woo-shipping-dpd-baltic' ) . ' *</label>
					<input type="text" name="dpd_pickup_until" id="dpd_pickup_until" value="' . $pickup_until . '" required style="width: 100%;">
				</p>
				<p class="form-field">
					<label for="dpd_parcels">' . __( 'Count of parcels', 'woo-shipping-dpd-baltic' ) . ' *</label>
					<input type="number" name="dpd_parcels" id="dpd_parcels" value="1" min="1" step="1" required style="width: 100%;">
				</p>
				<p class="form-field">
					<label for="dpd_pallets">' . __( 'Count of pallets', 'woo-shipping-dpd-baltic' ) . ' *</label>
					<input type="number" name="dpd_pallets" id="dpd_pallets" value="0" min="0" step="1" required style="width: 100%;">
				</p>
				<p class="form-field">
					<label for="dpd_weight">' . __( 'Total weight', 'woo-shipping-dpd-baltic' ) . ' (kg) *</label>
					<input type="number" name="dpd_weight" id="dpd_weight" value="0.1" min="0.1" step="any" required style="width: 100%;">
				</p>
			</div>
			<div class="options_group">
				<p>
					<button type="submit" class="button button-primary">' . __( 'Request courier pickup', 'woo-shipping-dpd-baltic' ) . '</button>
				</p>
			</div>
		</form>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function(){
	    var today = new Date();
		var tomorrow = new Date();
		tomorrow.setDate(today.getDate()+1);
	    
		jQuery(".dpd_datepicker").datepicker({
			dateFormat : "yy-mm-dd",
			firstDay: 1,
			minDate: today,
			beforeShowDay: jQuery.datepicker.noWeekends
		});
		
		jQuery(".dpd_datepicker_upcoming").datepicker({
			dateFormat : "yy-mm-dd",
			firstDay: 1,
			minDate: tomorrow,
			beforeShowDay: jQuery.datepicker.noWeekends
		});
	});
</script>';
	}

	public function manifest_popup() {
		echo '
<div id="close-dpd-manifest" style="display:none;">
	<div class="panel woocommerce_options_panel">
		<form action="' . admin_url( 'admin-ajax.php' ) . '" method="get">
			<input type="hidden" name="action" value="dpd_close_manifest">
			' . wp_nonce_field( 'dpd-close-manifest' ) . '
			<div class="options_group">
				<p>' . __( 'Do you really want to close today\'s manifest?', 'woo-shipping-dpd-baltic' ) . '</p>
			</div>
			<div class="options_group">
				<p>
					<button type="submit" class="button button-primary">' . __( 'Close manifest', 'woo-shipping-dpd-baltic' ) . '</button>
				</p>
			</div>
		</form>
	</div>
</div>';
	}

}
