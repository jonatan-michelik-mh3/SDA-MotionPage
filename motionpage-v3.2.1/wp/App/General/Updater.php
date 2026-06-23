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

namespace motionpage\App\General;

use motionpage\Common\Abstracts\Base;

/**
 * Auto-updater — checks Convex for new versions and gates downloads
 * behind a valid session token.
 *
 * Version check:    GET /wp/version                    (public, cached 12 h)
 * Package download: GET /wp/update/download?token=xxx  (gated, 302 → GitHub ZIP)
 *
 * @package motionpage\App\General
 * @since 3.0.0
 */
class Updater extends Base {
	/** @var string WP transient key for cached version data */
	private const TRANSIENT_KEY = 'motionpage_update_data';

	/** @var int Cache lifetime in seconds (12 hours) */
	private const CACHE_TTL = 43200;

	/** @var int Cache lifetime for failed requests (30 minutes) — avoids hammering */
	private const CACHE_TTL_ERROR = 1800;

	/** @var string WP option key where the session token is stored */
	private const SESSION_KEY = 'motionpage_session';

	/**
	 * Initialize hooks.
	 * Runs on all request types (admin, cron, frontend) so that
	 * WP cron-triggered update checks work correctly.
	 *
	 * @since 3.0.0
	 */
	public function init(): void {
		\add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);
		\add_filter('site_transient_update_plugins', [$this, 'injectPackageUrl']);
		\add_filter('plugins_api', [$this, 'pluginInfo'], 10, 3);
	}

	/**
	 * Inject update info into the WP update transient when a newer
	 * version is available on the remote.
	 *
	 * @param mixed $transient The update_plugins transient value.
	 * @return mixed
	 * @since 3.0.0
	 */
	public function checkUpdate($transient) {
		if (!is_object($transient)) {
			return $transient;
		}

		$remote = $this->getRemoteVersionData();
		if (!$remote || empty($remote['new_version'])) {
			return $transient;
		}

		$item_base = [
			'id' => 'motionpage/motionpage.php',
			'slug' => 'motionpage',
			'plugin' => MOTIONPAGE_BASENAME,
			'url' => $remote['url'] ?? 'https://motion.page',
			'icons' => [
				'default' => 'https://motion.page/icon.png',
			],
		];

		// Nothing to do if we're already on the latest (or newer)
		if (\version_compare(MOTIONPAGE_VERSION, $remote['new_version'], '>=')) {
			// Populate no_update so WP doesn't re-check on every page load
			$transient->no_update[MOTIONPAGE_BASENAME] = (object) array_merge($item_base, [
				'new_version' => MOTIONPAGE_VERSION,
				'package' => '',
			]);
			return $transient;
		}

		// Build gated download URL (empty if no session → WP shows
		// "Automatic update unavailable" which is correct UX).
		$package = $this->buildPackageUrl();

		$transient->response[MOTIONPAGE_BASENAME] = (object) array_merge($item_base, [
			'new_version' => $remote['new_version'],
			'package' => $package,
			'requires' => $remote['requires'] ?? $this->plugin->requiredWP(),
			'requires_php' => $remote['requires_php'] ?? $this->plugin->requiredPHP(),
		]);

		return $transient;
	}

	/**
	 * Supply plugin information for the "View details" modal in WP admin.
	 *
	 * @param mixed  $result Default result (false on first call).
	 * @param string $action API action (we only care about 'plugin_information').
	 * @param object $args   Request args (->slug).
	 * @return mixed
	 * @since 3.0.0
	 */
	public function pluginInfo($result, $action = '', $args = null) {
		if ($action !== 'plugin_information') {
			return $result;
		}

		if (!is_object($args) || ($args->slug ?? '') !== 'motionpage') {
			return $result;
		}

		$remote = $this->getRemoteVersionData();
		if (!$remote || empty($remote['new_version'])) {
			return $result;
		}

		$package = $this->buildPackageUrl();

		return (object) [
			'name' => 'Motion.page',
			'slug' => 'motionpage',
			'version' => $remote['new_version'],
			'author' => '<a href="https://motion.page">CoreWeb OÜ</a>',
			'author_profile' => 'https://motion.page',
			'homepage' => 'https://motion.page',
			'requires' => $remote['requires'] ?? $this->plugin->requiredWP(),
			'requires_php' => $remote['requires_php'] ?? $this->plugin->requiredPHP(),
			'download_link' => $package,
			'trunk' => $package,
			'sections' => [
				'description' => 'Visual animation builder for WordPress.',
				'changelog' => '<p>Visit <a href="https://motion.page/changelog" target="_blank">motion.page/changelog</a> for the full changelog.</p>',
			],
			'banners' => [],
		];
	}

	/**
	 * Ensure the package URL is always fresh when WP reads the transient.
	 *
	 * The `pre_set_site_transient_update_plugins` filter only fires when WP
	 * *writes* the transient (every ~12 h). Between writes WP serves a cached
	 * copy. If the user authenticates after the last write, the cached
	 * `package` field is empty → "Update package not available".
	 *
	 * This READ-side filter re-computes the URL on every transient read,
	 * so the download link always reflects the current session state.
	 *
	 * @param mixed $transient The update_plugins transient value.
	 * @return mixed
	 * @since 3.0.0
	 */
	public function injectPackageUrl($transient) {
		if (
			!is_object($transient)
			|| empty($transient->response[MOTIONPAGE_BASENAME])
		) {
			return $transient;
		}

		$item = $transient->response[MOTIONPAGE_BASENAME];

		// Only inject when the cached URL is missing / stale
		if (!empty($item->package)) {
			return $transient;
		}

		$package = $this->buildPackageUrl();
		if ($package !== '') {
			$item->package = $package;
		}

		return $transient;
	}

	// ── Private helpers ──────────────────────────────────────────────────

	/**
	 * Fetch (and cache) version metadata from the Convex endpoint.
	 * Caches both successes (12 h) and failures (30 min) to avoid
	 * hammering the remote on errors.
	 *
	 * @return array|null Decoded JSON or null on failure.
	 */
	private function getRemoteVersionData(): ?array {
		$cached = \get_transient(self::TRANSIENT_KEY);

		if (is_array($cached)) {
			return $cached;
		}

		// A string 'error' means we cached a recent failure — don't retry yet
		if ($cached === 'error') {
			return null;
		}

		$url = $this->getConvexSiteUrl() . '/wp/version';

		$response = \wp_remote_get($url, [
			'timeout' => 10,
			'headers' => ['Accept' => 'application/json'],
		]);

		if (\is_wp_error($response) || \wp_remote_retrieve_response_code($response) !== 200) {
			\set_transient(self::TRANSIENT_KEY, 'error', self::CACHE_TTL_ERROR);
			return null;
		}

		$body = json_decode(\wp_remote_retrieve_body($response), true);
		if (!is_array($body) || empty($body['new_version'])) {
			\set_transient(self::TRANSIENT_KEY, 'error', self::CACHE_TTL_ERROR);
			return null;
		}

		\set_transient(self::TRANSIENT_KEY, $body, self::CACHE_TTL);

		return $body;
	}

	/**
	 * Build the gated package download URL.
	 * Returns empty string if no valid session token is stored.
	 *
	 * @return string Download URL or empty string.
	 */
	private function buildPackageUrl(): string {
		$session = motionpage()->getOption(self::SESSION_KEY, null);
		if (empty($session) || !is_array($session) || empty($session['token'])) {
			return '';
		}

		return $this->getConvexSiteUrl() . '/wp/update/download?token=' . \urlencode($session['token']);
	}

	/**
	 * Get the Convex HTTP site URL (supports override via constant).
	 *
	 * @return string
	 */
	private function getConvexSiteUrl(): string {
		return defined('MOTIONPAGE_CONVEX_SITE_URL')
			? \MOTIONPAGE_CONVEX_SITE_URL
			: 'https://cloud.motion.page';
	}
}
