<?php

/**
 * Fired during plugin activation
 *
 * @link       https://dpd.com
 * @since      1.0.0
 *
 * @package    Dpd
 * @subpackage Dpd/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Dpd
 * @subpackage Dpd/includes
 * @author     DPD
 */
class Dpd_Baltic_Activator {

	public static function activate() {
		self::create_tables();
		self::update_tables();
		self::create_cron_jobs();
	}

    public static function update() {
        self::update_tables();
    }

	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables[] = "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpd_barcodes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  dpd_barcode varchar(50) NOT NULL,
  PRIMARY KEY  (id),
  KEY  (order_id),
  UNIQUE KEY dpd_barcode (dpd_barcode)
) $collate;
		";

		$tables[] = "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpd_manifests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pdf LONGTEXT NOT NULL,
  date DATE NOT NULL,
  PRIMARY KEY  (id),
  KEY  (date)
) $collate;
		";

		$tables[] = "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dpd_terminals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  parcelshop_id varchar(20) NOT NULL DEFAULT '',
  company varchar(40) NOT NULL DEFAULT '',
  country varchar(2) NOT NULL DEFAULT '',
  city varchar(40) NOT NULL DEFAULT '',
  pcode varchar(5) NOT NULL DEFAULT '',
  street varchar(40) NOT NULL DEFAULT '',
  email varchar(30) NULL,
  phone varchar(30) NULL,
  mon text NOT NULL,
  tue text NOT NULL,
  wed text NOT NULL,
  thu text NOT NULL,
  fri text NOT NULL,
  sat text NOT NULL,
  sun text NOT NULL,
  distance int(30) NOT NULL DEFAULT 0,
  longitude float NULL,
  latitude float NULL,
  cod tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY parcelshop_id (parcelshop_id)
) $collate;
		";

		foreach ( $tables as $table ) {
			dbDelta( $table );
		}
	}

    private static function update_tables() {
        global $wpdb;

        $wpdb->hide_errors();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $installed_version = get_option( 'wc_shipping_dpd_baltic_db_version' );

        if ( ! defined( 'IFRAME_REQUEST' ) && DPD_NAME_VERSION != $installed_version ) {
            if ( version_compare( $installed_version, '1.1.0', '<' ) ) {
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `pcode` varchar(25) NOT NULL default ''" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `mon` text NULL default NULL" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `tue` text NULL default NULL" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `wed` text NULL default NULL" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `thu` text NULL default NULL" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `fri` text NULL default NULL" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `sat` text NULL default NULL" );
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}dpd_terminals MODIFY `sun` text NULL default NULL" );
            }
        }

        delete_option( 'wc_shipping_dpd_baltic_db_version' );
        add_option( 'wc_shipping_dpd_baltic_db_version', DPD_NAME_VERSION );
    }

	private static function create_cron_jobs() {
		wp_clear_scheduled_hook( 'dpd_parcels_updater' );
		wp_schedule_event( time(), 'daily', 'dpd_parcels_updater' );
	}
}
