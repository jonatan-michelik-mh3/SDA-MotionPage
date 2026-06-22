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

namespace motionpage\Common;

use motionpage\Common\Abstracts\Base;

/**
 * Updater function class for external uses
 * @see motionpageUpdater()
 * @package motionpage\Common
 */
class UpdaterFunctions extends Base {
	private function failedStoreHash(): string {
		return 'motionpage_sl_failed_http_' . hash('crc32c', $this->plugin->eddStore());
	}

	private function licenseHash(): string {
		$s = 'motionpage' . $this->plugin->license() . $this->plugin->eddBeta();
		return hash('crc32c', serialize($s));
	}

	public function updateOption(string $name, $data) {
		if (\is_multisite()) {
			return \update_blog_option(\get_current_blog_id(), $name, $data);
		}

		return \update_option($name, $data, false);
	}

	public function deleteOption(string $name) {
		if (\is_multisite()) {
			return \delete_blog_option(\get_current_blog_id(), $name);
		}

		return \delete_option($name);
	}

	public function getOption(string $option, $default = null) {
		if (\is_multisite()) {
			return \get_blog_option(\get_current_blog_id(), $option, $default);
		}

		return \get_option($option, $default);
	}

	/**
	 * Determines if a request has recently failed.
	 * @since 2.0.0
	 * @version EDD/1.9.1
	 */
	private function requestRecentlyFailed(): bool {
		$failed_request_details = $this->getOption($this->failedStoreHash());

		// Request has never failed.
		if (empty($failed_request_details) || !is_numeric($failed_request_details)) {
			return false;
		}

		// Request previously failed, but the timeout has expired. Try again.
		if (time() > $failed_request_details) {
			$this->deleteOption($this->failedStoreHash());
			return false;
		}

		return true;
	}

	/**
	 * Gets the current version information from the remote site.
	 * @since 2.0.0
	 * @version EDD/1.9.1
	 * @return array|false
	 */
	private function getVersionFromRemote() {
		$api_params = [
			'edd_action' => 'get_version',
			'license' => empty($this->plugin->license()) ? '' : $this->plugin->license(),
			'item_id' => $this->plugin->eddID() ?? false,
			'version' => $this->plugin->version() ?? false,
			'slug' => $this->plugin->eddSlug(),
			'url' => \home_url(),
			'beta' => $this->plugin->eddBeta(),
			'php_version' => phpversion(),
			'wp_version' => \get_bloginfo('version')
		];

		$request = \wp_remote_post($this->plugin->eddStore(), [
			'timeout' => 15,
			'sslverify' => true,
			'body' => $api_params
		]);

		/**
		 * Logs a failed HTTP request for this API URL.
		 * We set a timestamp for 1 hour from now. This prevents future API requests from being
		 * made to this domain for 1 hour. Once the timestamp is in the past, API requests
		 * will be allowed again. This way if the site is down for some reason we don't bombard
		 * it with failed API requests.
		 *
		 * @see EDD_SL_Plugin_Updater::request_recently_failed
		 * @since 2.0.0
		 * @version EDD/1.9.1
		 */
		if (\is_wp_error($request) || \wp_remote_retrieve_response_code($request) !== 200) {
			$this->updateOption($this->failedStoreHash(), strtotime('+1 hour'));
			return false;
		}

		$request = json_decode(\wp_remote_retrieve_body($request), null, 512, JSON_THROW_ON_ERROR);

		if ($request && (property_exists($request, 'sections') && $request->sections !== null)) {
			$request->sections = \maybe_unserialize($request->sections);
		} else {
			$request = false;
		}

		if ($request && (property_exists($request, 'banners') && $request->banners !== null)) {
			$request->banners = \maybe_unserialize($request->banners);
		}

		if ($request && (property_exists($request, 'icons') && $request->icons !== null)) {
			$request->icons = \maybe_unserialize($request->icons);
		}

		if (!empty($request->sections)) {
			foreach ($request->sections as $key => $section) {
				$request->$key = (array) $section;
			}
		}

		return $request;
	}

	/**
	 * Calls the API and, if successfull, returns the object delivered by the API.
	 * @since 2.0.0
	 * @version EDD/1.9.1
	 *
	 * @param  array $data Parameters for the API action.
	 * @return false|object|void
	 */
	public function apiRequest(array $data) {
		// prettier-ignore
		if ($data['slug'] !== $this->plugin->eddSlug()) return;

		// Don't allow a plugin to ping itself
		// prettier-ignore
		if (\trailingslashit(\home_url()) === $this->plugin->eddStore()) return false;

		// prettier-ignore
		if ($this->requestRecentlyFailed()) return false;

		return $this->getVersionFromRemote();
	}

	/**
	 * Get the version info from the cache, if it exists.
	 */
	public function getCachedVersionInfo($cache_key = '') {
		// prettier-ignore
		if (empty($cache_key)) $cache_key = 'motionpage_sl_' . $this->licenseHash();

		$cache = $this->getOption($cache_key, []);

		// Cache is expired
		// prettier-ignore
		if (empty($cache['timeout']) || time() > $cache['timeout']) return '';

		// We need to turn the icons into an array, thanks to WP Core forcing these into an object at some point.
		$cache['value'] = json_decode($cache['value'], null, 512, JSON_THROW_ON_ERROR);
		if (!empty($cache['value']->icons)) {
			$cache['value']->icons = (array) $cache['value']->icons;
		}

		return $cache['value'];
	}

	/**
	 * Gets the plugin's tested version.
	 * @since 2.0.0
	 * @version EDD/1.9.4
	 * @return null|string
	 */
	private function getTestedVersion(object $version_info) {
		// There is no tested version.
		// prettier-ignore
		if (empty($version_info->tested)) return null;

		// Strip off extra version data so the result is x.y or x.y.z.
		$current_wp_version = explode('-', \get_bloginfo('version'))[0];

		// The tested version is greater than or equal to the current WP version, no need to do anything.
		if (version_compare($version_info->tested, $current_wp_version, '>=')) {
			return $version_info->tested;
		}

		$current_version_parts = explode('.', $current_wp_version);
		$tested_parts = explode('.', $version_info->tested);

		// The current WordPress version is x.y.z, so update the tested version to match it.
		if (
			isset($current_version_parts[2]) &&
			$current_version_parts[0] === $tested_parts[0] &&
			$current_version_parts[1] === $tested_parts[1]
		) {
			$tested_parts[2] = $current_version_parts[2];
		}

		return implode('.', $tested_parts);
	}

	/**
	 * Adds the plugin version information to the database. Expires in 12 hours.
	 * @since 2.0.0
	 * @version EDD/1.9.1
	 */
	public function setVersionInfoCache($value = '', $cache_key = ''): void {
		// prettier-ignore
		if (empty($cache_key)) $cache_key = 'motionpage_sl_' . $this->licenseHash();

		$data = [
			'timeout' => strtotime('+12 hours', time()),
			'value' => \wp_json_encode($value)
		];

		$this->updateOption($cache_key, $data);

		// Delete the duplicate option
		$this->deleteOption('motionpage_api_request_' . $this->licenseHash());
	}

	/**
	 * Get repo API data from store / Save to cache
	 * @since 2.0.0
	 * @version EDD/1.9.1
	 * @return \stdClass
	 */
	public function getRepoApiData() {
		$version_info = $this->getCachedVersionInfo();

		if (!$version_info) {
			$version_info = $this->apiRequest(['slug' => $this->plugin->eddSlug()]);

			// prettier-ignore
			if (!$version_info) return false;

			// This is required for your plugin to support auto-updates in WordPress 5.5.
			$version_info->plugin = MOTIONPAGE_BASENAME;
			$version_info->id = MOTIONPAGE_BASENAME;
			$version_info->tested = $this->getTestedVersion($version_info);
			if (!isset($version_info->requires)) {
				$version_info->requires = '';
			}
			if (!isset($version_info->requires_php)) {
				$version_info->requires_php = '';
			}

			$this->setVersionInfoCache($version_info);
		}

		return $version_info;
	}

	/**
	 * Convert some objects to arrays when injecting data into the update API
	 * @param \stdClass $data
	 * @return array
	 */
	private function convertObjectToArray($data) {
		if (!is_array($data) && !is_object($data)) {
			return [];
		}
		$new_data = [];
		foreach ($data as $key => $value) {
			$new_data[$key] = is_object($value) ? $this->convertObjectToArray($value) : $value;
		}
		return $new_data;
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @uses apiRequest()
	 *
	 * @param array   $_transient_data Update array build by WordPress.
	 * @return array Modified update array with custom plugin data.
	 */
	public function checkUpdate($_transient_data) {
		if (!is_object($_transient_data)) {
			$_transient_data = new \stdClass();
		}

		if (!empty($_transient_data->response) && !empty($_transient_data->response[$this->plugin->eddSlug()])) {
			return $_transient_data;
		}

		$current = $this->getUpdateTransientData();
		if (false !== $current && is_object($current) && isset($current->new_version)) {
			if (version_compare($this->plugin->version(), $current->new_version, '<')) {
				$_transient_data->response[$this->plugin->eddSlug()] = $current;
			} else {
				// Populating the no_update information is required to support auto-updates in WordPress 5.5.
				$_transient_data->no_update[$this->plugin->eddSlug()] = $current;
			}
		}
		$_transient_data->last_checked = time();
		$_transient_data->checked[$this->plugin->eddSlug()] = $this->plugin->version();

		return $_transient_data;
	}

	/**
	 * Gets a limited set of data from the API response.
	 * This is used for the update_plugins transient.
	 *
	 * @since 3.8.12
	 * @return \stdClass|false
	 */
	private function getUpdateTransientData() {
		$version_info = $this->getRepoApiData();

		if (!$version_info) {
			return false;
		}

		$limited_data = new \stdClass();
		$limited_data->slug = $this->plugin->eddSlug();
		$limited_data->plugin = MOTIONPAGE_BASENAME;
		$limited_data->url = $version_info->url;
		$limited_data->package = $version_info->package;
		$limited_data->icons = $this->convertObjectToArray($version_info->icons);
		$limited_data->banners = $this->convertObjectToArray($version_info->banners);
		$limited_data->new_version = $version_info->new_version;
		$limited_data->tested = $version_info->tested;
		$limited_data->requires = $version_info->requires;
		$limited_data->requires_php = $version_info->requires_php;

		return $limited_data;
	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @uses apiRequest()
	 *
	 * @param mixed   $_data
	 * @param string  $_action
	 * @param object  $_args
	 * @return object $_data
	 */
	public function pluginsApiFilter($_data, $_action = '', $_args = null) {
		if ('plugin_information' !== $_action) {
			return $_data;
		}

		if (!isset($_args->slug) || $_args->slug !== $this->plugin->eddSlug()) {
			return $_data;
		}

		$to_send = [
			'slug' => $this->plugin->eddSlug(),
			'is_ssl' => \is_ssl(),
			'fields' => [
				'banners' => [],
				'reviews' => false,
				'icons' => []
			]
		];

		$edd_api_request_transient = $this->getCachedVersionInfo();

		if (empty($edd_api_request_transient)) {
			$api_response = $this->apiRequest(['action' => 'plugin_information', 'data' => $to_send]);

			$this->setVersionInfoCache($api_response);

			if (false !== $api_response) {
				$_data = $api_response;
			}
		} else {
			$_data = $edd_api_request_transient;
		}

		if (isset($_data->sections) && !is_array($_data->sections)) {
			$_data->sections = $this->convertObjectToArray($_data->sections);
		}

		if (isset($_data->banners) && !is_array($_data->banners)) {
			$_data->banners = $this->convertObjectToArray($_data->banners);
		}

		if (isset($_data->icons) && !is_array($_data->icons)) {
			$_data->icons = $this->convertObjectToArray($_data->icons);
		}

		if (isset($_data->contributors) && !is_array($_data->contributors)) {
			$_data->contributors = $this->convertObjectToArray($_data->contributors);
		}

		if (!isset($_data->plugin)) {
			$_data->plugin = MOTIONPAGE_BASENAME;
		}

		if (!isset($_data->version) && !empty($_data->new_version)) {
			$_data->version = $_data->new_version;
		}

		return $_data;
	}

	/**
	 * Show the update notification on multisite subsites.
	 *
	 * @param string  $file
	 * @param array   $plugin
	 */
	public function showUpdateNotification($file, $plugin) {
		// Return early if in the network admin, or if this is not a multisite install.
		if (\is_network_admin() || !\is_multisite()) {
			return;
		}

		// Allow single site admins to see that an update is available.
		if (!\current_user_can('activate_plugins')) {
			return;
		}

		if ($this->plugin->eddSlug() !== $file) {
			return;
		}

		// Do not print any message if update does not exist.
		$update_cache = \get_site_transient('update_plugins');

		if (!isset($update_cache->response[$this->plugin->eddSlug()])) {
			if (!is_object($update_cache)) {
				$update_cache = new \stdClass();
			}
			$update_cache->response[$this->plugin->eddSlug()] = $this->getRepoApiData();
		}

		// Return early if this plugin isn't in the transient->response or if the site is running the current or newer version of the plugin.
		if (
			empty($update_cache->response[$this->plugin->eddSlug()]) ||
			version_compare(
				$this->plugin->version(),
				$update_cache->response[$this->plugin->eddSlug()]->new_version,
				'>='
			)
		) {
			return;
		}

		printf(
			'<tr class="plugin-update-tr %3$s" id="%1$s-update" data-slug="%1$s" data-plugin="%2$s">',
			$this->plugin->eddSlug(),
			$file,
			in_array($this->plugin->eddSlug(), $this->getActivePlugins(), true) ? 'active' : 'inactive'
		);

		echo '<td colspan="3" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt"><p>';

		$changelog_link = '';
		if (!empty($update_cache->response[$this->plugin->eddSlug()]->sections->changelog)) {
			$changelog_link = \add_query_arg(
				[
					'edd_sl_action' => 'view_plugin_changelog',
					'plugin' => urlencode($this->plugin->eddSlug()),
					'slug' => urlencode($this->plugin->eddSlug()),
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
				'plugin' => urlencode($this->plugin->eddSlug())
			],
			\self_admin_url('update.php')
		);

		printf(
			/* translators: the plugin name. */
			\esc_html__('There is a new version of %1$s available.', 'easy-digital-downloads'),
			\esc_html($plugin['Name'])
		);

		if (!current_user_can('update_plugins')) {
			echo ' ';
			\esc_html_e('Contact your network administrator to install the update.', 'easy-digital-downloads');
		} elseif (empty($update_cache->response[$this->plugin->eddSlug()]->package) && !empty($changelog_link)) {
			echo ' ';
			printf(
				/* translators: 1. opening anchor tag, do not translate 2. the new plugin version 3. closing anchor tag, do not translate. */
				\__('%1$sView version %2$s details%3$s.', 'easy-digital-downloads'),
				'<a target="_blank" class="thickbox open-plugin-details-modal" href="' .
					\esc_url($changelog_link) .
					'">',
				\esc_html($update_cache->response[$this->plugin->eddSlug()]->new_version),
				'</a>'
			);
		} elseif (!empty($changelog_link)) {
			echo ' ';
			printf(
				\__('%1$sView version %2$s details%3$s or %4$supdate now%5$s.', 'easy-digital-downloads'),
				'<a target="_blank" class="thickbox open-plugin-details-modal" href="' .
					\esc_url($changelog_link) .
					'">',
				\esc_html($update_cache->response[$this->plugin->eddSlug()]->new_version),
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

		\do_action("in_plugin_update_message-{$file}", $plugin, $plugin);

		echo '</p></div></td></tr>';
	}

	/**
	 * Gets the plugins active in a multisite network.
	 * @since 2.0.0
	 * @version EDD/1.9.1
	 */
	public function getActivePlugins(): array {
		$active_plugins = (array) $this->getOption('active_plugins', []);
		$active_network_plugins = (array) \get_site_option('active_sitewide_plugins', []);
		return array_merge($active_plugins, array_keys($active_network_plugins));
	}
}
