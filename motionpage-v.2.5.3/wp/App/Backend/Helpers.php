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
 * Helpers
 *
 * @package motionpage\App\Backend
 * @since 2.0.0
 */
class Helpers extends Base {
	/**
	 * Initialize the class.
	 *
	 * @since 2.0.0
	 */
	public function init(): void {
		/**
		 * This backend class is only being instantiated in the backend as requested in the Scaffold class
		 *
		 * @see Requester::isAdminBackend()
		 * @see Scaffold::__construct
		 *
		 * Add plugin code here for admin helpers specific functions
		 */

		//\add_action('wp_head', [$this, 'playground'], 9999);
		\add_filter('script_loader_tag', [$this, 'filterScripts'], 0, 3);
		\add_action('upgrader_process_complete', [$this, 'upgradeCompleted'], 10, 2);
		\add_action('motionpage/action/builder', [$this, 'upgradeProcess']);
		//\add_action('delete_attachment', [$this, 'deleteAttachment'], 10, 2);

		\add_filter('plugins_api', [$this, 'viewPluginVersionDetails'], 10, 3);
		\add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate']);
		//\add_action('admin_footer', [$this, 'pluginsFooter']);
	}

	/**
	 * Builder playground page
	 * @since   1.4.0
	 */
	public function playground(): void {
		if (!empty($_GET['motionpage_playground'] ?? '')) {
			//require MOTIONPAGE_DIR_PATH . 'core/includes/playground.php';
			die();
		}
	}

	/**
	 * Filter script attributes
	 * @since 2.0.0
	 */
	public function filterScripts(string $tag, string $handle, string $src): string {
		$module = \wp_scripts()->get_data($handle, 'mp-module');
		$defer = \wp_scripts()->get_data($handle, 'mp-defer');
		$async = \wp_scripts()->get_data($handle, 'mp-async');
		//$crossorigin = \wp_scripts()->get_data($handle, 'mp-crossorigin');
		$crossorigin = false;

		if (\str_contains($handle, 'module/hypewolf/mp/')) {
			$module = true;
			$crossorigin = $this->plugin->isDev();

			$scriptsDataAttr = motionpage()->getScriptsDataAttr();
			$tag = str_replace('<script ', sprintf('<script %s ', $scriptsDataAttr), $tag);

			$tag = str_replace('src=""', 'src="' . $src . '"', $tag); // CHECK
		} elseif (\str_starts_with($handle, 'mp-')) {
			$scriptsDataAttr = motionpage()->getScriptsDataAttr();
			$tag = str_replace('<script ', sprintf('<script %s ', $scriptsDataAttr), $tag);
			$tag = str_replace('src=""', 'src="' . $src . '"', $tag);
		} else {
			$is_allowed = \apply_filters('motionpage/lib/stripGSAP', false);
			$has_gsap = strpos(strtolower($handle), 'gsap') !== false;
			$has_sc = strpos(strtolower($handle), 'scrolltrigger') !== false;
			if ($is_allowed && ($has_gsap || $has_sc)) {
				$tag = '';
			}
		}

		if ($module) {
			$tag = str_replace('<script ', '<script type="module" ', $tag);
		}

		if ($defer) {
			$tag = str_replace('<script ', '<script defer ', $tag);
		}

		if ($async) {
			$tag = str_replace('<script ', '<script async ', $tag);
		}

		if ($crossorigin) {
			$tag = str_replace('></script>', ' crossorigin></script>', $tag);
		}

		return $tag;
	}

	/**
	 * After update plugin completed.
	 * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete/ Reference.
	 * @since 2.0.0
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	public function upgradeCompleted(\WP_Upgrader $upgrader, array $hook_extra): void {
		if (
			is_array($hook_extra) &&
			array_key_exists('plugins', $hook_extra) &&
			(($hook_extra['action'] ?? '') === 'update' &&
				($hook_extra['type'] ?? '') === 'plugin' &&
				is_array($hook_extra['plugins']) &&
				!empty($hook_extra['plugins']))
		) {
			foreach ($hook_extra['plugins'] as $plugin) {
				if ($plugin === MOTIONPAGE_BASENAME) {
					\set_transient('motionpage/updated', MOTIONPAGE_VERSION);
					break;
				}
			}
		}
	}

	public function upgradeProcess(): void {
		if (\get_transient('motionpage/updated')) {
			motionpage()->flushRewriteRules();
			\delete_transient('motionpage/updated');
		}
	}

	/**
	 * Delete files and folder tied to image sequence from the media library
	 * @since 2.0.0
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	//public function deleteAttachment($post_id, object $post): void {
	//	if (preg_match('/\/uploads\/motionpage\//', $post->guid)) {
	//		preg_match('/\/uploads\/motionpage\/(.*?)\/.*?\..*?$/', $post->guid, $matches);
	//		$folder_id = $matches[1] ?? false;
	//		if ($folder_id) {
	//			motionpage()->removeFolderFiles($folder_id);
	//		}
	//	}
	//}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 * @param  false|object|array	$_result - The result object or array. Default false.
	 * @param  string $_action - The type of information being requested from the Plugin Installation API.
	 * @param  object $_args - Plugin API arguments
	 */
	public function viewPluginVersionDetails($_result, $_action = '', $_args = null) {
		if ($_action !== 'plugin_information') {
			return $_result;
		}

		$slug = $this->plugin->eddSlug();

		if (!(property_exists($_args, 'slug') && $_args->slug !== null) || $_args->slug !== $slug) {
			return $_result;
		}

		$to_send = [
			'slug' => $slug,
			'is_ssl' => \is_ssl(),
			'fields' => [
				'banners' => [],
				'reviews' => false,
				'icons' => []
			]
		];

		// Get the transient where we store the api request for this plugin for 12 hours
		$motionpage_api_request_transient = motionpageUpdater()->getCachedVersionInfo();

		//If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
		if (empty($motionpage_api_request_transient)) {
			$api_response = motionpageUpdater()->apiRequest($to_send);
			motionpageUpdater()->setVersionInfoCache($api_response);
			if ($api_response !== false) {
				$_result = $api_response;
			}
		} else {
			$_result = $motionpage_api_request_transient;
		}

		// Convert sections into an associative array, since we're getting an object, but Core expects an array.
		if (
			property_exists($_result, 'sections') &&
			$_result->sections !== null &&
			!is_array($_result->sections)
		) {
			$_result->sections = motionpage()->convertObjectToArray($_result->sections);
		}

		// Convert banners into an associative array, since we're getting an object, but Core expects an array.
		if (property_exists($_result, 'banners') && $_result->banners !== null && !is_array($_result->banners)) {
			$_result->banners = motionpage()->convertObjectToArray($_result->banners);
		}

		// Convert icons into an associative array, since we're getting an object, but Core expects an array.
		if (property_exists($_result, 'icons') && $_result->icons !== null && !is_array($_result->icons)) {
			$_result->icons = motionpage()->convertObjectToArray($_result->icons);
		}

		// Convert contributors into an associative array, since we're getting an object, but Core expects an array.
		if (
			property_exists($_result, 'contributors') &&
			$_result->contributors !== null &&
			!is_array($_result->contributors)
		) {
			$_result->contributors = motionpage()->convertObjectToArray($_result->contributors);
		}

		if (!(property_exists($_result, 'plugin') && $_result->plugin !== null)) {
			$_result->plugin = MOTIONPAGE_BASENAME;
		}

		return $_result;
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @uses api_request()
	 *
	 * @param  array $_transient_data Update array build by WordPress.
	 * @return array Modified update array with custom plugin data.
	 */
	public function checkUpdate($_transient_data) {
		if (!is_object($_transient_data)) {
			$_transient_data = new \stdClass();
		}

		$version = $this->plugin->version();
		$repoApiData = motionpageUpdater()->getRepoApiData();

		if (
			$repoApiData &&
			is_object($repoApiData) &&
			(property_exists($repoApiData, 'new_version') && $repoApiData->new_version !== null)
		) {
			if (version_compare($version, $repoApiData->new_version, '<')) {
				$_transient_data->response[MOTIONPAGE_BASENAME] = $repoApiData;
			} else {
				// Populating the no_update information is required to support auto-updates in WordPress 5.5.
				$_transient_data->no_update[MOTIONPAGE_BASENAME] = $repoApiData;
			}
		}

		$_transient_data->last_checked = time();
		$_transient_data->checked[MOTIONPAGE_BASENAME] = $version;

		return $_transient_data;
	}

	public function pluginsFooter(): void {
		global $pagenow;
		if ($pagenow === 'plugins.php') {
			\wp_enqueue_script(
				'mp-deactivation-message',
				MOTIONPAGE_DIR_URL . 'assets/js/deactivation-message.js',
				[],
				MOTIONPAGE_VERSION,
				true
			);
		}
	}
}
