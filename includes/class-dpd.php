<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://dpd.com
 * @since      1.0.0
 *
 * @package    Dpd
 * @subpackage Dpd/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Dpd
 * @subpackage Dpd/includes
 * @author     DPD
 */
class Dpd {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Dpd_Baltic_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'DPD_NAME_VERSION' ) ) {
			$this->version = DPD_NAME_VERSION;
		} else {
			$this->version = '1.1.0';
		}
		$this->plugin_name = 'woo-shipping-dpd-baltic';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Dpd_Baltic_Loader. Orchestrates the hooks of the plugin.
	 * - Dpd_Baltic_i18n. Defines internationalization functionality.
	 * - Dpd_Baltic_Admin. Defines all hooks for the admin area.
	 * - Dpd_Baltic_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dpd-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dpd-i18n.php';

		/**
		 * Helpers.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dpd-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-dpd-public.php';

		/**
		 * Plugin AJAX methods.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dpd-ajax.php';

		/**
		 * Dial code helper.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dpd-dial-code-helper.php';

		$this->loader = new Dpd_Baltic_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Dpd_Baltic_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Dpd_Baltic_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Dpd_Baltic_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_ajax  = new Dpd_Baltic_Ajax();

		$this->loader->add_action( 'woocommerce_get_settings_pages', $plugin_admin, 'get_settings_pages' );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'woocommerce_shipping_init', $this, 'dpd_shipping_methods' );
		$this->loader->add_filter( 'woocommerce_shipping_methods', $this, 'add_dpd_shipping_methods' );

		$this->loader->add_action( 'woocommerce_email', $this, 'load_shipping_method', 1, 1 );

		$this->loader->add_action( 'dpd_parcels_updater', $plugin_admin, 'get_all_parcels_list' );
		$this->loader->add_action( 'dpd_parcels_country_update', $plugin_admin, 'country_parcels_list', 10, 1 );

		// Custom order actions
		$this->loader->add_action( 'woocommerce_order_actions_start', $plugin_admin, 'order_actions_metabox_dpd', 10, 1 );
		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $plugin_admin, 'save_order_actions_meta_box', 0, 2 );
		$this->loader->add_action( 'woocommerce_order_actions', $plugin_admin, 'add_order_actions' );
		$this->loader->add_action( 'woocommerce_order_action_dpd_print_parcel_label', $plugin_admin, 'do_print_parcel_label' );
		$this->loader->add_action( 'woocommerce_order_action_dpd_parcel_status', $plugin_admin, 'do_get_parcel_status' );
		$this->loader->add_action( 'woocommerce_order_action_dpd_cancel_shipment', $plugin_admin, 'do_cancel_shipment' );
		$this->loader->add_action( 'woocommerce_order_action_dpd_collection_request', $plugin_ajax, 'dpd_order_reverse_collection_request' );

		// Renders warehouses settings
		$this->loader->add_action( 'woocommerce_settings_dpd_warehouses', $plugin_admin, 'settings_dpd_warehouses' );

		// Renders manifests table
		$this->loader->add_action( 'woocommerce_settings_dpd_manifests', $plugin_admin, 'settings_dpd_manifests' );
		// Download manifest action
		$this->loader->add_action( 'init', $plugin_admin, 'download_manifest' );

		// Renders collect form
		$this->loader->add_action( 'woocommerce_settings_dpd_collect', $plugin_admin, 'settings_dpd_collect' );

		// Remove selected warehouse
		$this->loader->add_action( 'wp_ajax_delete_warehouse', $plugin_admin, 'delete_warehouse' );

		// Bulk order actions
		$this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'define_orders_bulk_actions', 10 );
		$this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'handle_orders_bulk_actions', 10, 3 );
		$this->loader->add_filter( 'admin_notices', $plugin_admin, 'bulk_admin_notices' );

		// Request courier pop-up
		$this->loader->add_action( 'wp_ajax_dpd_request_courier', $plugin_ajax, 'dpd_request_courier' );
		$this->loader->add_action( 'wp_ajax_dpd_close_manifest', $plugin_ajax, 'dpd_close_manifest' );

		// Request order collection
		$this->loader->add_action( 'wp_ajax_dpd_order_collection_request', $plugin_ajax, 'dpd_order_collection_request' );

		$this->loader->add_action( 'admin_footer', $plugin_admin, 'courier_popup', 100 );
		$this->loader->add_action( 'admin_footer', $plugin_admin, 'manifest_popup', 110 );

		add_action( 'admin_notices', 'dpd_baltic_display_flash_notices', 12 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Dpd_Baltic_Public( $this->get_plugin_name(), $this->get_version() );
		$plugin_ajax   = new Dpd_Baltic_Ajax();

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_filter( 'woocommerce_locate_template', $plugin_public, 'locate_template', 20, 3 );
		$this->loader->add_filter( 'woocommerce_locate_core_template', $plugin_public, 'locate_template', 20, 3 );

		$this->loader->add_action( 'woocommerce_shipping_init', $this, 'dpd_shipping_methods' );
		$this->loader->add_filter( 'woocommerce_shipping_methods', $this, 'add_dpd_shipping_methods' );

		$this->loader->add_action( 'woocommerce_email', $this, 'load_shipping_method', 1, 1 );

		$this->loader->add_action( 'woocommerce_checkout_update_order_review', $plugin_ajax, 'checkout_save_session_fields', 10, 1 );

		$this->loader->add_action( 'wp_ajax_set_checkout_session', $plugin_public, 'set_checkout_session' );
		$this->loader->add_action( 'wp_ajax_nopriv_set_checkout_session', $plugin_public, 'set_checkout_session' );

		// AJAX methods
		$this->loader->add_action( 'wc_ajax_get_dpd_parcels', $plugin_ajax, 'get_ajax_terminals' );
		$this->loader->add_action( 'wc_ajax_nopriv_get_dpd_parcels', $plugin_ajax, 'get_ajax_terminals' );

		$this->loader->add_action( 'wc_ajax_choose_dpd_terminal', $plugin_ajax, 'ajax_save_session_terminal' );
		$this->loader->add_action( 'wc_ajax_nopriv_choose_dpd_terminal', $plugin_ajax, 'ajax_save_session_terminal' );

		// Available payment methods
		$this->loader->add_filter( 'woocommerce_available_payment_gateways', $plugin_public, 'available_payment_gateways', 10, 1 );

		// COD fee
		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $plugin_public, 'add_cod_fee', 10, 2 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Dpd_Baltic_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function dpd_shipping_methods() {
		if ( ! class_exists( 'DpdHomeDelivery' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/DpdHomeDelivery.php';
		}

		if ( ! class_exists( 'DpdSameDayDelivery' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/DpdSameDayDelivery.php';
		}

		if ( ! class_exists( 'DpdHomeDeliverySat' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/DpdHomeDeliverySat.php';
		}

		if ( ! class_exists( 'DpdParcels' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/DpdParcels.php';
		}

		if ( ! class_exists( 'DpdSameDayParcels' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/DpdSameDayParcels.php';
		}

		$dpd_home_delivery = new DpdHomeDelivery();
		$dpd_home_delivery->init_actions_and_filters();

		$dpd_same_day_delivery = new DpdSameDayDelivery();
		$dpd_same_day_delivery->init_actions_and_filters();

		$dpd_same_day_delivery_sat = new DpdHomeDeliverySat();
		$dpd_same_day_delivery_sat->init_actions_and_filters();

		$dpd_parcels = new DpdParcels();
		$dpd_parcels->init_actions_and_filters();

		$dpd_same_day_parcels = new DpdSameDayParcels();
		$dpd_same_day_parcels->init_actions_and_filters();
	}

	/**
	 * Sita funkcija vykdomas tiek back tiek front
	 * jei esam admine tiesiog grazinam metodus kaip yra
	 * jei fronte darom logiak
	 *
	 * @param $methods
	 *
	 * @return mixed
	 */
	public function add_dpd_shipping_methods( $methods ) {
		$methods['dpd_home_delivery']     = 'DpdHomeDelivery';
		$methods['dpd_sameday_delivery']  = 'DpdSameDayDelivery';
		$methods['dpd_sat_home_delivery'] = 'DpdHomeDeliverySat';
		$methods['dpd_parcels']           = 'DpdParcels';
		$methods['dpd_sameday_parcels']   = 'DpdSameDayParcels';

		return $methods;
	}

	public function load_shipping_method( $order_id ) {
		WC()->shipping();
	}
}
