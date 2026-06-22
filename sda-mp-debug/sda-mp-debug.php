<?php
/**
 * Plugin Name: S.D.A. Slideshow — Debug
 * Description: Console diagnostics for the SDA slideshow / Motion.page integration. Activate temporarily, open the browser console, then deactivate. Loads for admins, or for anyone on a URL with ?sda_debug=1.
 * Version: 1.0
 * Author: S.D.A.
 */

function sda_mp_debug_enqueue() {
	// Only for admins, or when explicitly requested via ?sda_debug=1 (so it can be
	// triggered in an incognito / not-logged-in window too).
	if ( ! current_user_can( 'manage_options' ) && ! isset( $_GET['sda_debug'] ) ) {
		return;
	}

	$file = 'js/debug.js';
	$url  = plugin_dir_url( __FILE__ ) . $file;
	$path = plugin_dir_path( __FILE__ ) . $file;

	wp_enqueue_script( 'sda-mp-debug', $url, array(), @filemtime( $path ), array( 'strategy' => 'defer' ) );

	// Server-side facts the browser cannot easily see — passed to the JS.
	$obj   = get_queried_object();
	$qtype = 'none';
	$qslug = '';
	if ( $obj instanceof WP_Term ) {
		$qtype = 'WP_Term (' . $obj->taxonomy . ')';
		$qslug = $obj->slug;
	} elseif ( $obj instanceof WP_Post ) {
		$qtype = 'WP_Post (' . $obj->post_type . ')';
		$qslug = $obj->post_name;
	}

	$php = array(
		'queried_type'      => $qtype,
		'queried_slug'      => $qslug,
		'queried_id'        => (int) get_queried_object_id(),
		'is_product_cat'    => function_exists( 'is_product_category' ) ? is_product_category() : null,
		'mp_imagesequence'  => wp_script_is( 'mp-ImageSequence', 'enqueued' ),
		'mp_gsap'           => wp_script_is( 'mp-gsap', 'enqueued' ),
		'mp_motion_sdk'     => wp_script_is( 'mp-motion-sdk', 'enqueued' ),
		'sda_test_external' => wp_script_is( 'test-external', 'enqueued' ),
	);

	wp_add_inline_script(
		'sda-mp-debug',
		'window.SDA_DEBUG_PHP = ' . wp_json_encode( $php ) . ';',
		'before'
	);
}
// Run after Motion.page (priority 999) and the SDA plugin (9999) so the
// wp_script_is() checks below reflect what they enqueued.
add_action( 'wp_enqueue_scripts', 'sda_mp_debug_enqueue', 100000 );
