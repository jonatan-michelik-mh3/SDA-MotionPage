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

namespace motionpage\App\Backend;

use motionpage\Common\Abstracts\Base;

/**
 * Class Notices
 *
 * @package motionpage\App\Backend
 * @since 2.0.0
 */
class Notices extends Base {
	/**
	 * Initialize the class.
	 * @since 2.0.0
	 */
	public function init(): void {
		/**
		 * This backend class is only being instantiated in the backend as requested in the Scaffold class
		 * @see Requester::isAdminBackend()
		 * @see Scaffold::__construct
		 */
		//\add_action('in_plugin_update_message-' . MOTIONPAGE_BASENAME, [$this, 'pluginUpdateMessage'], 10, 2);
		\add_action('after_plugin_row_wp-' . MOTIONPAGE_BASENAME, 'pluginUpdateMessageMulti', 10, 2);

		\add_action('after_plugin_row', [$this, 'showUpdateNotification'], 10, 2);
	}

	/**
	 * Plugin Update Message
	 * @since 2.0.0
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	public function pluginUpdateMessage($data, $response): void {
		//https://wisdomplugin.com/add-inline-plugin-update-message/
		//https://wordpress.stackexchange.com/questions/2003/how-to-create-custom-message-on-plugin-update
		global $pagenow;
		if (
			$pagenow === 'plugins.php' &&
			isset($data['update']) &&
			$data['update'] &&
			isset($data['upgrade_notice'])
		) {
			printf('<div class="update-message">%s</div>', \wpautop($data['upgrade_notice']));
		}
	}

	/**
	 * Plugin Update Message MultiSite
	 * @since 2.0.0
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	public function pluginUpdateMessageMulti($file, $plugin): void {
		global $pagenow;
		if (
			$pagenow === 'plugins.php' &&
			(\is_multisite() && version_compare($plugin['Version'], $plugin['new_version'], '<')) &&
			isset($plugin['update']) &&
			$plugin['update']
		) {
			$wp_list_table = \_get_list_table('WP_Plugins_List_Table');
			printf(
				'<tr class="plugin-update-tr"><td colspan="%s" class="plugin-update update-message notice inline notice-warning notice-alt"><div class="update-message"><h4 style="margin: 0; font-size: 14px;">%s</h4>%s</div></td></tr>',
				(int) $wp_list_table->get_column_count(),
				\esc_html($plugin['Name']),
				\wpautop($plugin['upgrade_notice'])
			);
		}
	}

	/**
	 * Show the update notification on multisite subsites.
	 *
	 * @param string $file
	 * @param array  $plugin
	 */
	public function showUpdateNotification($file, $plugin): void {
		$slug = $this->plugin->eddSlug();
		// Return early if in the network admin, or if this is not a multisite install.
		if (\is_network_admin() || !\is_multisite()) {
			return;
		}

		// Allow single site admins to see that an update is available.
		if (!\current_user_can('activate_plugins')) {
			return;
		}

		if ($file !== MOTIONPAGE_BASENAME) {
			return;
		}

		// Do not print any message if update does not exist.
		$update_cache = \get_site_transient('update_plugins');

		if (!isset($update_cache->response[MOTIONPAGE_BASENAME])) {
			if (!is_object($update_cache)) {
				$update_cache = new \stdClass();
			}

			$update_cache->response[MOTIONPAGE_BASENAME] = motionpageUpdater()->getRepoApiData();
		}

		// Return early if this plugin isn't in the transient->response or if the site is running the current or newer version of the plugin.
		if (
			empty($update_cache->response[MOTIONPAGE_BASENAME]) ||
			version_compare(
				$this->plugin->version(),
				$update_cache->response[MOTIONPAGE_BASENAME]->new_version,
				'>='
			)
		) {
			return;
		}

		printf(
			'<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">',
			$slug,
			$file,
			in_array(MOTIONPAGE_BASENAME, motionpageUpdater()->getActivePlugins(), true) ? 'active' : 'inactive'
		);

		echo <<<HTML
		<td colspan="3" class="plugin-update colspanchange">
		<div class="update-message notice inline notice-warning notice-alt"><p>
		HTML;

		$changelog_link = '';
		if (!empty($update_cache->response[MOTIONPAGE_BASENAME]->sections->changelog)) {
			$changelog_link = \add_query_arg(
				[
					'motionpage_sl_action' => 'view_plugin_changelog',
					'plugin' => urlencode(MOTIONPAGE_BASENAME),
					'slug' => urlencode($slug),
					'TB_iframe' => 'true',
					'width' => 77,
					'height' => 911
				],
				\self_admin_url('index.php')
			);
		}

		$update_link = \add_query_arg(
			[
				'action' => 'upgrade-plugin',
				'plugin' => urlencode(MOTIONPAGE_BASENAME)
			],
			\self_admin_url('update.php')
		);

		printf(
			/* translators: the plugin name. */
			\esc_html__('There is a new version of %1$s available.', 'easy-digital-downloads'),
			\esc_html($plugin['Name'])
		);

		if (!\current_user_can('update_plugins')) {
			echo ' ';
			\esc_html_e('Contact your network administrator to install the update.', 'easy-digital-downloads');
		} elseif (empty($update_cache->response[MOTIONPAGE_BASENAME]->package) && !empty($changelog_link)) {
			echo ' ';
			printf(
				/* translators: 1. opening anchor tag, do not translate 2. the new plugin version 3. closing anchor tag, do not translate. */
				\__('%1$sView version %2$s details%3$s or %4$supdate now%5$s.', 'easy-digital-downloads'),
				'<a target="_blank" class="thickbox open-plugin-details-modal" href="' .
					\esc_url($changelog_link) .
					'">',
				\esc_html($update_cache->response[MOTIONPAGE_BASENAME]->new_version),
				'</a>'
			);
		} elseif (!empty($changelog_link)) {
			echo ' ';
			printf(
				\__('%1$sView version %2$s details%3$s or %4$supdate now%5$s.', 'easy-digital-downloads'),
				'<a target="_blank" class="thickbox open-plugin-details-modal" href="' .
					\esc_url($changelog_link) .
					'">',
				\esc_html($update_cache->response[MOTIONPAGE_BASENAME]->new_version),
				'</a>',
				'<a target="_blank" class="update-link" href="' .
					\esc_url(\wp_nonce_url($update_link, 'upgrade-plugin_' . $file)) .
					'">',
				'</a>'
			);
		} else {
			printf(
				' %1$s%2$s%3$s',
				'<a target="_blank" class="update-link" href="' .
					\esc_url(\wp_nonce_url($update_link, 'upgrade-plugin_' . $file)) .
					'">',
				\esc_html__('Update now.', 'easy-digital-downloads'),
				'</a>'
			);
		}

		\do_action(sprintf('in_plugin_update_message-%s', $file), $plugin, $plugin);

		echo '</p></div></td></tr>';
	}
}
