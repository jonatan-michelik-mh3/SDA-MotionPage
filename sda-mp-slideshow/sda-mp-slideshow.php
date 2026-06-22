<?php
/**
 * Plugin Name: S.D.A. Slideshow
 * Description: Adds slideshow functionality to Motion.page's ImageSequence.
 * Version: 1.2
 * Author: S.D.A.
 */

/**
 * Baked data for EXISTING animations + page routing.
 *
 * Loaded from the separate data file data/slideshow-data.php (generated from the
 * one-time Motion.page DB export). Structure:
 *   [ 'animations' => [ timelineUID => [numSlides, timeSlideN] ],
 *     'pages'      => [ 'by_slug' => [slug => uid], 'by_post' => [post_id => uid] ] ]
 *
 * Slide data belongs to the animation (timelineUID); pages only route to it.
 * NEW sequences do NOT belong here — set those via Elementor data-attributes
 * (data-mp-slides / data-mp-times). See docs/data-mapping.md.
 *
 * @return array
 */
function sda_mp_slideshow_data_map() {
	static $map = null;
	if ( null === $map ) {
		$file = plugin_dir_path( __FILE__ ) . 'data/slideshow-data.php';
		$map  = file_exists( $file ) ? (array) require $file : array();
	}
	return $map;
}

/**
 * Resolve the slide data for the current page: page -> timelineUID -> animation data.
 *
 * @return array<string, int|float>|null
 */
function sda_mp_slideshow_current_data() {
	$map = sda_mp_slideshow_data_map();
	if ( empty( $map['animations'] ) ) {
		return null;
	}

	// The current page can be a WooCommerce product category (a `product_cat`
	// taxonomy TERM) or a regular post/page. Motion.page assigns sequences by
	// slug, so read the slug from whichever object is queried.
	$obj  = get_queried_object();
	$slug = '';
	if ( $obj instanceof WP_Term ) {
		$slug = $obj->slug;        // product_cat (or any taxonomy) term
	} elseif ( $obj instanceof WP_Post ) {
		$slug = $obj->post_name;   // regular page / post
	}

	$uid = null;
	if ( $slug && isset( $map['pages']['by_slug'][ $slug ] ) ) {
		$uid = $map['pages']['by_slug'][ $slug ];
	} else {
		// Fallback by queried-object id (product_cat term_id, or post ID).
		$qid = (int) get_queried_object_id();
		if ( $qid && isset( $map['pages']['by_post'][ $qid ] ) ) {
			$uid = $map['pages']['by_post'][ $qid ];
		}
	}

	if ( $uid && isset( $map['animations'][ $uid ] ) ) {
		return $map['animations'][ $uid ];
	}
	return null;
}

function sda_mp_slideshow() {
	$handle = 'mp-ImageSequence';

	// Only load when Motion.page actually enqueued the image sequence on this page.
	// (Handle is 'mp-ImageSequence' in both legacy-GSAP and SDK mode of MP v3.)
	if ( ! wp_script_is( $handle, 'enqueued' ) ) {
		return;
	}

	$file     = 'js/test-external.js';
	$url      = plugin_dir_url( __FILE__ ) . $file;
	$path     = plugin_dir_path( __FILE__ ) . $file;
	$mod_time = @filemtime( $path );

	wp_enqueue_script( 'test-external', $url, array(), $mod_time, array(
		'strategy' => 'defer',
	) );

	// Inject this page's animation data ahead of test-external.js so seqData()
	// can read window.SDA_SLIDESHOW_DATA. (New sequences use Elementor
	// data-attributes instead and don't need this.)
	static $injected = false;
	if ( ! $injected ) {
		$data = sda_mp_slideshow_current_data();
		if ( $data ) {
			wp_add_inline_script(
				'test-external',
				'window.SDA_SLIDESHOW_DATA = ' . wp_json_encode( $data ) . ';',
				'before'
			);
			$injected = true;
		}
	}
}

// Run AFTER Motion.page, which enqueues 'mp-ImageSequence' on wp_enqueue_scripts
// at priority 999. 9999 is comfortably after that (with headroom if MP bumps its
// priority) so wp_script_is() below reliably sees whether the sequence was enqueued.
add_action( 'wp_enqueue_scripts', 'sda_mp_slideshow', 9999 );
