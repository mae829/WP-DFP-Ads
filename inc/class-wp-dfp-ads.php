<?php

if ( ! defined( 'WP_DFP_ADS_DIR' ) ) {
	exit;
}

/**
 * Wordpress DFP Ads
 */

class Wp_Dfp_Ads {

	var $advert_meta = array(
		'_advert-id',
		'_advert-slot',
		'_advert-logic',
		'_advert-markup',
		'_advert-exclude-lazyload',
		'_advert-exclude-refresh',
		'_advert-mapname',
		'_advert-breakpoints'
	);

	static $instance = false;

	public static $lazyload_status;

	public static $refresh_status;

	public static $refresh_time;

	public function __construct() {

		// Set up our initial options
		$wp_dfp_ads_settings	= get_option( 'wp_dfp_ads_settings' );
		self::$lazyload_status	= !empty( $wp_dfp_ads_settings['lazy-load'] ) ? true : false;
		self::$refresh_status	= !empty( $wp_dfp_ads_settings['refresh'] ) ? true : false;
		self::$refresh_time		= !empty( $wp_dfp_ads_settings['refresh-time'] ) ? $wp_dfp_ads_settings['refresh-time'] * 1000 : 30000;

		$this->_add_actions();
	}

	/**
	 * Generate Ad Slots
	 *
	 * Queries the database for all published advert posts and defines them in
	 * the HEAD of the document accordingly.
	 */
	public function generate_ad_slots() {
		global $wpdb, $post;

		$ads	= array();

		// check for the transient of the ads meta.
		// this gets flushed every time an ad is saved by _generate_advert_slots()
		// it is done in the GENERATE function because we need the proper name to delete the transient
		// _save_advert_meta_box() does not have the required info to do so. but _generate_adver_slots() does
		if ( false === $ads = get_transient( 'ads_meta' ) ) {

			$ads_meta_args	= array(
				'post_type'			=> 'advert',
				'posts_per_page'	=> -1,
				'fields'			=> 'ids',
				'meta_query'		=> array(
					'relation'	=> 'OR',
					array(
						'key'		=> '_advert-markup',
						'value'		=> '',
					),
					array(
						'key'		=> '_advert-markup',
						'compare'	=> 'NOT EXISTS'
					)
				),
				'no_found_rows'				=> true,
				'update_post_meta_cache'	=> false,
				'ignore_sticky_posts'		=> true,
			);

			$ads_meta_object	= new WP_Query( $ads_meta_args );

			$the_taxonomies = array();

			foreach ( $ads_meta_object->posts as $key => $ad_id ) {

				$ads[$key]	= self::_get_meta(
					$ad_id,
					array(
						'_advert-slot',
						'_advert-logic',
						'_advert-mapname',
						'_advert-breakpoints',
						'_advert-exclude-lazyload',
						'_advert-exclude-refresh'
					)
				);

				// Break up the advert-slot because it could be more than one
				// and unset the original value
				$ads[$key]['advert-slots']	= explode( ',', $ads[$key]['_advert-slot']  );
				unset( $ads[$key]['_advert-slot'] );

				// Change up the advert logic so it works in eval
				$ads[$key]['_advert-logic'] = !empty( $ads[$key]['_advert-logic'] ) ? "return ( ". $ads[$key]['_advert-logic'] ." ? true : false );" : '';

				// Get our taxonomies and add them to the $ads array
				$the_taxonomies	= wp_get_object_terms(
					$ad_id,
					array(
						'advert-size'
					)
				);

				foreach ( $the_taxonomies as $tax ) {

					if ( $tax->taxonomy === 'advert-size' ) {

						$ads[$key]['advert-sizes'][]	= $tax->name;

					}

				}

			}

			set_transient( 'ads_meta', $ads );
			echo '<!-- wpdfpads-debug: ads_meta new query -->' ."\n\t\t";
		} else {
			echo '<!-- wpdfpads-debug: ads_meta from cache -->' ."\n\t\t";
		}

		// make sure we have ads before proceeding
		if ( !is_wp_error( $ads ) && !empty( $ads ) ) {

			$registered_slots = array();

			/**
			 * Set up and get all our slots, ad maps, etc to attach later to our actual HTML we will generate
			 */
			$placements			= array();
			$errors				= '';
			$slot_definitions	= '';
			$ad_maps			= '';

			// get DFP Keywords string
			$location = $this->generate_dfp_keywords();

			// Allow themes/plugins to hook into the ads and filter ads as wanted
			$ads	= apply_filters( 'wp_dfp_ads_filter', $ads );

			// loop through slots and generate DFP definitions
			foreach ( $ads as $ad ) {

				foreach ( $ad['advert-slots'] as $i => $slot ) {

					if ( false === in_array( $slot, $placements ) ) {

						// evaluate advert logic
						if ( !empty( $ad['_advert-logic'] ) && false === eval( $ad['_advert-logic'] ) ) {
							continue;
						}

						// prevent duplicate ad calls for the same location
						$placements[] = $slot;

						// sort sizes by value desc
						rsort( $ad['advert-sizes'] );

						$sizes	= array();

						foreach ( $ad['advert-sizes'] as $key => $size ) {

							// manipulate the ad size
							$size		= str_replace( 'x', ',', $size );
							// wrap fluid size in single parenthesis, else wrap it in brackets
							$size		= $size == 'fluid' ? '\'fluid\'' : "[$size]";

							$sizes[]	= $size;

						}

						$sizes	= implode( ',', $sizes );

						// if there are multiple ad sizes, convert to multi-dimensional array
						if ( 1 < count( $ad['advert-sizes'] ) ) {
							$sizes = "[{$sizes}]";
						}

						// define slot markup
						$slot_markup	= "%s = googletag.defineSlot( '%s', %s, '%s' )";

						// attach the size map name if it exists in metadata
						// and attach the defined map to $ad_map to print later
						if ( !empty( $ad['_advert-mapname'] ) && !empty( $ad['_advert-breakpoints'] ) ) {

							$slot_markup	.= ".defineSizeMapping( {$ad['_advert-mapname']} )";

							$ad_maps		.= $this->generate_ad_maps( $ad );

						}

						$slot_markup	.= ".addService( googletag.pubads() )";

						// make sure the ad collapses if it is not part of the lazy-load set up, in case nothing gets returned
						$slot_markup	.= self::$lazyload_status && !empty( $ad['_advert-exclude-lazyload'] ) ? ".setCollapseEmptyDiv(true);\n\t\t\t\t" : ";\n\t\t\t\t";

						// add the slot to our lazyload object for later use if the feature is turned on AND not excluded
						$slot_markup	.= self::$lazyload_status && empty( $ad['_advert-exclude-lazyload'] ) ? "lazyload_slots['". $slot ."'] = ". $slot .";\n\t\t\t\t" : '';

						// add the slot to our refresh object for later use if the feature is turned on AND not excluded
						$slot_markup	.= self::$refresh_status && empty( $ad['_advert-exclude-refresh'] ) ? "refresh_slots['". $slot ."'] = ". $slot .";\n\t\t\t\t" : '';

						$div = str_replace( 'slot_', 'div_', $slot );

						$slot_definitions .= sprintf(
							$slot_markup,
							$slot,
							$location,
							$sizes,
							$div
						);

						$registered_slots[] = $slot;

					} else {

						$errors .= sprintf( "ERROR: %s was called multiple times.\n\t", $slot );

					}

				}

			}

			/**
			 * if there was no ads for this page, do not print any of the code
			 * (so if $slot_definition is blank)
			 */
			if ( $slot_definitions === '' ) {
				return;
			}

			$html = '<script>' ."\n\t\t\t";

				if ( self::$lazyload_status ) {
					$html .= 'var lazyload_slots = {};' ."\n\t\t\t";
				}

				if ( self::$refresh_status ) {
					$html .= 'var refresh_slots = {};' ."\n\t\t\t";
					$html .= 'var refresh_time = '. self::$refresh_time  .';'."\n\t\t\t";
				}

				// define googletag before anything
				$html .= 'var googletag = googletag || {};' ."\n\t\t\t";
				$html .= 'googletag.cmd = googletag.cmd || [];' ."\n\t\t\t";

				// define DFP settings
				// load DFP script Asynchronously
				$html .= '(function() {' ."\n\t\t\t\t";
					$html .= 'var gads = document.createElement(\'script\');' ."\n\t\t\t\t";
					$html .= 'gads.async = true;' ."\n\t\t\t\t";
					$html .= 'gads.type = \'text/javascript\';' ."\n\t\t\t\t";
					$html .= 'var useSSL = \'https:\' == document.location.protocol;' ."\n\t\t\t\t";
					$html .= 'gads.src = (useSSL ? \'https:\' : \'http:\') + \'//www.googletagservices.com/tag/js/gpt.js\';' ."\n\t\t\t\t";
					$html .= 'var node = document.getElementsByTagName(\'script\')[0];' ."\n\t\t\t\t";
					$html .= 'node.parentNode.insertBefore(gads, node);' ."\n\t\t\t";
				$html .= '})();' ."\n\t\t\t";

				$html .= 'googletag.cmd.push( function() {'  ."\n\t\t\t\t";
					$html .= ( trim( $ad_maps ) != '') ? $ad_maps ."\n\t\t\t\t" : '';
					$html .= $slot_definitions ."\n\t\t\t\t";

					// add event listener to remove any ad_fallback
					$html .= 'googletag.pubads().addEventListener( \'slotRenderEnded\', function( event ) {' ."\n\t\t\t\t\t";
						$html .= 'var slotId  = event.slot.getSlotElementId();' ."\n\t\t\t\t\t";
						$html .= 'var slot    = document.getElementById( slotId );' ."\n\t\t\t\t\t";
						$html .= 'if ( slot !== null && !event.isEmpty ) {' ."\n\t\t\t\t\t\t";
							// add "advert-loaded" to div
							// $html .= 'slot.className += " advert-loaded";'; (leaving this in here because it works for older IE versions but repeats when doing ad refresh)
							$html .= 'slot.classList.add( \'advert-loaded\' );' ."\n\t\t\t\t\t\t";
							// hide any fallback content within the parent div of the ad
							$html .= 'var parentDiv  = slot.parentNode;' ."\n\t\t\t\t\t\t";
							$html .= 'var fallbacks  = parentDiv.getElementsByClassName( \'ad_fallback\' );' ."\n\t\t\t\t\t\t";
							$html .= 'for ( var i = 0; i < fallbacks.length; i++ ) {' ."\n\t\t\t\t\t\t\t";
								$html .= 'fallbacks[i].style.display = \'none\';' ."\n\t\t\t\t\t\t";
							$html .= '}' ."\n\t\t\t\t\t";
						$html .= '}' ."\n\t\t\t\t";
					$html .= '});' ."\n\t\t\t\t";

					if ( self::$lazyload_status ) {
						$html .= 'googletag.pubads().disableInitialLoad();' ."\n\t\t\t\t";
					} else {
						$html .= 'googletag.pubads().enableAsyncRendering();' ."\n\t\t\t\t";
						$html .= 'googletag.pubads().collapseEmptyDivs(true);' ."\n\t\t\t\t";
					}
					$html .= 'googletag.pubads().enableSingleRequest();' ."\n\t\t\t\t";
					$html .= 'googletag.pubads().setCentering(true);' ."\n\t\t\t\t";
					$html .= 'googletag.enableServices();' ."\n\t\t\t";

				$html .= '} );' ."\n\t\t";

				// Add debugging info ( if exists )
				if ( !empty( $errors ) ) {
					$html .= "\n\t/* \n\t\t {$errors} */ \n";
				}

			$html .= '</script>' ."\n\n";

			echo $html;
		}

	}

	/**
	 * Generate DFP Keywords
	 *
	 * Generates the DFP Keyword string which identifies the current page.
	 *
	 * @param  $append_prefix []
	 *
	 * @return string String containing the necessary DFP keyword information for the current page.
	 */
	public function generate_dfp_keywords( $append_prefix = true ) {

		$prefix	= wp_dfp_ads_get_option( 'dfp-prefix' ) ? wp_dfp_ads_get_option( 'dfp-prefix' ) : '';

		/**
		 * Enforce all ad keyword rules and generate a comma-separated list
		 * of ad keywords for the current WordPress location/context.
		 * Refer to ad rules documentation for plain English explanation of
		 * ad rules.
		 */
		$suffix = '';

		/**
		 * Every home page ( e.g. http://www.example.com,
		 * http://sub.example.com ) gets the keyword "home"
		 */
		if ( is_home() || is_front_page() ) {

			$suffix = "/home";

		} elseif ( is_page() ) {

			global $post;

			$post_name = str_replace( '-', '_', $post->post_name );

			if ( $post->post_parent != 0 ) {

				$parent = get_post( $post->post_parent );
				$parent_name = str_replace( '-', '_', $post_data->post_name );

				$suffix .= "/{$post_name_parent}";

			}

			$suffix .= "/{$post_name}";

		} elseif ( is_category() ) {

			$category = get_queried_object();

			/**
			 * Every category page should return it's parents ( if exist ), then
			 * itself.
			 */
			if ( !empty( $category->category_parent ) ) {

				$parents = get_category_parents( $category->cat_ID );

				if ( !is_wp_error( $parents ) && !empty( $parents ) ) {

					foreach ( explode( '/', $parents ) as $cat ) {

						$term	= $this->_sanitize_term( $cat );

						$suffix .= '/'. $term;

					}

				}

			} else {

				$suffix .= '/'. $this->_sanitize_term( $category->slug );

			}

		} elseif ( is_tag() ) {

			$tag	= single_tag_title( '', false );
			$suffix	.= '/'. $this->_sanitize_term( $tag );

		} elseif ( is_single() ) {

			global $post;

			$categories	= ( $post->post_parent == '0' ? get_the_category( $post->ID ) : get_the_category( $post->post_parent ) );

			$keywords		= array();

			foreach ( $categories as $category ) {

				$parents = get_category_parents( $category->cat_ID );

				if ( !is_wp_error( $parents ) && !empty( $parents ) ) {

					foreach ( explode( '/', $parents ) as $cat ) {

						$cat = $this->_sanitize_term( $cat );

						if ( !empty( $cat ) && !in_array( $cat, $keywords ) ) {

							$keywords[] = $cat;

							$suffix .= '/'. $category->slug;

						}

					}

				}

			}

		} elseif ( is_author() ) {

			$curauth = get_user_by('slug', get_query_var('author_name'));
			$suffix .= "/{$curauth->user_nicename}";

		} elseif ( is_search() ) {

			$suffix .= "/search";

		/**
		 * Blanket rules for everything else is to just grab any
		 * categories / parent categories
		 */
		} elseif ( !is_author() && !is_404() ) {

			global $post;

			$keywords = array();

			$categories = ( $post->post_parent == '0' ? get_the_category( $post->ID ) : get_the_category( $post->post_parent ) );

			if ( !empty( $categories ) ) {

				foreach ( $categories as $category ) {

					$parents = get_category_parents( $category->cat_ID );

					if ( !is_wp_error( $parents ) && !empty( $parents ) ) {

						foreach ( explode( $parents, '/' ) as $cat ) {

							$cat = $this->_sanitize_term( $cat );

							if ( !in_array( $cat, $keywords ) ) {

								$keywords[] = $cat;

								$suffix .= "/{$cat}";

							}

						}

					}

				}

			}

		}

		/**
		 * Filters the suffix.
		 * Allow for manipulation of the DFP keywords
		 *
		 * Returning false to this hook is the recommended way to never show an in-article ad
		 * (even w/one defined in the admin area).
		 *
		 * @param string	$suffix		String with the generated suffix so far
		 */
		$suffix	= apply_filters( 'wp_dfp_ads_keywords', $suffix );

		// Make sure the $suffix was returned with a leading slash
		if ( 0 !== strpos( $suffix, '/' ) ) {
			$suffix = '/'. $suffix;
		}

		return $append_prefix ? "{$prefix}{$suffix}" : "{$suffix}";
	}

	/**
	 * Generates an associative array with what's needed to create an ad map
	 *
	 * @param  array	$ad		array with needed metadata and info for map
	 *
	 * @return array			array with keys 'map' and 'map_slot_markup'
	 */
	public function generate_ad_maps( $ad = null ) {

		// making sure this isn't accessed randomly
		if ( $ad === null ) {
			return;
		}

		// making ABSOLUTELY sure this wasn't executed without the right data
		if ( empty( $ad['_advert-mapname'] ) || empty( $ad['_advert-breakpoints'] ) ) {
			return;
		}

		$ad_map	= "var {$ad['_advert-mapname']} = googletag.sizeMapping()";

		// attach each breakpoint to the map
		foreach ( $ad['_advert-breakpoints'] as $ad_breakpoint ) {

			$breakpoint					= $ad_breakpoint['breakpoint'] != '-' ? $ad_breakpoint['breakpoint'] : '0';
			$ad_breakpoint['adsize']	= $ad_breakpoint['adsize'] != '' ? $ad_breakpoint['adsize'] : array( '0x0' );

			foreach ( $ad_breakpoint['adsize'] as $key => $single_breakpoint ) {

				if ( $single_breakpoint == 'fluid' ) {
					$ad_breakpoint['adsize'][$key]	= '\'fluid\'';
				} else {
					$ad_breakpoint['adsize'][$key]	= "[$single_breakpoint]";
				}

			}

			rsort( $ad_breakpoint['adsize'] );

			$breakpoint_sizes	= ($ad_breakpoint['adsize'][0] != '[0x0]') ? implode( ',', $ad_breakpoint['adsize'] ) : '[]';
			$breakpoint_sizes	= count( $ad_breakpoint['adsize'] ) > 1 ? '[' . $breakpoint_sizes . ']' : $breakpoint_sizes;
			$breakpoint_sizes	= str_replace( 'x', ',', $breakpoint_sizes );

			$ad_map			.= ".addSize([$breakpoint, 0], $breakpoint_sizes)";

		}

		$ad_map	.= ".build();\n\t\t\t\t";


		return $ad_map;

	}

	/**
	 * Singleton
	 *
	 * Returns a single instance of the current class.
	 */
	public static function singleton() {

		if ( !self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Display Ad
	 *
	 * Displays the appropriate ad for the given slot.
	 *
	 * @param  mixed	$slot	Name of slot (aka "advert-slot") to query.
	 *
	 * @return					markup to echo out
	 */
	public static function display_ad( $slot = null ) {
		global $wpdb;

		if ( false === $ads = get_transient( $slot . '_slot_ad' ) ) {

			$ads	= array();

			$args	= array(
				'post_type'					=> 'advert',
				'posts_per_page'			=> -1,
				'fields'					=> 'ids',
				'meta_key'					=> '_advert-id',
				'meta_value'				=> $slot,
				'no_found_rows'				=> true,
				'update_post_meta_cache'	=> false,
				'ignore_sticky_posts'		=> true,
			);

			$ads_object	= new WP_Query( $args );

			foreach ( $ads_object->posts as $ad_id ) {

				$ads[]	= self::_get_meta(
					$ad_id,
					array(
						'_advert-slot',
						'_advert-logic',
						'_advert-markup',
						'_advert-exclude-lazyload',
						'_advert-exclude-refresh'
					),
					'post',
					OBJECT
				);

			}

			set_transient( $slot . '_slot_ad', $ads );

		}

		$pattern	= !is_array( $slot ) ? $slot : implode('|', $slot);

		if ( !empty( $ads ) ) {

			$placements = array();

			foreach ( $ads as $ad ) {

				if ( !empty( $ad->{'_advert-logic'} ) ) {

					$logic = $ad->{'_advert-logic'};
					$logic = "return ( {$logic} ? true : false );";

					if ( false === eval( $logic ) ) {
						continue;
					}

				}

				if ( !empty( $ad->{'_advert-markup'} ) ) {

					$placements[] = $ad->{'_advert-markup'};

				} else {

					$pattern = str_replace( '-', '_', $pattern );

					preg_match_all( "/(([a-z0-9_]+)($pattern)([a-z0-9_]+)?)/", $ad->{'_advert-slot'}, $matches );

					if ( !empty( $matches[0] ) ) {

						foreach ( $matches[0] as $match ) {

							$div = str_replace( 'slot_', 'div_', $match );

							$html = '<div id="'. $div .'">';
								$html .= '<script>';
									$html .= 'googletag.cmd.push(function() { ';
										$html .= 'googletag.display( "'. $div .'" ); ';
										$html .= ( self::$lazyload_status && !empty( $ad->{'_advert-exclude-lazyload'} ) ) ? 'googletag.pubads().refresh(['. $match .']);' : '';
									$html .= '});';
								$html .= '</script>';
							$html .= '</div>';

							$placements[] = $html;

						}

					}

				}

			}

			/**
			 * If $slot is an array then the return value should be an array
			 * containing the markup for each requested placement; otherwise
			 * return the markup for the requested placement.
			 */
			if ( is_array( $slot ) ) {

				return $placements;

			} else {

				return ( !empty( $placements[0] ) ? $placements[0] : '' );

			}

		}

	}

	/**
	 * Logic for adding the in-article ad to content
	 *
	 * @param  string $content The content before logic is applied
	 * @return string          The content with/without in-article ad
	 */
	public static function inarticle_ad( $content ) {

		$inarticle_ad	= Wp_Dfp_Ads::display_ad( 'inarticle' );

		// check ad exists
		if ( $inarticle_ad == '' )
			return $content;

		// check that it's a single post
		if ( is_single() ) {

			// wrap the ad in the correct markup
			$ad_text = '<aside class="advert advert_location_inline">
				<div class="advert__wrap">' . $inarticle_ad . '</div>
			</aside>';

			/**
			 * Filters the inarticle ad.
			 *
			 * Returning false to this hook is the recommended way to never show an in-article ad
			 * (even w/one defined in the admin area).
			 *
			 * @since 1.2
			 *
			 * @param string $ad_wrapper String to use and wrap the article.
			 */
			$ad_text	= apply_filters( 'wp_dfp_ads_inarticle', $ad_text, $inarticle_ad );

			// if the user for some reason returned false on the filter, simply return the content
			if ( !$ad_text )
				return $content;

			$paragraphs     = preg_split('~(?<=</p>)~', wptexturize($content), null, PREG_SPLIT_NO_EMPTY);
			$valid_ps_grid  = array();
			$valid_ps       = array();

			/**
			 *  Check that the paragraphs in question are valid paragraphs
			 *  - Must be longer than 15 words
			 *  - Decided to go with min. characters, but leaving the logic for word count in here (140 like Twitter)
			 *  These would be empty paragraphs left behind by editors or simple paragraphs that would make it weird to display the ad before/after
			 *  - Must not contain the following elements: ul, li, img
			 *  Can mean they are lists. Regular Exp can be changed to add other undesired elements
			 **/
			foreach ( $paragraphs as $k => $paragraph ) {

				$word_count = count( explode( ' ', $paragraph ) );
				$char_count = strlen( $paragraph );

				if ( preg_match( "~<(?:ul|li|img|table|td|tbody|td)[ >]~", $paragraph ) || $char_count < 140 ) {

					$valid_ps_grid[$k]  = 0;

				} else {

					$valid_ps_grid[$k]  = 1;
					$valid_ps[]         = $paragraph;

				}

			}
			$valid_ps_count       = count( $valid_ps );
			$valid_ps_characters  = strlen( utf8_decode( implode(' ', $valid_ps) ) );

			// only apply inarticle ad if there is 8 VALID paragraphs or more AND the VALID content is more than 1200 characters long
			if ( $valid_ps_count >= 8 && $valid_ps_characters > 1200 ) {

				// split into two but check if those two paragraphs are VALID paragraphs
				// use the original paragraph count to check for where to split
				// use $midpoint to check the VALID pagraph grid by key if it is valid
				$paragraph_count    = count( $paragraphs );
				$midpoint           = floor( $paragraph_count / 2 );
				$first              = array_slice($paragraphs, 0, $midpoint, true );
				$new_content        = array();

				$second = array_slice( $paragraphs, $midpoint, null, true );

				if ( $valid_ps_grid[$midpoint] == 1 && $valid_ps_grid[$midpoint - 1] == 1 ) {

					// both mid paragraphs at middle are valid paragraphs, prepare content and ad for display
					$new_content[] = implode( ' ', $first );
					$new_content[] = $ad_text;
					$new_content[] = implode( ' ', $second );

				} else {

					// $key_flag: to identify where in the $paragraphs array we should place the ad (if not in mid)
					$key_flag   = '';
					$the_search = array( array_reverse($first, true), $second );

					foreach ( $the_search as $piece ) {

						if ( $key_flag != '' ) break;

						$midkey = key($piece) + floor( count( $piece ) / 2 );

						foreach ( $piece as $k => $p ) {

							if ( $valid_ps_grid[$k] == 1 && $valid_ps_grid[$k + 1] == 1 ) {
								$key_flag = $k;

								break;
							}

							if ( $k  == $midkey ) break;

						}

					}

					if ( $key_flag != '' ) {

						$new_content[] = implode( ' ', array_slice( $paragraphs, 0, $key_flag + 1, true ) );
						$new_content[] = $ad_text;
						$new_content[] = implode( ' ', array_slice( $paragraphs, $key_flag + 1, count($paragraphs) - 1, true ) );

					} else {

						// else there is no valid point where to display ad, return the content
						return $content;

					}
				}

				// since new_content is in array, implode and set it as content, then return this new content
				$content =  implode( ' ', $new_content );

			}

		} // end if ( is_single() )

		// $content is returned, unaltered if conditions not met, and with ad if proper placement and conditions allow it
		return $content;

	}

	/**
	 * Load scripts for the front-end required by the plugin.
	 */
	public function enqueue_files() {

		if ( self::$lazyload_status ) {
			// make sure jQuery is enqueued
			if ( !wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery', 'https://code.jquery.com/jquery-2.2.4.min.js', array(), '2.2.4', true );
			}

			wp_enqueue_script( 'wpdfpads-lazyload', WP_DFP_ADS_URL .'js/lazy-load.min.js', array('jquery'), WP_DFP_ADS_VERSION, true );
		}

		if ( self::$refresh_status ) {
			// make sure jQuery is enqueued
			if ( !wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery', 'https://code.jquery.com/jquery-2.2.4.min.js', array(), '2.2.4', true );
			}

			wp_enqueue_script( 'wpdfpads-refresh', WP_DFP_ADS_URL .'js/refresh.min.js', array('jquery'), WP_DFP_ADS_VERSION, true );
		}

	}

	/**
	 * Add Actions
	 *
	 * Defines all the WordPress actions and filters used by this class.
	 */
	protected function _add_actions() {

		// front-end hooks
		add_action( 'wp_head', array( $this, 'generate_ad_slots' ), 20 );

		// front-end filters
		add_filter( 'the_content', array( $this, 'inarticle_ad' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_files' ) );

	}

	/**
	 * Parse Slot Name
	 *
	 * Transforms the given "advert size" into the appropriate DFP slot name.
	 *
	 * @param  string $slot_size Slug of the given "advert-size" term.
	 *
	 * @return string Formatted slot size name.
	 */
	public static function _parse_slot_name( $slot_size = null ) {
		return preg_replace( '/-?x-?/', '_', $slot_size );
	}

	/**
	 * Sanitize Term
	 *
	 * Sanitizes the given term so it doesn't break DFP.
	 *
	 * @param string $term Term to be added to keyword string.
	 *
	 * @return string Formatted keyword term.
	 */
	public static function _sanitize_term( $term = null ) {

		$term = str_replace( '_&amp;', '', $term );
		$term = str_replace( '_&', '', $term );
		$term = str_replace( "'", '', $term );
		$term = str_replace( ',', '', $term );
		$term = str_replace( ' ', '_', $term );
		$term = str_replace( '-', '_', $term ); // DFP doesnt like dashes in it's ad units
		$term = preg_replace('/[^a-zA-Z0-9-_]/', '', $term);
		$term = strtolower( $term );

		return $term;
	}

	/**
	 * Get Meta
	 *
	 * Utility function used to consolidate the quering of multiple meta values
	 * for the given object.
	 *
	 * @param int	 	$id ID of the current object.
	 * @param mixed		$fields Array/string containing meta field(s) to retrieve from database.
	 * @param string	$type Type of metadata request. Options: post/term/user
	 * @param constant	$output pre-defined constant for return type (OBJECT/ARRAY_A)
	 *
	 * @return mixed	MySQL object/Associative Array containing returned post metadata.
	 */
	public static function _get_meta( $id = null, $fields = array(), $type = 'post', $output = ARRAY_A ) {
		global $wpdb;

		$fields		= esc_sql( $fields );
		$values_arr	= array();
		$values_obj	= new stdClass();
		$dbtable	= $wpdb->{$type.'meta'};
		$column_id	= $type . '_id';
		$id			= $id == null ? get_the_ID() : $id ;

		// make sure ID exists or query will return error. or else return NULL
		if ( is_null( $id ) || $id === false ) {
			return null;
		}

		$query		= "SELECT meta_key, meta_value FROM {$dbtable} WHERE {$column_id} = {$id}";

		if ( !empty( $fields ) ) {

			if ( is_array( $fields ) ) {
				$query .= " AND meta_key IN ('". implode("','", $fields) ."')";
			} else {
				$query .= " AND meta_key = '{$fields}'";
			}
		}

		$results	=  $wpdb->get_results( $query, OBJECT_K );

		foreach ( $results as $key => $result ) {
			$values_arr[$key]	= is_serialized( $result->meta_value ) ? unserialize( $result->meta_value ) : $result->meta_value;
			$values_obj->{$key}	= is_serialized( $result->meta_value ) ? unserialize( $result->meta_value ) : $result->meta_value;
		}

		if ( !is_array( $fields ) && !empty( $values_arr[$fields] ) ) {

			return $output == ARRAY_A ? $values_arr[$fields] : $values_obj[$fields];

		}

		return $output == ARRAY_A ? $values_arr : $values_obj;
	}

}
