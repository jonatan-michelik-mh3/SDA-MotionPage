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
 * Class Settings
 *
 * @package motionpage\App\Backend
 * @since 2.0.0
 */
class Settings extends Base {
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

		\add_action('admin_menu', [$this, 'builderMenuItem'], 20);

		\add_filter('plugin_row_meta', [$this, 'rowMeta'], 10, 4);
		\add_filter('plugin_action_links_' . MOTIONPAGE_BASENAME, [$this, 'settingsLinks']);
		\add_filter('all_plugins', [$this, 'modifyPluginData']);
		\add_filter('post_row_actions', [$this, 'quickActionLink'], 10, 2);
		\add_filter('page_row_actions', [$this, 'quickActionLink'], 10, 2);

		\add_action('admin_init', [$this, 'showChangelog']);

		//\add_action('admin_bar_menu', [$this, 'adminMenuBar'], 100);
		//\add_action('wp_dashboard_setup', [$this, 'dashboardWidgets']);
	}

	public function builderMenuItem(): void {
		$hidden_slug = $this->plugin->hiddenMenuSlug();
		$is_hidden_menu = $this->plugin->isWhiteLabel() && $this->plugin->whiteLabelHiddenInMenu();

		if ($this->plugin->isClient()) {
			\add_submenu_page(
				null,
				'Animation Builder',
				'Animation Builder',
				'manage_options',
				$hidden_slug,
				function () use ($hidden_slug, $is_hidden_menu): void {
					$slug = $is_hidden_menu ? $hidden_slug : MOTIONPAGE_NAME;
					echo '<script>window.MOTIONAGE_CLIENT_MODE_URL = "' .
						\admin_url('admin.php?page=' . $slug) .
						'"</script>';
					echo '<script>window.MOTIONAGE_CLIENT_MODE = true</script>';
					echo '<div id="mp-init"></div>';
				}
			);
			return;
		}

		$logo =
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTM3cHgiIGhlaWdodD0iMTQxcHgiIHZpZXdCb3g9IjAgMCAxMzcgMTQxIiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPHRpdGxlPm1wPC90aXRsZT4KICAgIDxkZWZzPgogICAgICAgIDxsaW5lYXJHcmFkaWVudCB4MT0iNTAuMDMwMzcwMyUiIHkxPSI5OS45OTg1MDk3JSIgeDI9IjUwLjAzMDM3MDMlIiB5Mj0iNi41MzczODYwNWUtMTQlIiBpZD0ibGluZWFyR3JhZGllbnQtMSI+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiM2N0M4OTAiIG9mZnNldD0iMCUiPjwvc3RvcD4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iIzUwQTdBQyIgb2Zmc2V0PSIxOCUiPjwvc3RvcD4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iIzQyNjVCRSIgb2Zmc2V0PSI0MCUiPjwvc3RvcD4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iIzNBMkJBQSIgb2Zmc2V0PSI2MSUiPjwvc3RvcD4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iIzNFMUQ5OCIgb2Zmc2V0PSI4MSUiPjwvc3RvcD4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iIzNFMUQ5OCIgb2Zmc2V0PSIxMDAlIj48L3N0b3A+CiAgICAgICAgPC9saW5lYXJHcmFkaWVudD4KICAgICAgICA8bGluZWFyR3JhZGllbnQgeDE9IjUwJSIgeTE9IjEwMCUiIHgyPSI1MCUiIHkyPSIwJSIgaWQ9ImxpbmVhckdyYWRpZW50LTIiPgogICAgICAgICAgICA8c3RvcCBzdG9wLWNvbG9yPSIjRDA1MkNCIiBvZmZzZXQ9IjAlIj48L3N0b3A+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiMzRTFGOTciIG9mZnNldD0iMTAwJSI+PC9zdG9wPgogICAgICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICAgICAgPGxpbmVhckdyYWRpZW50IHgxPSI1MCUiIHkxPSItMC4wMDE4MDY4MDc1NCUiIHgyPSI1MCUiIHkyPSIxMDAlIiBpZD0ibGluZWFyR3JhZGllbnQtMyI+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiNEMDUyQ0IiIG9mZnNldD0iMCUiPjwvc3RvcD4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iIzNFMUY5NyIgb2Zmc2V0PSIxMDAlIj48L3N0b3A+CiAgICAgICAgPC9saW5lYXJHcmFkaWVudD4KICAgICAgICA8bGluZWFyR3JhZGllbnQgeDE9IjUwLjAwNzcwNzclIiB5MT0iMCUiIHgyPSI1MC4wMDc3MDc3JSIgeTI9IjEwMCUiIGlkPSJsaW5lYXJHcmFkaWVudC00Ij4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iI0Y5RjlGOSIgb2Zmc2V0PSIwJSI+PC9zdG9wPgogICAgICAgICAgICA8c3RvcCBzdG9wLWNvbG9yPSIjRjlGOUY5IiBzdG9wLW9wYWNpdHk9IjAuNzQiIG9mZnNldD0iMTYlIj48L3N0b3A+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiNGOUY5RjkiIHN0b3Atb3BhY2l0eT0iMC41MiIgb2Zmc2V0PSIzMyUiPjwvc3RvcD4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iI0Y5RjlGOSIgc3RvcC1vcGFjaXR5PSIwLjMzIiBvZmZzZXQ9IjQ4JSI+PC9zdG9wPgogICAgICAgICAgICA8c3RvcCBzdG9wLWNvbG9yPSIjRjlGOUY5IiBzdG9wLW9wYWNpdHk9IjAuMTkiIG9mZnNldD0iNjMlIj48L3N0b3A+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiNGOUY5RjkiIHN0b3Atb3BhY2l0eT0iMC4wOCIgb2Zmc2V0PSI3NyUiPjwvc3RvcD4KICAgICAgICAgICAgPHN0b3Agc3RvcC1jb2xvcj0iI0Y5RjlGOSIgc3RvcC1vcGFjaXR5PSIwLjAyIiBvZmZzZXQ9IjkwJSI+PC9zdG9wPgogICAgICAgICAgICA8c3RvcCBzdG9wLWNvbG9yPSIjRjlGOUY5IiBzdG9wLW9wYWNpdHk9IjAiIG9mZnNldD0iMTAwJSI+PC9zdG9wPgogICAgICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICAgICAgPGxpbmVhckdyYWRpZW50IHgxPSI1MC4wNjEyNzQ1JSIgeTE9Ii0wLjAzMDQwMTc2NDglIiB4Mj0iNTAuMDYxMjc0NSUiIHkyPSI5OS45NzUxMDEzJSIgaWQ9ImxpbmVhckdyYWRpZW50LTUiPgogICAgICAgICAgICA8c3RvcCBzdG9wLWNvbG9yPSIjNjdDODkwIiBvZmZzZXQ9IjAlIj48L3N0b3A+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiM1MEE3QUMiIG9mZnNldD0iMTclIj48L3N0b3A+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiM0MjY1QkUiIG9mZnNldD0iNDMlIj48L3N0b3A+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiMzRTFEOTgiIG9mZnNldD0iODglIj48L3N0b3A+CiAgICAgICAgICAgIDxzdG9wIHN0b3AtY29sb3I9IiMzRTFEOTgiIG9mZnNldD0iMTAwJSI+PC9zdG9wPgogICAgICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8L2RlZnM+CiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8ZyBpZD0ibXAiIGZpbGwtcnVsZT0ibm9uemVybyI+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik05Ny45NCw4MS4xMyBDOTYuNjIyMjI3Miw3OC41MDA3NDI4IDk0Ljg4NjMwOTYsNzYuMTAyODc5OSA5Mi44LDc0LjAzIEw2Ni40OCw0Ny43MiBMMy41NCwxMTAuNjYgQy0wLjI1MDY0ODYwOSwxMTQuNDUxNzYgLTAuMjUwNjQ4NjA5LDEyMC41OTgyNCAzLjU0LDEyNC4zOSBMMTAuOTgsMTMxLjgyIEMxMi43OTgyODQ4LDEzMy42NDUyNzYgMTUuMjY4NjA4NCwxMzQuNjcxMjk2IDE3Ljg0NSwxMzQuNjcxMjk2IEMyMC40MjEzOTE2LDEzNC42NzEyOTYgMjIuODkxNzE1MiwxMzMuNjQ1Mjc2IDI0LjcxLDEzMS44MiBMNDEuNTIsMTE1IEM0Mi42MywxMTMuOTQgNDguNTIsMTA5IDU1LjY0LDExNS4xMyBMNTUuNjQsMTE1LjEzIEw1NS42NCwxMTUuMTMgQzU1Ljk1NTUzMjIsMTE1LjQwODk5MSA1Ni4yODk2MzMsMTE1LjY2NjI0OSA1Ni42NCwxMTUuOSBDNjYuOTgwOTQ5LDEyMy41MTIwNzkgODEuMTkzNzI3OSwxMjMuMDM1NDggOTEuMDAxNTA3MSwxMTQuNzQ3NzUyIEMxMDAuODA5Mjg2LDEwNi40NjAwMjQgMTAzLjY1MDI2Nyw5Mi41MjU5MjcxIDk3Ljg3LDgxLjA2IEw5Ny45NCw4MS4xMyBaIiBpZD0iUGF0aCIgZmlsbD0idXJsKCNsaW5lYXJHcmFkaWVudC0xKSIgb3BhY2l0eT0iMC43NSI+PC9wYXRoPgogICAgICAgICAgICA8Y2lyY2xlIGlkPSJPdmFsIiBmaWxsPSJ1cmwoI2xpbmVhckdyYWRpZW50LTIpIiBvcGFjaXR5PSIwLjYiIGN4PSIxMTguNSIgY3k9IjEyMi4zMSIgcj0iMTcuNzgiPjwvY2lyY2xlPgogICAgICAgICAgICA8cGF0aCBkPSJNMjMuMzQsNC41OSBMNjYuNDgsNDcuNzIgTDMzLjA3LDgxLjEzIEwxNy43Niw4MS4xMyBDOC42MTQxNjQ1NCw4MS4xMyAxLjIsNzMuNzE1ODM1NSAxLjIsNjQuNTcgTDEuMiwxMy43NyBDMS4yMDcxNDIxMSw4LjU2Nzg0MDYzIDQuMzE0ODcwMDksMy44NzA4NDQxNiA5LjEsMS44MyBMOS4xLDEuODMgQzEzLjk2NjQ0NjUsLTAuMjI5NzI5NTk3IDE5LjU5NTQ3MDQsMC44NjEyODkwNzIgMjMuMzQsNC41OSBMMjMuMzQsNC41OSBaIiBpZD0iUGF0aCIgZmlsbD0idXJsKCNsaW5lYXJHcmFkaWVudC0zKSIgb3BhY2l0eT0iMC41Ij48L3BhdGg+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik02Ni40OCw0Ny43MiBMMzMuMDcsODEuMTMgTDk3Ljk0LDgxLjEzIEM5Ny45NCw4MS4xMyA5Ni44OCw3OC4xMyA5MS45NCw3My4yIEw2Ni40OCw0Ny43MiBaIiBpZD0iUGF0aCIgZmlsbD0idXJsKCNsaW5lYXJHcmFkaWVudC00KSI+PC9wYXRoPgogICAgICAgICAgICA8cGF0aCBkPSJNOTMuOSw3NS4xNSBDOTUuNDUyMjM3OCw3Ni45OTUwODA4IDk2Ljc5Mzg2Myw3OS4wMDc1MTg3IDk3LjksODEuMTUgTDExNS4xNSw4MS4xNSBDMTI0LjI5NTgzNSw4MS4xNSAxMzEuNzEsNzMuNzM1ODM1NSAxMzEuNzEsNjQuNTkgTDEzMS43MSwxMy43OSBDMTMxLjcwMjg1OCw4LjU4Nzg0MDYzIDEyOC41OTUxMywzLjg5MDg0NDE2IDEyMy44MSwxLjg1IEwxMjMuODEsMS44NSBDMTE4Ljk0NjMxNiwtMC4yMDUxOTk2NTIgMTEzLjMyMjY4MiwwLjg4NTU0MDI1NSAxMDkuNTgsNC42MSBMNjYuNDMsNDcuNzQgTDkzLjg2LDc1LjE3IiBpZD0iUGF0aCIgZmlsbD0idXJsKCNsaW5lYXJHcmFkaWVudC01KSIgb3BhY2l0eT0iMC41Ij48L3BhdGg+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4=';

		if ($is_hidden_menu) {
			\add_submenu_page(
				null,
				$this->plugin->isWhiteLabel() ? ($this->plugin->whiteLabelName() ?: 'Motion.page') : 'Motion.page',
				$this->plugin->isWhiteLabel() ? ($this->plugin->whiteLabelName() ?: 'Motion.page') : 'Motion.page',
				'manage_options',
				$hidden_slug,
				function (): void {
					echo '<div id="mp-init"></div>';
				}
			);
			return;
		}

		\add_menu_page(
			'Motion.page',
			$this->plugin->isWhiteLabel() ? ($this->plugin->whiteLabelName() ?: 'Motion.page') : 'Motion.page',
			'manage_options',
			MOTIONPAGE_NAME,
			function (): void {
				echo '<div id="mp-init"></div>';
			},
			$this->plugin->isWhiteLabel()
				? htmlspecialchars_decode($this->plugin->whiteLabelIcon() ?: $logo)
				: $logo,
			99
		);

		//\add_action('load-' . $hook_suffix, [$this, 'builder_scripts']);

		//add_submenu_page(
		//  MOTIONPAGE_NAME,
		//  'Motion.page',
		//  'Dashboard',
		//  'manage_options',
		//  'motionpage-dash',
		//  function () {
		//      echo '<div id="mp-init"></div>';
		//  },
		//  99
		//);
	}

	/**
	 * Add metadata to plugin
	 * @since   1.4.0
	 */
	public function rowMeta(array $plugin_meta, string $plugin_file): array {
		if ($plugin_file === MOTIONPAGE_BASENAME) {
			if ($this->plugin->isClient() || $this->plugin->isWhiteLabel()) {
				$author = $this->plugin->whiteLabelAuthor();
				$plugin_meta[1] = htmlspecialchars_decode($author);
				unset($plugin_meta[2]);
				if (!$author) {
					unset($plugin_meta[1]);
				}

				if ($this->plugin->whiteLabelHideVersion()) {
					unset($plugin_meta[0]);
				}
			} else {
				$plugin_meta[1] = 'By <a href="//motion.page/">HypeWolf OÜ</a>';
				$plugin_meta[] = '<a href="//community.motion.page/" target="_blank">Community</a>';
				//$plugin_meta[] = '<a href="//community.motion.page/" target="_blank">Check for updates</a>';
			}
		}

		return $plugin_meta;
	}

	/**
	 * Add setting link to plugin in plugin list
	 * @param   string[]  $actions  The current actions array
	 * @since   1.4.0
	 */
	public function settingsLinks($actions): array {
		$is_client = $this->plugin->isClient();

		if (!$is_client && !$this->plugin->isWhiteLabel()) {
			$link = '<a href="//community.motion.page/start-here/" target="_blank">Get started</a>';
			array_unshift($actions, $link);
			return $actions;
		}

		$hidden_slug = $this->plugin->hiddenMenuSlug();

		if ($is_client) {
			$link = '<a href="' . \admin_url('admin.php?page=' . $hidden_slug) . '">Access</a>';
			array_unshift($actions, $link);
			return $actions;
		}

		if ($this->plugin->isWhiteLabel() && $this->plugin->whiteLabelHiddenInMenu()) {
			$link = '<a href="' . \admin_url('admin.php?page=' . $hidden_slug) . '">Access</a>';
			array_unshift($actions, $link);
			return $actions;
		}

		return $actions;
	}

	/**
	 * Change the plugin data in the plugins list
	 * @since   1.6.0
	 */
	public function modifyPluginData(array $all_plugins): array {
		if ($this->plugin->isClient() || $this->plugin->isWhiteLabel()) {
			foreach ($all_plugins as &$all_plugin) {
				if ($all_plugin['Name'] === 'Motion.page') {
					$all_plugin['Name'] = $this->plugin->whiteLabelName();
					$all_plugin['Description'] = htmlspecialchars_decode($this->plugin->whiteLabelDescription());
				}
			}
		}

		return $all_plugins;
	}

	/**
	 * Add quick action link
	 * @since 2.0.0
	 */
	public function quickActionLink($actions, $post) {
		// prettier-ignore
		if (!\current_user_can('manage_options') || !$this->plugin->isEditLink()) return $actions;

		$post_types = ['page'];
		$post_types_filter = \apply_filters('motionpage/utils/pqal', ['page']);

		if (is_array($post_types_filter)) {
			$post_types = array_merge($post_types, $post_types_filter);
		}

		if (in_array($post->post_type, $post_types)) {
			$text = \__('Edit with Motion.page', MOTIONPAGE_NAME);
			if ($this->plugin->isWhiteLabel() && $this->plugin->whiteLabelName()) {
				$text = \esc_html__('Edit with ', $this->plugin->name()) . \esc_html($this->plugin->whiteLabelName());
			}

			$url = \get_admin_url(null, 'admin.php?page=motionpage');
			$encode = base64_encode(
				json_encode(
					[
						'post_id' => (int) $post->ID,
						'link' => \get_permalink($post->ID),
						'value' => html_entity_decode(\get_the_title($post->ID))
					],
					JSON_THROW_ON_ERROR
				)
			);

			$actions[MOTIONPAGE_NAME] = sprintf(
				'<a href="%s%s" title="%s" rel="permalink">%s</a>',
				$url,
				'&sp=' . $encode,
				$text,
				$text
			);
		}

		return $actions;
	}

	/**
	 * Add custom admin menu bar
	 * @since 2.0.0
	 */
	public function adminMenuBar(object $admin_bar): void {
		$admin_bar->add_menu([
			'id' => 'motionpage-ab',
			'title' => \__('Motion.page', $this->plugin->name()),
			'href' => '',
			'meta' => [
				'title' => \__('Motion.page', $this->plugin->name()),
				'onclick' => 'console.log(":(");'
			]
		]);
	}

	//public function dashboardWidgets(): void {
	//  \wp_add_dashboard_widget($this->plugin->name() . '_logs', 'Motion.page logs', function () {
	//      \esc_html_e('No Logs yet.', $this->plugin->name());
	//  });
	//}

	/**
	 * If available, show the changelog for sites in a multisite install.
	 * @see motionpage\App\Backend\showUpdateNotification()
	 * @since 2.0.0
	 * @version EDD/1.9.?
	 */
	public function showChangelog(): void {
		// prettier-ignore
		if (
			empty($_REQUEST['motionpage_sl_action']) ||
			$_REQUEST['motionpage_sl_action'] !== 'view_plugin_changelog'
		) return;

		// prettier-ignore
		if (empty($_REQUEST['plugin'])) return;

		if (empty($_REQUEST['slug']) || $_REQUEST['slug'] !== $this->plugin->eddSlug()) {
			return;
		}

		if (!current_user_can('update_plugins')) {
			\wp_die(
				\esc_html__('You do not have permission to install plugin updates', $this->plugin->name()),
				\esc_html__('Error', $this->plugin->name()),
				['response' => 403]
			);
		}

		$repoApiData = motionpageUpdater()->getRepoApiData();
		if (property_exists($repoApiData, 'sections') && $repoApiData->sections !== null) {
			$sections = motionpage()->convertObjectToArray($repoApiData->sections);
			if (!empty($sections['changelog'])) {
				echo '<div style="background:#fff;padding:10px;">' . \wp_kses_post($sections['changelog']) . '</div>';
			}
		}

		exit();
	}
}
