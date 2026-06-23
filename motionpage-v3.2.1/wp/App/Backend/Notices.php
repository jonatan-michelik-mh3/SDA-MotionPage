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

}
