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
 * Plugin data which are used through the plugin, most of them are defined
 * by the root file meta data. The data is being inserted in each class
 * that extends the Base abstract class
 *
 * @see Base
 * @package motionpage\Config
 * @since 2.0.0
 */
final class Plugin {
	/**
	 * Singleton trait
	 */
	use Singleton;

	/**
	 * Get the plugin meta data from the root file and include own data
	 * @since 2.0.0
	 */
	public function data(): array {
		$main_settings = motionpage()->getMainSettings();
		$is_beta = ($main_settings['system']['beta'] ?? 0) === 1;
		$is_edit_link = ($main_settings['system']['editLink'] ?? 0) === 1;

		$plugin_data = [
			'settings' => $main_settings,
			'development' => motionpage()->isDev(),
			'edd_store' => 'https://motion.page',
			'edd_id' => 423,
			'edd_beta' => \apply_filters('motionpage/edd/beta', $is_beta),
			'edd_slug' => 'motion-page-plugin',
			'client_mode' => ($main_settings['system']['client'] ?? 0) === 1 ?? 0,
			'gsap' => '3.13.0',
			'sequence_folder' => 'sequence', // TODO: allow filters
			'database_version' => '2.0',
			'assets_path' => MOTIONPAGE_DIR_PATH . 'assets/',
			'assets_url' => MOTIONPAGE_DIR_URL . 'assets/',
			'dist_path' => MOTIONPAGE_DIR_PATH . 'dist/',
			'dist_url' => MOTIONPAGE_DIR_URL . 'dist/',
			'disabled_types' => $main_settings['advanced']['disabledTypes'] ?? [],
			'internal_rest' => 'motionpage/v1',
			'show_edit_link' => \apply_filters('motionpage/settings/showEditLink', $is_edit_link),
			'cm_wl_slug' => 'pbcxowd'
		];

		return array_merge(
			\apply_filters(
				'motionpage/lib/meta',
				\get_file_data(
					MOTIONPAGE_FILE,
					[
						'name' => 'Plugin Name',
						'uri' => 'Plugin URI',
						'description' => 'Description',
						'version' => 'Version',
						'author' => 'Author',
						'author-uri' => 'Author URI',
						'text-domain' => 'Text Domain',
						'domain-path' => 'Domain Path',
						'required-php' => 'Requires PHP',
						'required-wp' => 'Requires WP',
						'namespace' => 'Namespace'
					],
					'plugin'
				)
			),
			$plugin_data
		);
	}

	/**
	 * Get the plugin settings
	 * @since 2.0.0
	 */
	public function settings(): array {
		return $this->data()['settings'];
	}

	/**
	 * Get the plugin license key
	 * @since 2.0.0
	 */
	public function license(): string {
		return trim($this->data()['settings']['license_key'] ?? '');
	}

	/**
	 * Get the plugin version number
	 * @since 2.0.0
	 */
	public function version(): string {
		return (string) $this->data()['version'];
	}

	/**
	 * Get the required minimum PHP version
	 * @since 2.0.0
	 */
	public function requiredPHP(): string {
		return (string) $this->data()['required-php'];
	}

	/**
	 * Get the required minimum WP version
	 * @since 2.0.0
	 */
	public function requiredWP(): string {
		return (string) $this->data()['required-wp'];
	}

	/**
	 * Get the plugin name
	 * @since 2.0.0
	 */
	public function name(): string {
		return $this->data()['name'];
	}

	/**
	 * Get the plugin text domain
	 * @since 2.0.0
	 */
	public function textDomain(): string {
		return $this->data()['text-domain'];
	}

	/**
	 * Get the plugin namespace
	 * @since 2.0.0
	 */
	public function namespace(): string {
		return $this->data()['namespace'];
	}

	/**
	 * Get the plugin development mode
	 * @since 2.0.0
	 */
	public function isDev(): bool {
		return $this->data()['development'];
	}

	/**
	 * Get the plugin development mode
	 * @since 2.0.0
	 */
	public function eddStore(): string {
		return \trailingslashit($this->data()['edd_store']);
	}

	/**
	 * Get the plugin EDD STORE ID
	 * @since 2.0.0
	 */
	public function eddID(): int {
		return $this->data()['edd_id'];
	}

	/**
	 * Check if the plugin can download beta versions
	 * @since 2.0.0
	 */
	public function eddBeta(): bool {
		return $this->data()['edd_beta'];
	}

	/**
	 * Get the plugin EDD PLUGIN NAME SLUG
	 * @since 2.0.0
	 */
	public function eddSlug(): string {
		return $this->data()['edd_slug'];
	}

	/**
	 * Get the plugin client mode
	 * @since 2.0.0
	 */
	public function isClient(): bool {
		return (bool) $this->data()['client_mode'];
	}

	/**
	 * Get the GreenSock plugins version
	 * @since 2.0.0
	 */
	public function gsapVersion(): string {
		return $this->data()['gsap'];
	}

	/**
	 * Get image sequence folder name
	 * @since 2.0.0
	 */
	public function sequenceFolder(): string {
		return motionpage()->sanitizeFolderName($this->data()['sequence_folder'], 'sequence');
	}

	/**
	 * Get the current version of Motion.page database
	 * @since 2.0.0
	 */
	public function databaseVersion(): string {
		return $this->data()['database_version'];
	}

	/**
	 * Get the path of assets folder
	 * @since 2.0.0
	 */
	public function assetsPath(): string {
		return $this->data()['assets_path'];
	}

	/**
	 * Get the URL of assets folder
	 * @since 2.0.0
	 */
	public function assetsURL(): string {
		return $this->data()['assets_url'];
	}

	/**
	 * Get the path of dist folder
	 * @since 2.0.0
	 */
	public function distPath(): string {
		return $this->data()['dist_path'];
	}

	/**
	 * Get the URL of dist folder
	 * @since 2.0.0
	 */
	public function distURL(): string {
		return $this->data()['dist_url'];
	}

	/**
	 * Get the REST API namespace + version
	 * @since 2.0.0
	 */
	public function internalRest(): string {
		return $this->data()['internal_rest'];
	}

	/**
	 * Settings - show edit link (Edit with Motion.page)
	 * @since 2.1.0
	 */
	public function isEditLink(): bool {
		return (bool) $this->data()['show_edit_link'];
	}

	/**
	 * Client Mode / White label - slug
	 * @since 2.1.0
	 */
	public function hiddenMenuSlug(): string {
		return $this->data()['cm_wl_slug'];
	}

	/**
	 * Get the client mode meta data from the root file and include own data
	 * @since 2.0.0
	 */
	public function clientModeData(): array {
		$settings = motionpage()->getClientModeSettings();

		$active = \apply_filters('motionpage/wl/active', $settings['active'] ?? 0);
		$name = \apply_filters('motionpage/wl/name', $settings['name'] ?? 'Motion.page');
		$desc = \apply_filters('motionpage/wl/description', $settings['description'] ?? "Move it like it's HOT!");
		$author = \apply_filters(
			'motionpage/wl/author',
			$settings['author'] ?? 'By <a href="//motion.page/">HypeWolf OÜ</a>'
		);
		$icon = \apply_filters('motionpage/wl/icon', $settings['icon'] ?? '');
		$hidden = \apply_filters('motionpage/wl/hidden', $settings['hidden'] ?? 0);
		$hideVersion = \apply_filters('motionpage/wl/hideVersion', $settings['hideVersion'] ?? 0);

		return \apply_filters('motionpage/wl/data', [
			'active' => (bool) $active,
			'name' => $name,
			'description' => $desc,
			'author' => $author,
			'icon' => $icon,
			'hidden' => (bool) $hidden,
			'hideVersion' => (bool) $hideVersion
		]);
	}

	/**
	 * White Label - is white label active
	 * @since 2.0.0
	 */
	public function isWhiteLabel(): bool {
		return (bool) $this->clientModeData()['active'] ?? false;
	}

	/**
	 * White Label - plugin name
	 * @since 2.0.0
	 */
	public function whiteLabelName(): string {
		return $this->clientModeData()['name'] ?: 'Motion.page';
	}

	/**
	 * White Label - plugin description
	 * @since 2.0.0
	 */
	public function whiteLabelDescription(): string {
		return $this->clientModeData()['description'] ?: "Move it like it's HOT!";
	}

	/**
	 * White Label - plugin author
	 * @since 2.0.0
	 */
	public function whiteLabelAuthor(): string {
		return $this->clientModeData()['author'] ?: 'By <a href="//motion.page/">HypeWolf OÜ</a>';
	}

	/**
	 * White Label - plugin WP dashicon
	 * @since 2.0.0
	 */
	public function whiteLabelIcon(): string {
		return $this->clientModeData()['icon'] ?: '';
	}

	/**
	 * White Label - hide plugin in WP admin menu
	 * @since 2.1.0
	 */
	public function whiteLabelHiddenInMenu(): bool {
		return $this->clientModeData()['hidden'] ?: false;
	}

	/**
	 * White Label - hide plugin in WP admin menu
	 * @since 2.1.0
	 */
	public function whiteLabelHideVersion(): bool {
		return $this->clientModeData()['hideVersion'] ?: false;
	}
}
