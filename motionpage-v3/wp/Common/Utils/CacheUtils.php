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

namespace motionpage\Common\Utils;

/**
 * CacheUtils
 *
 * Shared cache management helpers. Animation code is inlined into the page
 * HTML via wp_add_inline_script and the SDK bundle is loaded from
 * wp-content/uploads/. Both can become version-skewed against host-level
 * page caches (LiteSpeed, WP Rocket, Cloudflare, etc.) — particularly after
 * a plugin update or an SDK bundle regeneration. We purge known caches
 * defensively whenever animation data, generated code, or the SDK bundle
 * itself changes.
 *
 * @package motionpage\Common\Utils
 * @since 3.1.2
 */
class CacheUtils {
	/**
	 * Purge known page caches.
	 *
	 * Triggers cache purges across the most common WordPress caching plugins
	 * and managed-host caches. Safe to call multiple times — each integration
	 * is feature-detected and silently no-ops if the corresponding plugin is
	 * not active on the site.
	 *
	 * @since 3.1.2
	 */
	public static function purgePageCaches(): void {
		// LiteSpeed Cache (Hostinger default)
		if (class_exists('LiteSpeed\Purge')) {
			\do_action('litespeed_purge_all');
		}

		// WP Rocket
		if (function_exists('rocket_clean_domain')) {
			\rocket_clean_domain();
		}

		// WP Super Cache
		if (function_exists('wp_cache_clear_cache')) {
			\wp_cache_clear_cache();
		}

		// W3 Total Cache
		if (function_exists('w3tc_flush_all')) {
			\w3tc_flush_all();
		}

		// WP Fastest Cache
		if (function_exists('wpfc_clear_all_cache')) {
			\wpfc_clear_all_cache(true);
		}

		// Autoptimize
		if (class_exists('autoptimizeCache')) {
			\autoptimizeCache::clearall();
		}

		// Kinsta Cache
		if (class_exists('Kinsta\Cache')) {
			\wp_remote_get(\home_url() . '/kinsta-clear-cache-all', ['blocking' => false]);
		}

		// SiteGround Optimizer
		if (function_exists('sg_cachepress_purge_cache')) {
			\sg_cachepress_purge_cache();
		}

		// Cloudflare (via plugin)
		if (class_exists('CF\WordPress\Hooks')) {
			\do_action('cloudflare_purge_by_url', \home_url('/'));
		}

		// Generic: WordPress object cache (flush transient-based caches)
		\wp_cache_flush();
	}
}
