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

namespace motionpage\App\General;

use motionpage\Common\Abstracts\Base;

/**
 * @since 2.0.0
 */
class SiteHealth extends Base {
	public function init(): void {
		/**
		 * This general class is always being instantiated as requested in the Scaffold class
		 * @see Scaffold::__construct
		 */

		global $pagenow;

		if ($pagenow === 'site-health.php' && isset($_GET['tab']) && $_GET['tab'] === 'debug') {
			if ($this->plugin->isClient()) {
				return;
			}

			\add_filter('debug_information', function ($info) {
				$main_settings = $this->plugin->settings();

				$info['motionpage'] = [
					'label' => 'Motion.page',
					'description' => 'Information related to Motion.page plugin.',
					'show_count' => true,
					'fields' => [
						'requiredWP' => [
							'label' => 'Minimum WP version',
							'value' => $this->plugin->requiredWP()
						],
						'requiredPHP' => [
							'label' => 'Minimum PHP version',
							'value' => $this->plugin->requiredPHP()
						],
						'gsapVersion' => [
							'label' => 'GSAP version',
							'value' => $this->plugin->gsapVersion()
						],
						'version' => [
							'label' => 'Plugin version',
							'value' => $this->plugin->version()
						],
						'databaseVersion' => [
							'label' => 'Database version',
							'value' => $this->plugin->databaseVersion()
						],
						'isClient' => [
							'label' => 'Client mode enabled',
							'value' => $this->plugin->isClient() ? 'Yes' : 'No'
						],
						'beta' => [
							'label' => 'Beta mode',
							'value' => ($main_settings['system']['beta'] ?? 0) === 1 ? 'Yes' : 'No'
						],
						'wipeOnUninstall' => [
							'label' => 'Remove data on uninstall',
							'value' => ($main_settings['system']['wipeOnUninstall'] ?? 0) === 1 ? 'Yes' : 'No'
						],
						'sequenceFolder' => [
							'label' => 'Image sequence folder',
							'value' => $this->plugin->sequenceFolder()
						]
					]
				];

				$gsap_defer_async = \apply_filters('motionpage/utils/gsapDeferAsync', false);
				$info['motionpage']['fields']['gsapDeferAsync'] = [
					'label' => 'GSAP defer/async [filter]',
					'value' =>
						$gsap_defer_async === 'defer' || $gsap_defer_async === 'async' ? $gsap_defer_async : 'Disabled'
				];

				return $info;
			});
		}
	}
}
