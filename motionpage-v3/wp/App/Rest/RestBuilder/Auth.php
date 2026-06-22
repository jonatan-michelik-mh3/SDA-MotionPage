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
 * The callback for the [AUTH] REST API endpoint
 * Manages session token storage for OTP-based WordPress authentication.
 *
 * Session data is stored in the 'motionpage_session' WP option:
 * [
 *   'token' => 'uuid-plain-text',
 *   'email' => 'user@example.com',
 *   'plan' => ['name' => '...', 'label' => '...', 'type' => '...', 'expiresAt' => ...],
 *   'activated_at' => 1704067200000,
 * ]
 *
 * @since 3.0.0
 */
class Auth extends AllPoints {
	private const OPTION_KEY = 'motionpage_session';

	/**
	 * GET /motionpage/v1/auth/session
	 * Returns the current session data (token + plan info).
	 */
	public function sessionGet(): \WP_REST_Response {
		$session = motionpage()->getOption(self::OPTION_KEY, null);

		if (empty($session) || !is_array($session) || empty($session['token'])) {
			return new \WP_REST_Response([
				'authenticated' => false,
				'token' => null,
				'email' => null,
				'plan' => null,
			]);
		}

		return new \WP_REST_Response([
			'authenticated' => true,
			'token' => $session['token'],
			'email' => $session['email'] ?? null,
			'plan' => $session['plan'] ?? null,
			'user' => $session['user'] ?? null,
		]);
	}

	/**
	 * POST /motionpage/v1/auth/session
	 * Store session token after successful OTP verification.
	 * Called by the React frontend after Convex responds with a valid session.
	 */
	public function sessionPost(\WP_REST_Request $request): \WP_REST_Response {
		$params = $request->get_json_params();

		$token = $params['token'] ?? null;
		$email = $params['email'] ?? null;
		$plan = $params['plan'] ?? null;
		$user = $params['user'] ?? null;

		if (empty($token) || !is_string($token)) {
			return new \WP_REST_Response(['error' => 'Missing session token'], 400);
		}

		$session_data = [
			'token' => \sanitize_text_field($token),
			'email' => \sanitize_email($email ?? ''),
			'plan' => is_array($plan) ? $this->sanitizePlan($plan) : null,
			'user' => is_array($user) ? $this->sanitizeUser($user) : null,
			'activated_at' => time() * 1000, // Unix ms for consistency with JS
		];

		motionpage()->updateOption(self::OPTION_KEY, $session_data);

		// Also update the main settings to mark as valid (for b.valid flag)
		$main_settings = motionpage()->getMainSettings();
		$main_settings['license_status'] = 'valid';
		motionpage()->updateOption('motionpage_main', $main_settings);

		// Force WP to re-check plugin updates so the download URL
		// picks up the new session token immediately.
		\delete_site_transient('update_plugins');

		return new \WP_REST_Response(['success' => true]);
	}

	/**
	 * DELETE /motionpage/v1/auth/session
	 * Clear session (logout/deactivate).
	 */
	public function sessionDelete(): \WP_REST_Response {
		motionpage()->deleteOption(self::OPTION_KEY);

		// Update main settings to mark as invalid
		$main_settings = motionpage()->getMainSettings();
		$main_settings['license_status'] = 'invalid';
		motionpage()->updateOption('motionpage_main', $main_settings);

		return new \WP_REST_Response(['success' => true]);
	}

	/**
	 * Sanitize plan data from the Convex response.
	 * @param array $plan Raw plan data.
	 * @return array Sanitized plan data.
	 */
	private function sanitizePlan(array $plan): array {
		return [
			'name' => \sanitize_text_field($plan['name'] ?? ''),
			'label' => \sanitize_text_field($plan['label'] ?? ''),
			'type' => \sanitize_text_field($plan['type'] ?? ''),
			'maxDomains' => (int) ($plan['maxDomains'] ?? 0),
			'domainsUsed' => (int) ($plan['domainsUsed'] ?? 0),
			'expiresAt' => isset($plan['expiresAt']) ? (int) $plan['expiresAt'] : null,
		];
	}

	/**
	 * Sanitize user data from the Convex response.
	 * @param array $user Raw user data.
	 * @return array Sanitized user data.
	 */
	private function sanitizeUser(array $user): array {
		return [
			'email' => \sanitize_email($user['email'] ?? ''),
			'displayName' => \sanitize_text_field($user['displayName'] ?? ''),
		];
	}
}
