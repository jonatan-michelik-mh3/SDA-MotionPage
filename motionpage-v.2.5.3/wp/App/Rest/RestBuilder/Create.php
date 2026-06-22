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
 * The callback for the [CREATE | IMPORT] REST API endpoint
 * @since 2.1.0
 */
class Create extends AllPoints {
	public function createPath(\WP_REST_Request $request): \WP_REST_Response {
		$params = $request->get_json_params();

		$timeline = [
			'script_name' => $params['script_name'],
			'script_value' => $params['script_value'],
			'post_id' => $params['post_id'],
			'is_global' => $params['is_global'],
			'is_active' => $params['is_active'],
			'trigger_name' => $params['trigger_name'],
			'plugins' => $params['plugins'],
			'reload' => $params['reload'],
			'types' => $params['types'],
			'cats' => $params['cats']
		];

		$fill_response = $this->fillRows($timeline);

		if (isset($fill_response['error'])) {
			return new \WP_REST_Response(['errors' => $fill_response], 500);
		}

		\do_action('motionpage/action/api/create', [
			'v1' => [
				'db_id' => (int) ($fill_response['data_id'] ?? 0),
				'selectors' => $params['selectors'] ?? ''
			]
		]);

		return new \WP_REST_Response(['ids' => $fill_response]);
	}
}
