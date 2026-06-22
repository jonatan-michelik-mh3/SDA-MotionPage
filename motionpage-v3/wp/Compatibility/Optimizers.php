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

namespace motionpage\Compatibility;

/**
 * @since 2.0.0
 */
class Optimizers {
	public function init(): void {
		/**
		 * Compatibility classes instantiates after anything else
		 * @see Scaffold::__construct
		 */
		\add_filter('sgo_js_minify_exclude', [$this, 'excludeIframe']);
		\add_filter('sgo_js_async_exclude', [$this, 'excludeIframe']);
		\add_filter('sgo_javascript_combine_exclude', [$this, 'excludeIframe']);

		\add_filter('rocket_delay_js_exclusions', function ($excluded_strings): array {
			if (!is_array($excluded_strings)) {
				$excluded_strings = [];
			}

			$excluded_strings[] = '/motionpage/assets/js/gsap/(.*)';
			$excluded_strings[] = '/motionpage/assets/js/(.*)';
			$excluded_strings[] = '/motionpage/dist/(.*)';
			return $excluded_strings;
		});

		\add_filter('rocket_defer_inline_exclusions', function ($inline_exclusions_list): array {
			if (!is_array($inline_exclusions_list)) {
				$inline_exclusions_list = [];
			}

			$inline_exclusions_list[] = 'mp-';
			$inline_exclusions_list[] = 'data-mp';
			return $inline_exclusions_list;
		});

		\add_filter('wpmeteor_exclude', [$this, 'handleWpMeteor'], null, 2);
	}

	/**
	 * SiteGround optimizer compatibility
	 * @since 2.0.0
	 * @version 1.1.0
	 */
	public function excludeIframe(array $exclude_list): array {
		$exclude_list[] = 'module/hypewolf/mp/embed/embed.ts';
		return $exclude_list;
	}

	/**
	 * WP Meteor optimizer exclude
	 * @since 2.1.0
	 * @version 1.1.0
	 */
	public function handleWpMeteor($exclude, $content) {
		if (
			\str_starts_with($content, 'mp-') ||
			\str_contains($content, 'motionpage') ||
			\str_contains($content, 'MOTIONPAGE_FRONT') ||
			\str_contains($content, 'hypewolf')
		) {
			return true;
		}

		return $exclude;
	}
}
