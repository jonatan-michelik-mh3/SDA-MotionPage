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
 * The callback for the [SETTINGS] REST API endpoint
 * @since 2.1.0
 */
class Settings extends AllPoints {
	public function settingsGet(): \WP_REST_Response {
		$settings = motionpage()->getMainSettings();
		return new \WP_REST_Response($this->decode($settings));
	}

	public function settingsPost(\WP_REST_Request $request): \WP_REST_Response {
		$settings = $request['settings'];

		if (!$settings) {
			http_response_code(400);
			exit('No settings provided');
		}

		$settings = json_decode($settings, true);
		if ($settings === null && json_last_error() !== JSON_ERROR_NONE) {
			return new \WP_REST_Response(['error' => 'Invalid JSON!'], 200);
		}

		motionpage()->updateOption('motionpage_main', $this->encode($settings));

		return new \WP_REST_Response(['settings' => $settings]);
	}
}
