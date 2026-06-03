<?php

/**
 * Motion.page
 *
 * @package   motionpage
 * @author    HypeWolf OÜ <hello@hypewolf.com>
 * @copyright 2024 HypeWolf OÜ
 * @license   EULA + GPLv2
 * @link      https://motion.page
 */

declare(strict_types=1);

namespace motionpage\App\Rest\RestBuilder;

/**
 * The callback for the [LOAD] REST API endpoint
 * @since 2.1.0
 */
class Load extends AllPoints {
	public function loadPath(\WP_REST_Request $request): \WP_REST_Response {
		global $wpdb;
		$prefix = \esc_sql($wpdb->prefix . 'motionpage');

		$dbv = $request['dbv'] ?? '2.0';

		$plugin_dbv = $this->plugin->databaseVersion();
		$current_dbv = motionpage()->getOption('motionpage_db_version', $plugin_dbv);
		$newer_or_same_as_2_0 = version_compare($current_dbv, $plugin_dbv, '>=');
		if ($newer_or_same_as_2_0 && version_compare($dbv, '2.0', '>=')) {
			$code_table = \esc_sql($prefix . '_code');
			$data_table = \esc_sql($prefix . '_data');

			$query_string = $wpdb->prepare(
				'SELECT * FROM %1$s LEFT JOIN %2$s ON %1$s.id = %2$s.data_id',
				$data_table,
				$code_table
			);
			$data_rows = $wpdb->get_results($query_string);
			if (\is_wp_error($data_rows)) {
				error_log('Database query error: ' . $wpdb->last_error);
				return new \WP_REST_Response(['error' => 'Database query failed!'], 500);
			}

			return new \WP_REST_Response($data_rows);
		}

		$srows = $wpdb->get_results($wpdb->prepare('SELECT * FROM %1$s', \esc_sql($prefix . '_scripts')));
		if (\is_wp_error($srows)) {
			error_log('Database query error: ' . $wpdb->last_error);
			return new \WP_REST_Response(['error' => 'Database query failed!'], 500);
		}

		// Ensure that the data satisfies the Zod schema
		$modified_scripts_rows = array_map(function ($row) {
			unset($row->trigger_is_active);
			$row->data_id = $row->id;
			return $row;
		}, $srows);

		return new \WP_REST_Response($modified_scripts_rows);
	}
}
