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

namespace motionpage\Config;

use motionpage\Common\Traits\Singleton;

/**
 * Plugin setup hooks (activation, deactivation, uninstall)
 * @package motionpage\Config
 * @since 2.0.0
 */
final class Setup {
	use Singleton;

	/**
	 * Run only once after plugin is activated
	 * @docs https://developer.wordpress.org/reference/functions/register_activation_hook/
	 */
	public static function activation(bool $network_wide): void {
		if (!\current_user_can('activate_plugins')) {
			return;
		}

		if ($network_wide) {
			$main_settings = motionpage()->getMainSettings();
			foreach (\get_sites() as $site) {
				\switch_to_blog($site->blog_id);
				motionpage()->createDatabase();
				motionpage()->createSettings($main_settings);
				motionpage()->flushRewriteRules();
				\restore_current_blog();
			}

			return;
		}

		motionpage()->createDatabase();
		motionpage()->createSettings();

		motionpage()->flushRewriteRules();
	}

	/**
	 * Run only once after plugin is deactivated
	 * @docs https://developer.wordpress.org/reference/functions/register_deactivation_hook/
	 */
	public static function deactivation(): void {
		if (\current_user_can('activate_plugins')) {
			motionpage()->flushRewriteRules();
		}
	}

	/**
	 * Run only once after plugin is uninstalled
	 * @docs https://developer.wordpress.org/reference/functions/register_uninstall_hook/
	 */
	public static function uninstall(): void {
		if (\current_user_can('activate_plugins')) {
			global $wpdb;

			$query = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '%motionpage_sl_%');
			$wpdb->query($query);

			$options = motionpage()->getMainSettings();
			if (!empty($options)) {
				\wp_remote_post('https://api.hypewolf.com/motionpage/builder/deactivate', [
					'headers' => ['Content-Type' => 'application/json'],
					'body' => \wp_json_encode([
						'license' => $options['license_key'] ?? '',
						'url' => \trailingslashit(preg_replace('#^https?://#i', '', \home_url()))
					]),
					'timeout' => 15
				]);

				if ($options['system']['wipeOnUninstall']) {
					motionpage()->deleteOption('motionpage_main');
					motionpage()->deleteOption('motionpage_db_version');
					motionpage()->deleteOption('motionpage_client');
					//motionpage()->deleteOption('widget_motionpage');
					//motionpage()->deleteOption('motionpage_widget');

					$tables_suffix = ['_scripts', '_code', '_data'];

					foreach ($tables_suffix as $table_suffix) {
						$table_name = \esc_sql($wpdb->prefix . 'motionpage' . $table_suffix);
						$wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %1$s', $table_name));
					}

					return;
				}

				$options['license_key'] = '';
				$options['license_status'] = 'invalid';
				motionpage()->updateOption('motionpage_main', $options);
			}
		}
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	public static function newMultisiteBlog(object $new_site, $args = []): void {
		$main_settings = motionpage()->getMainSettings();

		\switch_to_blog($new_site->blog_id);
		motionpage()->createDatabase();
		motionpage()->createSettings($main_settings);
		motionpage()->flushRewriteRules();
		\restore_current_blog();
	}
}
