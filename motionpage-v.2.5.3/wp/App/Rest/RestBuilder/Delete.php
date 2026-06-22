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
 * The callback for the [DELETE] REST API endpoint
 * @since 2.1.0
 */
class Delete extends AllPoints {
	public function deletePath(\WP_REST_Request $request): \WP_REST_Response {
		global $wpdb;
		$wpdb->hide_errors();
		$errors = [];

		$params = $request->get_json_params();
		$id = (int) $params['id'];

		$tables = ['_code', '_data'];
		foreach ($tables as $table_name) {
			$table = \esc_sql($wpdb->prefix . 'motionpage' . $table_name);

			$wpdb->query($wpdb->prepare('SELECT EXISTS (SELECT 1 FROM %1$s WHERE id = %2$d)', $table, $id));

			if ($wpdb->last_error !== '') {
				$errors[] = [
					'error' => $wpdb->last_error,
					'id' => $id,
					'type' => $table_name . ':select'
				];
				continue;
			}

			$wpdb->delete($table, ['id' => $id]);
			if ($wpdb->last_error !== '') {
				$errors[] = [
					'error' => $wpdb->last_error,
					'id' => $id,
					'type' => $table_name . ':delete'
				];
			}
		}

		if (empty($errors)) {
			\do_action('motionpage/action/api/delete', ['v1' => ['db_id' => $id]]);
			return new \WP_REST_Response(['id' => $id]);
		}

		return new \WP_REST_Response(['errors' => $errors]);
	}
}
