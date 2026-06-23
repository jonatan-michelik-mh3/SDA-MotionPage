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

namespace motionpage\Common;

use motionpage\Common\Abstracts\Base;

/**
 * Main function class for external uses
 *
 * @see motionpage()
 * @package motionpage\Common
 */
class Functions extends Base {
	private function readENV() {
		if (!function_exists('is_readable')) {
			return false;
		}

		$env = MOTIONPAGE_DIR_PATH . '.env';
		if (!file_exists($env) || !is_readable($env)) {
			return false;
		}

		return file_get_contents($env);
	}

	/**
	 * Determine if is development by using motionpage()->isDev()
	 * @since 2.0.0
	 */
	public function isDev(): bool {
		$distPath = MOTIONPAGE_DIR_PATH . 'dist';

		if (!function_exists('is_readable')) {
			return false;
		}

		if (!file_exists($distPath) || !is_readable($distPath)) {
			// No dist folder = development mode
			return true;
		}

		$files = array_diff(scandir($distPath), ['.', '..', '.vite']);
		// Empty dist folder = development mode
		return empty($files);
	}

	public function getDevURL() {
		$defaultURL = 'oxyninja.animator';
		$env_content = $this->readENV();
		if (!$env_content) {
			return $defaultURL;
		}
		preg_match('/DEV_URL=(.*?)\n/', $env_content, $matches);
		return !empty($matches) && isset($matches[1]) ? $matches[1] : $defaultURL;
	}

	/**
	 * Create DB tables on activation for admin builder
	 * motionpage()->createDatabase()
	 * @since   1.0.0
	 * @version 1.1
	 */
	public function createDatabase($preventCreation = false): void {
		global $wpdb;

		$prefix = $wpdb->prefix . 'motionpage';
		$table_name_code = \esc_sql($prefix . '_code');
		$table_name_data = \esc_sql($prefix . '_data');
		$charset_collate = $wpdb->get_charset_collate();

		$createCodeTable = fn() => "CREATE TABLE IF NOT EXISTS {$table_name_code} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				script_value longtext NOT NULL,
				generated_code longtext NULL,
				post_id bigint(20) NULL,
				is_global tinyint(1) NOT NULL,
				is_active tinyint(1) NOT NULL,
				plugins varchar(191) NULL,
				types varchar(191) NULL,
				cats varchar(191) NULL,
				data_id bigint(20) UNSIGNED NOT NULL,
				PRIMARY KEY (id)
			) {$charset_collate};";

		$createDataTable = fn() => "CREATE TABLE IF NOT EXISTS {$table_name_data} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				script_name varchar(191) NOT NULL,
				trigger_name varchar(191) NOT NULL,
				reload longtext NOT NULL,
				PRIMARY KEY (id)
			) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		\dbDelta($createCodeTable());
		\dbDelta($createDataTable());

		if (!$preventCreation) {
			$this->addOption('motionpage_db_version', $this->plugin->databaseVersion());
		}
	}

	/**
	 * Create settings during activation or upgrade on mutli-site
	 * motionpage()->createSettings()
	 * @since 2.0.0
	 */
	public function createSettings($main = []): void {
		if (!empty($main)) {
			$this->addOption('motionpage_main', $main);
			return;
		}

		$main_settings = motionpage()->getMainSettings();
		if (!empty($main_settings)) {
			$this->addOption('motionpage_main', $main_settings);
			return;
		}

		$config = [
			'license_status' => 'invalid',
			'breakpoints' => [
				'laptops' => [
					'unit' => 'max-width',
					'value' => '992'
				],
				'tablets' => [
					'unit' => 'max-width',
					'value' => '768'
				],
				'phones' => [
					'unit' => 'max-width',
					'value' => '576'
				]
			],
			'cdn' => 0,
			'language' => 'en',
			'advanced' => [
				'tooltips' => 1,
				'overflow' => 1,
				'disabledTypes' => [],
				'layout' => 0,
				'hashAnchorLinks' => 0,
				'stripGSAP' => 0,
				'debugMode' => 0
			],
			'system' => [
				'last' => MOTIONPAGE_VERSION,
				'current' => MOTIONPAGE_VERSION,
				'permission' => 'manage_options',
				'client' => 0,
				'theme' => 'dark',
				'beta' => 0,
				'editLink' => 1,
				'wipeOnUninstall' => 0,
				'polylang' => 0,
				'wpml' => 0,
				'siteUrl' => \untrailingslashit(\home_url()),
				'migration_status' => 'complete'
			],
			'scrollSmoother' => [
				'isOpen' => 0,
				'code' => '',
				'wrapper' => '',
				'content' => '',
				'ease' => 'Expo',
				'smooth' => 0.8,
				'smoothTouch' => 0,
				'effects' => '',
				'normalizeScroll' => 0,
				'ignoreMobileResize' => 0,
				'anchorFix' => 1,
				'hashFix' => 0,
				'fixedSticky' => 0,
				'speed' => 1
			],
			'lenis' => [
				'isOpen' => 0,
				'code' => '',
				'duration' => 1.2,
				'easing' => 'easeOutExpo',
				'smoothTouch' => 0,
				'mouseMultiplier' => 1,
				'touchMultiplier' => 1.5,
				'excludedPages' => '',
				'legacyWrapper' => '',
				'legacyContent' => ''
			]
		];

		$this->addOption('motionpage_main', $config);
	}

	/**
	 * Check if plugins is active
	 * @since 2.0.0
	 */
	public static function isPluginActive($plugin): bool {
		if (!function_exists('is_plugin_active_for_network')) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$active_plugins = motionpage()->getOption('active_plugins', []);
		return in_array($plugin, (array) $active_plugins) || \is_plugin_active_for_network($plugin);
	}

	/**
	 * Delete all hidden files from the uploads/sequence/*uid* folder + folder itself
	 * calla with motionpage()->removeFolderFiles()
	 * @since 2.0.0
	 */
	// TODO : ERRORS
	public function removeFolderFiles($folder): void {
		$sequence_folder = $this->plugin->sequenceFolder();
		$cud = \wp_upload_dir()['basedir'] . '/' . $sequence_folder . '/' . $folder;

		$iter = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($cud, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iter as $fileinfo) {
			if (strpos($fileinfo->getFilename(), '.') !== false) {
				if ($fileinfo->isDir()) {
					@rmdir($fileinfo->getPathname());
				} elseif ($fileinfo->isFile()) {
					\wp_delete_file_from_directory($fileinfo->getPathname(), $cud);
				}
			}
		}

		// 2 accounts for '.' and '..'
		if (is_dir($cud) && (\is_countable(scandir($cud)) ? count(scandir($cud)) : 0) == 2) {
			@rmdir($cud);
		}
	}

	/**
	 * Switch default upload directory to the plugin's upload directory
	 * motionpage()->modifyUploadDir()
	 * @since  1.6.0
	 */
	public function modifyUploadDir(array $uploads, string $mydir): array {
		$uploads['subdir'] = $mydir;
		$uploads['path'] = $uploads['basedir'] . $mydir;
		$uploads['url'] = $uploads['baseurl'] . $mydir;
		return $uploads;
	}

	/**
	 * motionpage()->createDir()
	 * @since  1.6.0
	 */
	public function createDir($name): bool {
		try {
			if (!is_dir($name)) {
				$chmod_dir = 0755 & ~umask();
				if (defined('FS_CHMOD_DIR')) {
					$chmod_dir = FS_CHMOD_DIR;
				}

				if (!mkdir($name, $chmod_dir) && !is_dir($name)) {
					return false;
				}
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function getPostTypes(): array {
		$disable_types = $this->plugin->data()['disabled_types'];
		return array_filter(
			\get_post_types(['public' => true]),
			function ($t) use (&$disable_types): bool {
				return !in_array(
					$t,
					array_merge(
						[
							'attachment',
							'pp_email_submission',
							'pp_video_block',
							'rank_math_schema',
							'piotnetgrid',
							'piotnetgrid-archive',
							'piotnetgrid-card',
							'piotnetgrid-facet',
							'piotnetgrid-template',
							'cartflows_step',
							'jet-engine',
							'ct_template',
							'oxy_user_library'
						],
						$disable_types
					)
				);
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	public function canUseCoep() {
		$browsers = ['Opera', 'Edg', 'Chrome', 'Safari', 'Firefox', 'MSIE', 'Trident'];
		$agent = $_SERVER['HTTP_USER_AGENT'];
		$ub = '';

		foreach ($browsers as $browser) {
			if (strpos($agent, $browser) !== false) {
				$ub = $browser;
				break;
			}
		}

		switch ($ub) {
			case 'MSIE':
			case 'Trident':
			case 'Safari':
				return false;

			case 'Firefox':
				if (preg_match('/Firefox\/([0-9\.]+)/', $agent, $matches)) {
					$version = floatval($matches[1]);
					return $version >= 119;
				}

				return false;

			default:
				return true;
		}
	}

	public function flushRewriteRules($hard = true): void {
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->flush_rules($hard);
	}

	public function addOption(string $name, $data) {
		if (\is_multisite()) {
			return \add_blog_option(\get_current_blog_id(), $name, $data);
		}

		return \add_option($name, $data, '', false);
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

	public function getMainSettings() {
		return $this->getOption('motionpage_main', []);
	}

	public function getClientModeSettings() {
		return $this->getOption('motionpage_client', []);
	}

	/**
	 * Convert some objects to arrays when injecting data into the update API
	 * @since 2.0.0
	 */
	public function convertObjectToArray(\stdClass $data): array {
		// prettier-ignore
		if (!is_array($data) && !is_object($data)) return [];

		$new_data = [];
		foreach ($data as $key => $value) {
			$new_data[$key] = is_object($value) ? $this->convertObjectToArray($value) : $value;
		}

		return $new_data;
	}

	/**
	 * Data attributes for the <script> tag
	 * @since 2.1.7
	 */
	public function getScriptsDataAttr(): string {
		// CookieBot Ignore Filter - Cookie Consent : https://www.cookiebot.com/en/
		$cookieconsent = \apply_filters('motionpage/utils/cookieconsent', false);
		$cc_code = $cookieconsent ? ' data-cookieconsent=ignore' : '';
		$speedien = defined('SPEEDIEN_API_URL') ? ' data-wpspdn-nooptimize=true' : '';
		$wpm = class_exists('\\WP_Meteor\\Engine\\Initialize', false) ? ' data-wpmeteor-nooptimize=true' : '';
		$wpr = defined('WP_ROCKET_VERSION') ? ' nowprocket' : '';
		return \esc_attr('data-mp=true data-cfasync=false' . $cc_code . $speedien . $wpm . $wpr);
	}

	/**
	 * @param string $raw_folder
	 * @param null|string $default_folder
	 * @return string
	 */
	public function sanitizeFolderName($raw_folder, $default_folder = null) {
		if (!$default_folder) {
			$randomString = hash('sha256', uniqid((string) \wp_rand(), true));
			$default_folder = substr($randomString, 0, 16);
		}

		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $raw_folder)) {
			$sequence_folder = $default_folder;
		} else {
			$sequence_folder = basename($raw_folder);
			if (strpos($sequence_folder, '..') !== false) {
				$sequence_folder = $default_folder;
			}
		}

		if (strlen($sequence_folder) > 25) {
			$sequence_folder = substr($sequence_folder, 0, 25);
		}

		return $sequence_folder;
	}
}
