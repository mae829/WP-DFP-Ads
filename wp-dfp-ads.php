<?php
/**
 * Plugin Name: WP DFP Ads
 * Description: This plugin creates a whole system for running DFP ads
 * Version: 1.3
 * Author: Mike Estrada, Alex Delgado
 */

define( 'WP_DFP_ADS_DIR', dirname( __FILE__ ) );
define( 'WP_DFP_ADS_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_DFP_ADS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_DFP_ADS_VERSION', '1.3' );

class Wp_Dfp_Setup {

	static $instance = false;

	public function __construct() {

		// check if CMB2 is loaded. if not BAIL
		if ( defined( 'CMB2_LOADED' ) ) {

			// set up Ads settings page
			if ( file_exists( WP_DFP_ADS_DIR .'/inc/class-wp-dfp-admin.php' ) ) {

				require_once WP_DFP_ADS_DIR .'/inc/class-wp-dfp-admin.php';
				Wp_Dfp_admin::singleton();

			}

			// load general ad file and initiate class
			if ( file_exists( WP_DFP_ADS_DIR .'/inc/class-wp-dfp-ads.php' ) ) {

				require_once WP_DFP_ADS_DIR .'/inc/class-wp-dfp-ads.php';
				Wp_Dfp_Ads::singleton();

			}

			// load post type ads file and initiate class
			if ( file_exists( WP_DFP_ADS_DIR .'/inc/class-post-type-ads.php' ) ) {

				require_once WP_DFP_ADS_DIR .'/inc/class-post-type-ads.php';
				Post_Type_Ads::singleton();

			}

		} else {

			//display error if CMB2 is not IN USE
			echo '<div class="error">
						<p>This plugin is dependent of CMB2 plugin. Please <strong>activate CMB2</strong>.</p>
					</div>';

		}

	}

	/**
	 * Singleton
	 *
	 * @return a single instance of the current class.
	 */
	public static function singleton() {

		if ( !self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

}

//load after all plugins are loaded, so we can check if CMB2 is loaded
add_action( 'plugins_loaded', array( 'Wp_Dfp_Setup', 'singleton' ) );
