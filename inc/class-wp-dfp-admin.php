<?php

if ( ! defined( 'WP_DFP_ADS_DIR' ) ) {
	exit;
}

class Wp_Dfp_Admin {

	static $instance	= false;
	private $key		= 'wp_dfp_ads_settings';
	private $metabox_id	= 'wp_dfp_ads_metabox';

	public function __construct() {

		$this->_add_actions();
	}

	/**
	 * Singleton
	 *
	 * Returns a single instance of the current class.
	 */
	public static function singleton() {

		if ( ! self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Register our setting to WP
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Register the administration page.
	 *
	 */
	public function menu() {

		global $wp_dfp_ads_options_page;

		$wp_dfp_ads_options_page	= add_options_page( 'Ads Settings', 'Advertisements', 'manage_options', 'wp-dfp-ads', array( $this, 'admin_page' ) );

		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-{$wp_dfp_ads_options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );

	}

	/**
	 * Create Plugin: loading files for the plugin.
	 */
	public function register_files( $hook ){

		global $wp_dfp_ads_options_page;
		global $post_type;

		if ( $hook != $wp_dfp_ads_options_page && 'advert' != $post_type )
			return;

		// queue main styles and scripts
		wp_enqueue_style( 'wp-dfp-ads-styles-admin', WP_DFP_ADS_URL . 'css/metabox-ui.min.css' );

	}

	/**
	 * Page Templating: Manage the Plugin Settings Page.
	 */
	public function admin_page() { ?>

		<div class="wrap cmb2-options-page <?php echo $this->key; ?>">

			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<hr/>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>

		</div>

		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 */
	public function add_options_page_metabox() {

		// hook in our save notices
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );

		// Set up CMB2 fields
		$cmb = new_cmb2_box( array(
			'id'			=> $this->metabox_id,
			'hookup'		=> false,
			'cmb_styles'	=> false,
			'show_on'		=> array(
				'key'	=> 'options-page',
				'value'	=> array( $this->key, )
			),
		) );

		$cmb->add_field( array(
			'id'	=> 'general-settings',
			'name'	=> __( 'General', 'wp-dfp-ads' ),
			'type'	=> 'title',
			'desc'	=> 'General advertisement settings.'
		) );

		$cmb->add_field( array(
			'id'	=> 'dfp-prefix',
			'name'	=> __( 'DFP Prefix', 'wp-dfp-ads' ),
			'desc'	=> __( 'Prefix to use when calling DFP slots (e.g. /XXXXXX/site_name).', 'wp-dfp-ads' ),
			'type'	=> 'text',
		) );

		/*$cmb->add_field( array(
			'id'	=> 'async-ads-switch',
			'name'	=> __( 'Asynchronous Ads (on/off)', 'wp-dfp-ads' ),
			'type'	=> 'checkbox',
			'desc'	=> 'Turn asynchronously loaded ads on/off.'
		) );*/

		/*$cmb->add_field( array(
			'id'	=> 'ad-fallback-class',
			'name'	=> __( 'Ad Fallback Class', 'wp-dfp-ads' ),
			'type'	=> 'text',
			'desc'	=> 'If left empty will default to "ad_fallback".'
		) );*/

	}

	/**
	 * Register settings notices for display
	 *
	 */
	public function settings_notices( $object_id, $updated ) {

		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		add_settings_error( $this->key . '-notices', '', __( 'Settings updated.', 'wp_dfp_ads' ), 'updated' );
		settings_errors( $this->key . '-notices' );

	}

	/**
	 * Public getter method for retrieving protected/private variables
	 */
	public function __get( $field ) {

		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );

	}

	/**
	 * Add Actions
	 *
	 * Defines all the WordPress actions and filters used by this class.
	 */
	protected function _add_actions() {

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_files' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );

	}

}

/**
 * Wrapper function around cmb2_get_option
 */
function wp_dfp_ads_get_option( $key = '' ) {
	return function_exists('cmb2_get_option') ? cmb2_get_option( Wp_Dfp_Admin::singleton()->key, $key ) : '';
}
