<?php
/**
 * Plugin Name: S.D.A. Slideshow
 * Description: Adds slideshow functionality to Motion.page's ImageSequence.
 * Version: 1.0
 * Author: S.D.A.
 */
function sda_mp_slideshow() {
    $handle = 'mp-ImageSequence';
	$list = 'enqueued';
	$file = 'js/test-external.js';
	$url = plugin_dir_url( __FILE__ ) . $file;
	$path = plugin_dir_path( __FILE__ ) . $file;
	$mod_time = @filemtime($path);
	if (wp_script_is( $handle, $list )) {
    	wp_enqueue_script( 'test-external', $url, array(), $mod_time, array(
        'strategy' => 'defer'
    	) );
	} else {
	return;
	}
}

add_action( 'wp_print_scripts', 'sda_mp_slideshow' );