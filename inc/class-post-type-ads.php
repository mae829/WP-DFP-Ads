<?php

if ( ! defined( 'WP_DFP_ADS_DIR' ) ) {
	exit;
}

class Post_Type_Ads {

	var $advert_meta = array(
		'advert-id',
		'advert-slot',
		'advert-logic',
		'advert-markup',
		'advert-mapname',
		'advert-breakpoints'
	);

	var $admin_notice_key = 'ad_sanitized';

	static $instance = false;

	public static $sidebar_bottom_ad = false;

	public function __construct() {

		$this->_add_actions();

	}

	/**
	 * Add Advert Columns
	 *
	 * Adds additional column headers to the "advert" post type screen.
	 *
	 * @param array $columns
	 *
	 * @return Array containing column headers for the "advert" post type.
	 */
	public function add_advert_columns( $columns = array() ) {

		unset( $columns['date'] );

		$columns['advert-logic'] = 'Ad Logic';

		return $columns;
	}

	/**
	 * Set Advert Column Values
	 *
	 * Sets the values of our custom "advert" columns.
	 *
	 * @param string $name
	 */
	public function set_advert_column_values( $name = null ) {
		global $post;

		if ( 'advert-logic' === $name )
			echo get_post_meta( $post->ID, 'advert-logic', true );
	}

	/**
	 * Set Sortable Advert Columns
	 *
	 * Defines which custom "advert" columns should be sortable.
	 *
	 * @param array $columns
	 *
	 * @return Array containing list of sortable columns.
	 */
	public function set_sortable_advert_columns( $columns = array() ) {

		$columns['taxonomy-advert-size']		= 'taxonomy-advert-size';
		// $columns['advert-logic']				= 'advert-logic';

		return $columns;
	}

	/**
	 * Sort Custom Fields
	 *
	 * Utility function used to properly sort our custom post header columns.
	 *
	 * @param WP_Query $wp_query
	 */
	public function sort_custom_fields( $wp_query = null ) {

		if ( !is_admin() )
			return;

		if ( $wp_query->is_main_query() ) {

			$order		= ( !empty( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'desc' );
			$orderby	= ( !empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '' );

			$custom_columns = array(
				'advert-logic' => array( 'orderby' => 'meta_value', 'meta_key' => 'advert-logic' )
			);

			if ( array_key_exists( $orderby, $custom_columns ) ) {
				$filter = $custom_columns[$orderby];

				$wp_query->set( 'order', $order );
				$wp_query->set( 'orderby', $filter['orderby'] );
				$wp_query->set( 'meta_key', $filter['meta_key'] );

				if ( isset( $filter['meta_type'] ) ) {
					$wp_query->set( 'meta_type', $filter['meta_type'] );
				}
			}
		}
	}

	/**
	 * Register Meta Boxes
	 *
	 * Defines all the meta boxes used by this theme.
	 *
	 * @param WP_Post $post The object for the current post/page.
	 */
	public function register_meta_boxes( $post = null ) {
		add_meta_box( 'advert_meta_box', 'Ad Details', array( $this, 'generate_advert_meta_box' ), 'advert', 'normal', 'default' );
	}

	/**
	 * Add Responsive Sizes Metabox
	 *
	 * Generates the Metabox and fields for Responsive Ad Sizes
	 * Dependent of CMB2
	 */
	public function add_responsive_sizes_metabox() {

		// Get registered ad sizes
		$ad_sizes	= array();
		$ad_sizes_objects	= get_terms( array(
			'taxonomy'		=> 'advert-size',
			'hide_empty'	=> false
		) );
		foreach ( $ad_sizes_objects	as $ad_size ) {
			$ad_sizes[$ad_size->name]	= $ad_size->name;
		}

		// Initiate metabox
		$responsive_sizes_box	= new_cmb2_box( array(
			'id'			=> 'responsive_ad_sizes',
			'title'			=> __( 'Responsive Ad Sizes', 'wp_dfp_ads' ),
			'object_types'	=> array( 'advert' ),
			'context'		=> 'normal',
			'priority'		=> 'default',
			'show_names'	=> true
		) );

		$responsive_sizes_box->add_field( array(
			'id'		=> 'advert-mapname',
			'name'		=> __( 'SizeMap Name', 'wp_dfp_ads' ),
			'desc'		=> __( 'Please fill out with a unique name. If left blank, no responsive ad map will be generated but the fields below will be saved for future reference.', 'wp_dfp_ads' ),
			'type'		=> 'text',
		) );

		$responsive_size_group = $responsive_sizes_box->add_field( array(
			'id'		=> 'advert-breakpoints',
			'type'		=> 'group',
			'desc'		=> __( 'Add a size per desired breakpoint. This will override the regular Ad Sizes in the the Taxonomy so clear out the SizeMap Name to use Taxonomy Ad Sizes.', 'wp_dfp_ads' ),
			'options'	=> array(
				'group_title'   => __( 'Size {#}', 'wp_dfp_ads' ),
				'add_button'    => __( 'Add Size', 'wp_dfp_ads' ),
				'remove_button' => __( 'Remove Size', 'wp_dfp_ads' ),
				'sortable'		=> true,
			)
		) );

		$responsive_sizes_box->add_group_field( $responsive_size_group, array(
			'id'		=> 'breakpoint',
			'name'		=> __( 'Breakpoint', 'wp_dfp_ads' ),
			'type'		=> 'radio_inline',
			'options'	=> array(
				'-'			=> __( '0px', 'wp_dfp_ads' ),
				'768'		=> __( '>= 768px', 'wp_dfp_ads' ),
				'992'		=> __( '>= 992px', 'wp_dfp_ads' ),
				'1200'		=> __( '>= 1200px', 'wp_dfp_ads' ),
			),
		) );

		$responsive_sizes_box->add_group_field( $responsive_size_group, array(
			'id'				=> 'adsize',
			'name'				=> __( 'Ad Size', 'wp_dfp_ads' ),
			'desc'				=> __( 'Leaving this option blank will hide/not load an ad for this breakpoint.', 'wp_dfp_ads' ),
			'type'				=> 'multicheck_inline',
			'select_all_button'	=> false,
			'options'			=> $ad_sizes
		) );

	}

	/**
	 * Generate Advert Meta Box
	 *
	 * Generates and displays the "Advert Details" meta box.
	 */
	public function generate_advert_meta_box() {

		global $post;

		// get post meta values if they've already been set
		$post_meta = $this->_get_post_meta( $post->ID, $this->advert_meta );

		$advert_id	= ( !empty( $post_meta['advert-id'] ) ? $post_meta['advert-id']->meta_value : '' );
		$slot		= ( !empty( $post_meta['advert-slot'] ) ? $post_meta['advert-slot']->meta_value : '' );
		$logic		= ( !empty( $post_meta['advert-logic'] ) ? $post_meta['advert-logic']->meta_value : '' );
		$markup		= ( !empty( $post_meta['advert-markup'] ) ? $post_meta['advert-markup']->meta_value : '' );

		wp_nonce_field( 'wp_dfp_ads_meta_box','wp_dfp_ads_meta_box_nonce' );

		require( WP_DFP_ADS_DIR .'/inc/meta-boxes/advert-meta-box.php' );
	}

	/**
	 * Save Post Meta
	 *
	 * Verifies that the given post is being saved, the request is valid, and
	 * the user has the necessary permissions before handing off to the
	 * post-type-specific save method.
	 *
	 * @param int $post_id
	 */
	public function save_post_meta( $post_id = null ) {

		/*
		* We need to verify this came from our screen and with proper authorization,
		* because the save_post action can be triggered at other times.
		*/

		// if this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// check that our nonce is set and it is valid.
		if ( !isset( $_POST['wp_dfp_ads_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['wp_dfp_ads_meta_box_nonce'], 'wp_dfp_ads_meta_box' ) )
			return;

		// check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

			if ( !current_user_can( 'edit_page', $post_id ) )
				return;

		} else {

			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
		}

		$save_method = "_save_{$_POST['post_type']}_meta_box";

		if ( method_exists( $this, $save_method ) )
			call_user_func( array( $this, $save_method ), $post_id );
	}

	/**
	 * Add Notice Query Var
	 *
	 * Add a custom query var to WordPress so that we know the ad logic has
	 * been sanitized.
	 *
	 * @param string $location The destination URL.
	 *
	 * @return string New URL query string.
	 */
	public function add_notice_query_var( $location = null ) {
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );

		return add_query_arg( array( $this->admin_notice_key => 'true' ), $location );
	}

	/**
	 * Do Admin Notices
	 *
	 * Displays any registered notices when the 'admin_notices' hook is called.
	 *
	 * @return [type] [description]
	 */
	public function do_admin_notices() {

		if ( isset( $_GET[$this->admin_notice_key] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>The ad logic you entered has been sanitized, please check below.</p></div>';
		}
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
	 * Add Actions
	 *
	 * Defines all the WordPress actions and filters used by this class.
	 */
	protected function _add_actions() {

		// register post type and taxonomies
		add_action( 'init', array( $this, '_register_advert_post_type' ) );
		add_action( 'init', array( $this, '_register_advert_size_taxonomy' ) );

		// back-end hooks
		add_action( 'manage_edit-advert_columns', array( $this, 'add_advert_columns' ) );
		add_action( 'manage_advert_posts_custom_column', array( $this, 'set_advert_column_values' ) );
		add_action( 'admin_notices', array( $this, 'do_admin_notices' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post_meta' ) );

		// cmb2 dependent hooks
		add_action( 'cmb2_admin_init', array( $this, 'add_responsive_sizes_metabox' ) );

		// add_filter( 'pre_get_posts', array( $this, 'sort_custom_fields' ) );
		add_filter( 'manage_edit-advert_sortable_columns', array( $this, 'set_sortable_advert_columns' ) );

	}

	/**
	 * Register Ads Post Type
	 *
	 * Defines and registers the "Ads" custom post type.
	 */
	public function _register_advert_post_type() {

		$labels = array(
			'name'               => _x( 'Ads', 'post type general name', 'wp_dfp_ads' ),
			'singular_name'      => _x( 'Ad', 'post type singular name', 'wp_dfp_ads' ),
			'menu_name'          => _x( 'Ads', 'admin menu', 'wp_dfp_ads' ),
			'name_admin_bar'     => _x( 'Ads', 'add new on admin bar', 'wp_dfp_ads' ),
			'add_new'            => _x( 'Add New', 'advert', 'wp_dfp_ads' ),
			'add_new_item'       => __( 'Add New Ad', 'wp_dfp_ads' ),
			'new_item'           => __( 'New Ad', 'wp_dfp_ads' ),
			'edit_item'          => __( 'Edit Ad', 'wp_dfp_ads' ),
			'view_item'          => __( 'View Ad', 'wp_dfp_ads' ),
			'all_items'          => __( 'All Ads', 'wp_dfp_ads' ),
			'search_items'       => __( 'Search Ads', 'wp_dfp_ads' ),
			'parent_item_colon'  => __( 'Parent Ad:', 'wp_dfp_ads' ),
			'not_found'          => __( 'No ads found.', 'wp_dfp_ads' ),
			'not_found_in_trash' => __( 'No ads found in Trash.', 'wp_dfp_ads' )
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_nav_menus'   => false,
			'show_in_menu'        => true,
			'menu_position'       => null,
			'menu_icon'           => 'dashicons-megaphone',
			'hierarchical'        => true,
			'supports'            => array( 'title' ),
			'taxonomies'          => array(),
			'has_archive'         => false,
			'query_var'           => false,
		);

		register_post_type( 'advert', $args );
	}

	/**
	 * Save Ads Meta Box
	 *
	 * Handles the sanitizing and saving of the advert meta box fields.
	 *
	 * @param int $post_id
	 */
	protected function _save_advert_meta_box( $post_id = null ) {

		if ( !current_user_can( 'manage_options', $post_id ) )
			return;

		foreach ( $this->advert_meta as $field ) {

			$name = str_replace( '-', '_', $field );

			if ( isset( $_POST[$name] ) ) {

				write_to_log($_POST[$name]);

				if ( 'advert_id' === $name ) {

					/*	MADE THIS SO THAT MORE THAN ONE ID COULD BE USED
						UNECESSARY AT MOMENT. CREATE ONE AD PER LOCATION
					$meta_value	= is_array( $_POST[$name] ) ? array_filter( $_POST[$name] ) : (array) $_POST[$name];

					foreach ( $meta_value as $key => $value ) {

						// sanitize text field
						$value				= sanitize_text_field( $value );
						// turn spaces into dashes
						$meta_value[$key]	= str_replace( ' ', '-', $value );

					}
					*/

					// sanitize text field
					$meta_value	= sanitize_text_field( $_POST[$name] );
					// turn spaces into dashes
					$meta_value	= str_replace( ' ', '-', $meta_value );

				} elseif ( 'advert_slot' === $name ) {

					$meta_value = $this->_generate_advert_slots( $post_id );

				} elseif ( 'advert_logic' === $name ) {

					/**
					 * NEED THIS FIXED:
					 * regex doesn't capture the last parenthesis in situations like the following:
					 * !in_category(array('2015-buyers-guide','2016-buyers-guide')) && !is_category(array('2015-buyers-guide','2016-buyers-guide'))
					 * Suspected culprit: nested parenthesis with the array inside the logic calls
					 *
					 * Sanitize the ad logic to prevent bad things from happening.
					 * Specifically only allow functions that start with is_* or
					 * in_* and whitelisted special characters ( !, &, | ) or
					 * the group that is with the php function date and its operator comparison to a string
					 */
					/*preg_match_all( '/((!?is_\w+\(.*?\))|(!?in_\w+\(.*?\))|(&&)|(\|\|)|(!?date+\(.*?\)+\s([<>!=]?=|[<>])+\s[\"\'].*?[\"\'])|([()]))/', stripslashes( $_POST[$name] ), $matches );

					$statement = implode( ' ', $matches[0] );

					$meta_value = preg_replace( '/\s([^a-z0-9<>!=]+)\s?$/', '', addslashes($statement) );*/

					$meta_value	= stripslashes( trim( $_POST[$name] ) );

					// add admin notice if the input doesn't match the output
					if ( $meta_value != $_POST[$name] ) {
						add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
					}

				} elseif ( 'advert_markup' === $name ) {

					/**
					 * This function makes sure that only the allowed HTML
					 * element names, attribute names and attribute values plus
					 * only sane HTML entities will occur in $meta_value.
					 */
					$allowed_tags = wp_kses_allowed_html( 'post' );

					$allowed_tags['script'] = array(
						'id' => array(),
						'type' => array()
					);

					// allow onclick attribute to anchor tags for tracking reasons
					$allowed_tags['a']['onclick'] = 1;

					$meta_value = wp_kses( $_POST[$name], $allowed_tags );

				} else {

					$meta_value = sanitize_text_field( $_POST[$name] );

				}

				update_post_meta( $post_id, "{$field}", $meta_value );

			}
		}
	}

	/**
	 * Register Ad Size Taxonomy
	 *
	 * Defines and registers the "Ad Size" taxonomy.
	 */
	public function _register_advert_size_taxonomy() {

		$labels = array(
			'name'                       => _x( 'Ad Sizes', 'taxonomy general name' ),
			'singular_name'              => _x( 'Ad Size', 'taxonomy singular name' ),
			'search_items'               => __( 'Search Ad Sizes' ),
			'popular_items'              => __( 'Popular Ad Sizes' ),
			'all_items'                  => __( 'All Ad Sizes' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Ad Size' ),
			'update_item'                => __( 'Update Ad Size' ),
			'add_new_item'               => __( 'Add New Ad Size' ),
			'new_item_name'              => __( 'New Ad Size Name' ),
			'separate_items_with_commas' => __( 'Separate ad sizes with commas' ),
			'add_or_remove_items'        => __( 'Add or remove ad sizes' ),
			'choose_from_most_used'      => __( 'Choose from the most used ad sizes' ),
			'not_found'                  => __( 'No ad sizes found.' ),
			'menu_name'                  => __( 'Ad Sizes' ),
		);

		$args = array(
			'hierarchical'          => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'public'                => false,
			'rewrite'               => false,
		);

		register_taxonomy( 'advert-size', 'advert', $args );
	}

	/**
	 * Get Post Meta
	 *
	 * Utility function used to consolidate the querying of multiple meta values
	 * for the given post.
	 *
	 * @param int $post_id
	 * @param array $fields
	 */
	protected function _get_post_meta( $post_id = null, $fields = array() ) {
		global $wpdb;

		$query = "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$post_id}";

		if ( !empty( $fields ) )
			$query .= " AND meta_key IN ( '". implode( "','", $fields ) ."' )";

		return $wpdb->get_results( $query, OBJECT_K );
	}

	/**
	 * Generate Advert Slots
	 *
	 * Dynamically generates the "slot name" for the given "advert" when it is saved.
	 *
	 * @param  int $post_id ID of the "advert" being saved.
	 *
	 * @return string Name of the slot the "advert" belongs to.
	 */
	protected function _generate_advert_slots( $post_id = null ) {

		$slots = array();

		$sizes		= wp_get_object_terms( $post_id, 'advert-size', array( 'orderby' => 'slug', 'order' => 'desc', 'fields' => 'slugs' ) );
		$ids		= get_post_meta( $post_id, 'advert-id' );

		if ( !empty( $ids ) && $ids !== false && !empty( $sizes ) && !is_wp_error( $sizes ) ) {

			foreach ( $ids as $id ) {

				// we have access to the ID so we delete the transient for the front end
				// and also the general ads meta transient since it will need to be updated
				delete_transient( $id . '_slot_ad' );
				delete_transient( 'ads_meta' );

				$id = str_replace('-', '_', $id);

				if ( 1 === count( $sizes ) ) {

					$size		= Wp_Dfp_Ads::_parse_slot_name( $sizes[0] );
					$slots[]	= sprintf( 'slot_%s_%s', $size, $id );

				} else {

					$ss		= $sizes;

					$size	= Wp_Dfp_Ads::_parse_slot_name( $ss[0] );
					unset( $ss[0] );

					$slot	= sprintf( 'slot_%s_%s_', $size, $id );

					foreach ( $ss as $size ) {
						$slot	.= Wp_Dfp_Ads::_parse_slot_name( $size ) .'_';
					}

					$slots[]	= rtrim( $slot, '_' );

				}

			}

		}

		return implode( ', ', $slots );
	}

}
