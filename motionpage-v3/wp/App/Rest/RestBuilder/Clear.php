<?php

/**
 * Motion.page
 *
 * @package   motionpage
 * @author    CoreWeb OÜ
 * @copyright 2025 CoreWeb OÜ
 * @license   EULA + GPLv2
 * @link      https://motion.page
 */

declare(strict_types=1);

namespace motionpage\App\Rest\RestBuilder;

/**
 * The callback for the [CLEAR] REST API endpoint
 * @since 2.1.0
 */
class Clear extends AllPoints {
	public function clearPath(): \WP_REST_Response {
		global $wpdb;
		$wpdb->hide_errors();

		$query = $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'%motionpage_sl_%',
			'_site_transient_update_plugins'
		);
		$wpdb->query($query);
		if ($wpdb->last_error !== '') {
			return new \WP_REST_Response(['error' => $wpdb->last_error], 500);
		}

		// Clear cached version data so the updater fetches fresh info from Convex
		\delete_transient('motionpage_update_data');

		\wp_cache_flush();

		if (\has_action('litespeed_purge_all')) {
			\do_action('litespeed_purge_all');
		}

		http_response_code(200);
		exit();
	}
}
