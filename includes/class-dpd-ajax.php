<?php

class Dpd_Baltic_Ajax {
	public function get_ajax_terminals() {
		$data = $this->get_terminals( WC()->customer->get_shipping_country() );

		wp_send_json( $data );
	}

	public function ajax_save_session_terminal() {
		check_ajax_referer( 'save-terminal', 'security' );

		WC()->session->set( wc_clean( $_POST['terminal_field'] ), wc_clean( $_POST['terminal'] ) );

		$cod = filter_var( $_REQUEST['cod'], FILTER_SANITIZE_NUMBER_INT );

		if ( is_numeric( $cod ) ) {
			WC()->session->set( 'cod_for_parcel', $cod );
		}

		wp_send_json( [
			'shipping_parcel_id' => WC()->session->get( wc_clean( $_POST['terminal_field'] ) )
		] );

		wp_die();
	}

	public function checkout_save_session_fields( $post_data ) {
		parse_str( $post_data, $posted );

		$google_map_api = get_option( 'dpd_google_map_key' );

		if ( $google_map_api == '' ) {
			if ( isset( $posted[ 'wc_shipping_dpd_parcels_terminal' ] ) && ! empty( $posted[ 'wc_shipping_dpd_parcels_terminal' ] ) ) {
				WC()->session->set( 'wc_shipping_dpd_parcels_terminal', $posted[ 'wc_shipping_dpd_parcels_terminal' ] );
			}

			if ( isset( $posted[ 'wc_shipping_dpd_sameday_parcels_terminal' ] ) && ! empty( $posted[ 'wc_shipping_dpd_sameday_parcels_terminal' ] ) ) {
				WC()->session->set( 'wc_shipping_dpd_sameday_parcels_terminal', $posted[ 'wc_shipping_dpd_sameday_parcels_terminal' ] );
			}
		}

		if ( isset( $posted[ 'wc_shipping_dpd_home_delivery_shifts' ] ) && ! empty( $posted[ 'wc_shipping_dpd_home_delivery_shifts' ] ) ) {
			WC()->session->set( 'wc_shipping_dpd_home_delivery_shifts', $posted[ 'wc_shipping_dpd_home_delivery_shifts' ] );
		}
	}

	public function dpd_request_courier() {
		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'dpd-request-courier' ) ) {
			$order_nr = get_option( 'dpd_request_order_nr' );

			if ( $order_nr == '' ) {
				update_option( 'dpd_request_order_nr', 1 );

				$order_nr = get_option( 'dpd_request_order_nr' );
			} else {
				update_option( 'dpd_request_order_nr', (int) $order_nr + 1 );

				$order_nr = get_option( 'dpd_request_order_nr' );
			}

			// Get info about warehouse
			$warehouse_info = maybe_unserialize( get_option( $_GET['dpd_warehouse'] ) );

			$response = '';

			if ( $warehouse_info ) {
				$payerId          = get_option( 'dpd_api_username' );
				$senderAddress    = $this->custom_length( $warehouse_info['address'], 100 );
				$senderCity       = $this->custom_length( $warehouse_info['city'], 100 );
				$senderCountry    = $warehouse_info['country'];
				$senderPostalCode = preg_replace( '/[^0-9,.]/', '', $warehouse_info['postcode'] );
				$senderContact    = $this->custom_length( $warehouse_info['contact_person'], 100 );

				$palletsCount = intval( $_GET['dpd_pallets'] );
				$parcelsCount = intval( $_GET['dpd_parcels'] );

				$weight_total = floatval( $_GET['dpd_weight'] ) ? floatval( $_GET['dpd_weight'] ) : 0.1;

				// Correct phone
				$dial_code_helper = new Dpd_Baltic_Dial_Code_Helper();
				$correct_phone    = $dial_code_helper->separatePhoneNumberFromCountryCode( $warehouse_info['phone'], $senderCountry );
				$phone            = $correct_phone['dial_code'] . $correct_phone['phone_number'];

				// Working hours
				$dayofweek = current_time( 'w' );

				$pickup_from  = isset( $_GET['dpd_pickup_from'] ) ? $_GET['dpd_pickup_from'] . ':00' : '10:00:00';
				$pickup_until = isset( $_GET['dpd_pickup_until'] ) ? $_GET['dpd_pickup_until'] . ':00' : '17:30:00';

				$time_cut_off = strtotime( '15:00:00' );
				$current_time = current_time( 'H:i:s' );

				if ( $dayofweek == 6 ) {
					// If its saturday
					$date = date( "Y-m-d", strtotime( "+ 2 days", strtotime( $current_time ) ) );
				} else if ( $dayofweek == 7 ) {
					// If its sunday
					$date = date( "Y-m-d", strtotime( "+ 1 day", strtotime( $current_time ) ) );
				} else if ( $dayofweek == 5 ) {
					// If its more or equal 15, request go for tommorow
					if ( strtotime( $current_time ) >= $time_cut_off or date( 'H:m:s', strtotime( $_GET['dpd_pickup_from'] . ':00' ) ) >= $time_cut_off ) {
						$date = date( "Y-m-d", strtotime( "+ 3 days", strtotime( $current_time ) ) );
					} else {
						$date = current_time( "Y-m-d" );
					}
				} else {
					if ( strtotime( $current_time ) >= $time_cut_off or date( 'H:m:s', strtotime( $_GET['dpd_pickup_from'] . ':00' ) ) >= $time_cut_off ) {
						$date = date( "Y-m-d", strtotime( "+ 1 days", strtotime( $current_time ) ) );
					} else {
						$date = current_time( "Y-m-d" );
					}
				}

				if ( strtotime( $_GET['dpd_pickup_date'] ) > strtotime( $date ) ) {
					$date = $_GET['dpd_pickup_date'];
				}

				$until = $date . ' ' . $pickup_until;
				$from  = $date . ' ' . $pickup_from;

				// Comment
				$comment = $this->custom_length( sanitize_text_field( $_GET['dpd_note'] ), 100 );

				$response = Dpd_Baltic_Admin::http_client( 'pickupOrderSave_', [
					'orderNr'          => $order_nr,
					'payerId'          => $payerId,
					'senderAddress'    => $senderAddress,
					'senderCity'       => $senderCity,
					'senderCountry'    => $senderCountry,
					'senderPostalCode' => $senderPostalCode,
					'senderContact'    => $senderContact,
					'senderPhone'      => $phone,
					'senderWorkUntil'  => $until,
					'pickupTime'       => $from,
					'weight'           => $weight_total,
					'parcelsCount'     => $parcelsCount,
					'palletsCount'     => $palletsCount,
					'nonStandard'      => isset( $comment ) ? $comment : '' // Comment for courier
				] );

				if ( $response == 'DONE|' ) {
					$response .= __( 'Courier will arrive from:', 'woo-shipping-dpd-baltic' ) . ' ' . $from;
					$response .= ' ' . __( 'until', 'woo-shipping-dpd-baltic' ) . ' ' . $pickup_until;
				}
			}

			dpd_baltic_add_flash_notice( $response, 'info', true );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
		exit;
	}

	public function dpd_close_manifest() {
		global $wpdb;

		if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'dpd-close-manifest' ) ) {
			$service_provider  = get_option( 'dpd_api_service_provider' );
			$test_mode         = get_option( 'dpd_test_mode' );
			$service_test_mode = ! empty( $test_mode ) && $test_mode == 'yes' ? true : false;

			$dates = [
				date( 'Y-m-d' ),
				date( 'Y-m-d', strtotime( '+ 1 day' ) ),
				date( 'Y-m-d', strtotime( '+ 2 days' ) ),
				date( 'Y-m-d', strtotime( '+ 3 days' ) ),
			];

			$i = 0;
			foreach ( $dates as $date ) {
				$i ++;

				$response = Dpd_Baltic_Admin::http_client( 'parcelManifestPrint_', [
					'type' => 'manifest',
					'date' => $date
				] );

				$message = sprintf( __( 'DPD manifest is closed for today\'s orders that were made up to now. DPD doesn\'t require you to print the manifest. If you would like to print the manifest anyway, go <a href="%s">here</a>.', 'woo-shipping-dpd-baltic' ), esc_url( add_query_arg(
					array(
						'page'    => 'wc-settings',
						'tab'     => 'dpd',
						'section' => 'manifests',
					), admin_url( 'admin.php' )
				) ) );

				if ( $service_provider == 'lt' && ! $service_test_mode ) {
					if ( $response && $response->status == 'err' ) {
						if ( $i == 1 ) {
							dpd_baltic_add_flash_notice( $response->errlog, 'error', true );
						}

						continue;
					} else {
						if ( ! empty( $response ) ) {
							$wpdb->insert( $wpdb->prefix . 'dpd_manifests', [
								'pdf'  => base64_encode( $response ),
								'date' => $date
							] );

							if ( $i == 1 ) {
								dpd_baltic_add_flash_notice( $message, 'success', true );
							}
						}
					}
				} else {
					$response = @json_decode( $response );

					if ( $response && $response->status == 'ok' ) {
						if ( $response->pdf && $response->pdf !== null ) {
							$wpdb->insert( $wpdb->prefix . 'dpd_manifests', [
								'pdf'  => $response->pdf,
								'date' => $date
							] );

							if ( $i == 1 ) {
								dpd_baltic_add_flash_notice( $message, 'success', true );
							}
						}
					} else {
						if ( $i == 1 ) {
							dpd_baltic_add_flash_notice( $response->errlog, 'error', true );
						}

						continue;
					}
				}
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=shop_order' ) );
		exit;
	}

	public function dpd_order_collection_request( $data = [], $die = true, $post = true ) {
		$location = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-settings&tab=dpd&section=collect' );

		if ( current_user_can( 'edit_shop_orders' ) ) {
			$data = isset( $_POST ) && $post ? $_POST : $data;

			$total_amount = absint( $data['dpd_collect_parcels_number'] ) + absint( $data['dpd_collect_pallets_number'] );

			if ( isset( $data['dpd_collect_total_weight'] ) && $data['dpd_collect_total_weight'] > 0 ) {
				$weight = '#kg' . round( ( $data['dpd_collect_total_weight'] ), 2 );
			} else {
				$weight = '#kg20';
			}

			if ( $total_amount > 0 ) {
				$parcels_amount = absint( $data['dpd_collect_parcels_number'] );
				$pallets_amount = absint( $data['dpd_collect_pallets_number'] );

				$parcels_no = '#' . $parcels_amount . 'cl';
				$pallets_no = '#' . $pallets_amount . 'pl';

				$cname  = $data['dpd_collect_sender_name'];
				$cname0 = substr( $cname, 0, 35 );
				$cname1 = substr( $cname, 35, 35 );
				$cname2 = substr( $cname, 70, 35 );
				$cname3 = substr( $cname, 105, 35 );

				$cstreet  = $data['dpd_collect_sender_street_address'];
				$cpostal  = $data['dpd_collect_sender_postcode'];
				$ccity    = $data['dpd_collect_sender_city'];
				$ccountry = $data['dpd_collect_sender_country'];

				$cphone = $data['dpd_collect_sender_contact_phone_number'];
				$cemail = $data['dpd_collect_sender_contact_email'];

				$rname  = $data['dpd_collect_recipient_name'];
				$rname0 = substr( $rname, 0, 35 );
				$rname1 = substr( $rname, 35, 35 );

				$rstreet  = $data['dpd_collect_recipient_street_address'];
				$rpostal  = $data['dpd_collect_recipient_postcode'];
				$rcity    = $data['dpd_collect_recipient_city'];
				$rcountry = $data['dpd_collect_recipient_country'];

				$rphone = $data['dpd_collect_recipient_contact_phone_number'];
				$remail = $data['dpd_collect_recipient_contact_email'];

				$pickup_date = isset( $data['dpd_collect_pickup_date'] ) ? '#' . substr( $data['dpd_collect_pickup_date'], 5 ) : '#' . date( 'm-d', strtotime( "+ 1 day", strtotime( current_time( 'Y-m-d' ) ) ) );

				$info1 = $parcels_no . $pallets_no . $pickup_date . $weight;

				$info2 = $data['dpd_collect_additional_information'];

				$params = [
					'cstreet'  => $cstreet,
					'ccountry' => strtoupper( $ccountry ),
					'cpostal'  => $cpostal,
					'ccity'    => $ccity,
					'info1'    => $info1,
					'rstreet'  => $rstreet,
					'rpostal'  => $rpostal,
					'rcountry' => strtoupper( $rcountry ),
					'rcity'    => $rcity
				];

				if ( isset( $cphone ) && $cphone ) {
					$params['cphone'] = $cphone;
				}

				if ( isset( $cemail ) && $cemail ) {
					$params['cemail'] = $cemail;
				}

				if ( isset( $rphone ) && $rphone ) {
					$params['rphone'] = $rphone;
				}

				if ( isset( $remail ) && $remail ) {
					$params['remail'] = $remail;
				}

				if ( isset( $info2 ) && $info2 ) {
					$params['info2'] = $info2;
				}

				if ( isset( $cname0 ) && $cname0 ) {
					$params['cname'] = $cname0;
				}

				if ( isset( $cname1 ) && $cname1 ) {
					$params['cname1'] = $cname1;
				}

				if ( isset( $cname2 ) && $cname2 ) {
					$params['cname2'] = $cname2;
				}

				if ( isset( $cname3 ) && $cname3 ) {
					$params['cname3'] = $cname3;
				}

				if ( isset( $rname0 ) && $rname0 ) {
					$params['rname'] = $rname0;
				}

				if ( isset( $rname1 ) && $rname1 ) {
					$params['rname2'] = $rname1;
				}

				$response = Dpd_Baltic_Admin::http_client( 'crImport_', $params );

				if ( strpos( $response, '201' ) !== false ) {
					dpd_baltic_add_flash_notice( __( 'Your request was sent to DPD and your parcels will be collected. For more info call DPD.', 'woo-shipping-dpd-baltic' ), 'success', true );
				} else {
					dpd_baltic_add_flash_notice( $response, 'error', true );
				}
			}

			if ( $die ) {
				die( $location );
			} else {
				return;
			}
		}

		if ( $die ) {
			die( $location );
		} else {
			return;
		}
	}

	public function dpd_order_reverse_collection_request( $order ) {
		$country_code = $order->get_shipping_country();
		if ( strtoupper( $country_code ) == 'LT' || strtoupper( $country_code ) == 'LV' || strtoupper( $country_code ) == 'EE' ) {
			$pcode = preg_replace( '/[^0-9,.]/', '', $order->get_shipping_postcode() );
		} else {
			$pcode = $order->get_shipping_postcode();
		}

		$dial_code_helper = new Dpd_Baltic_Dial_Code_Helper();
		$correct_phone    = $dial_code_helper->separatePhoneNumberFromCountryCode( $order->get_billing_phone(), $country_code );

		$data['dpd_collect_sender_name']                 = $this->custom_length( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(), 140 );
		$data['dpd_collect_sender_street_address']       = $this->custom_length( $order->get_shipping_address_1(), 35 );
		$data['dpd_collect_sender_postcode']             = $pcode;
		$data['dpd_collect_sender_city']                 = $this->custom_length( $order->get_shipping_city(), 25 );
		$data['dpd_collect_sender_contact_phone_number'] = $correct_phone['dial_code'] . $correct_phone['phone_number'];
		$data['dpd_collect_sender_contact_email']        = $order->get_billing_email();
		$data['dpd_collect_sender_country']              = $country_code;
		$data['dpd_collect_parcels_number']              = '1';
		$data['dpd_collect_pallets_number']              = '0';

		$data['dpd_collect_total_weight'] = 0; // @TODO: get product weight
		$data['dpd_collect_pickup_date']  = date( 'Y-m-d', strtotime( "+ 1 day", strtotime( current_time( 'Y-m-d' ) ) ) );

		$data['dpd_collect_additional_information'] = 'product return';

		$warehouse_info  = maybe_unserialize( get_option( $_POST['dpd_warehouse'] ) );
		$correct_phone_r = $dial_code_helper->separatePhoneNumberFromCountryCode( $warehouse_info['phone'], $warehouse_info['country'] );

		$data['dpd_collect_recipient_name']                 = $this->custom_length( $warehouse_info['contact_person'], 70 );
		$data['dpd_collect_recipient_street_address']       = $this->custom_length( $warehouse_info['address'], 35 );
		$data['dpd_collect_recipient_postcode']             = preg_replace( '/[^0-9,.]/', '', $warehouse_info['postcode'] );
		$data['dpd_collect_recipient_city']                 = $this->custom_length( $warehouse_info['city'], 25 );
		$data['dpd_collect_recipient_country']              = $warehouse_info['country'];
		$data['dpd_collect_recipient_contact_phone_number'] = $correct_phone_r['dial_code'] . $correct_phone_r['phone_number'];
		$data['dpd_collect_recipient_contact_email']        = $this->custom_length( get_bloginfo( 'admin_email' ), 30 );

		$this->dpd_order_collection_request( $data, false, false );
	}

	private function get_terminals( $country = false ) {
		global $wpdb;

		$shipping_country = '';

		if ( $country ) {
			$shipping_country = "WHERE country = '{$country}'";
		}

		$terminals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}dpd_terminals {$shipping_country} ORDER BY city" );

		return $terminals;
	}

	private function custom_length( $string, $length ) {
		if ( strlen( $string ) <= $length ) {
			return $string;
		} else {
			return substr( $string, 0, $length );
		}
	}
}
