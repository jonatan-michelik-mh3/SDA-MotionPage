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

namespace motionpage\Common\Traits;

use motionpage\Common\Utils\Errors;
use motionpage\Config\Plugin;

/**
 * The requester trait to determine what we request; used to determine
 * which classes we instantiate in the Scaffold class
 *
 * @see Scaffold
 *
 * @package motionpage\Common\Traits
 * @since 2.0.0
 */
trait Requester {
	/**
	 * Get the plugin data
	 * @since 2.0.0
	 */
	public function getPlugin(): Plugin {
		return Plugin::init();
	}

	/**
	 * What type of request is this?
	 * @param string $type admin, amp or frontend.
	 * @since 2.0.0
	 */
	public function request(string $type): bool {
		switch ($type) {
			case 'installing_wp':
				return $this->isInstallingWP();
			case 'frontend':
				return $this->isFrontend();
			case 'backend':
				return $this->isAdminBackend();
			case 'is_builder':
				return $this->isBuilder();
			case 'rest_builder':
				return $this->isRestBuilder();
			case 'rest':
				return $this->isRest();
			case 'other_builders':
				return $this->isOtherBuilders();
			case 'plugin_page':
				return $this->isPluginPage();
			case 'updater_core':
				return $this->isUpdateCore();
			case 'cron':
				return $this->isCron();
			default:
				Errors::wpDie(
					sprintf(
						/* translators: %s: request function */
						\__('Unknown request type: %s', $this->getPlugin()->textDomain()),
						\esc_html($type)
					),
					\__('Classes are not being correctly requested', $this->getPlugin()->textDomain()),
					__FILE__
				);
				return false;
		}
	}

	/**
	 * Is installing WP
	 * @since 2.0.0
	 */
	public function isInstallingWP(): bool {
		return defined('WP_INSTALLING');
	}

	/**
	 * Is frontend
	 * @since 2.0.0
	 */
	public function isFrontend(): bool {
		return !$this->isAdminBackend() && !$this->isCron() && !$this->isRest() && !$this->isBuilder();
	}

	/**
	 * Is admin
	 * @since 2.0.0
	 */
	public function isAdminBackend(): bool {
		return \is_user_logged_in() && \is_admin();
	}

	/**
	 * Is cron
	 * @since 2.0.0
	 */
	public function isCron(): bool {
		return (function_exists('wp_doing_cron') && \wp_doing_cron()) || defined('DOING_CRON');
	}

	/**
	 * Is rest
	 * @since 2.0.0
	 */
	public function isRest(): bool {
		return strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-json') !== false || defined('REST_REQUEST');
	}

	/**
	 * Is Motion.page Builder
	 * @since 2.0.0
	 */
	public function isBuilder(): bool {
		$url_parse = \wp_parse_url(\admin_url('admin.php'));
		$admin_page = strpos($url_parse['path'] ?? '', '/wp-admin/admin.php') !== false;
		$builder = strpos($_SERVER['QUERY_STRING'] ?? '', 'page=motionpage') !== false;
		$client_mode =
			strpos($_SERVER['QUERY_STRING'] ?? '', 'page=' . $this->getPlugin()->hiddenMenuSlug()) !== false;
		$is_builder = $admin_page && ($builder || $client_mode);
		return $this->isAdminBackend() && $is_builder;
	}

	/**
	 * Is Motion.page Rest API
	 * @since 2.0.0
	 */
	public function isRestBuilder(): bool {
		$nonce = \wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest');
		$mp_header = \wp_verify_nonce($_SERVER['HTTP_X_WP_MP'] ?? '', 'YRSH8YUD396XJH66ZE');
		return $this->isRest() && $mp_header && !$this->isFrontend() && defined('ABSPATH') && $nonce;
	}

	/**
	 * Is Oxygen / Bricks / Elementor / Gutenberg
	 * @since 2.0.0
	 */
	public function isOtherBuilders(): bool {
		$oxygen = strpos($_SERVER['QUERY_STRING'] ?? '', 'ct_builder=true') !== false;
		$bricks = strpos($_SERVER['QUERY_STRING'] ?? '', 'bricks=run') !== false;
		$elementor = strpos($_SERVER['QUERY_STRING'] ?? '', 'action=elementor') !== false;
		global $pagenow;
		$posts = !empty($pagenow) && $pagenow === 'post.php';
		return $oxygen || $bricks || $posts || $elementor;
	}

	/**
	 * Is dash plugins.php page?
	 * @since 2.0.0
	 */
	public function isPluginsPage(): bool {
		global $pagenow;
		return $this->isAdminBackend() && $pagenow === 'plugins.php';
	}

	/**
	 * Is dash updater-core page?
	 * @since 2.0.0
	 */
	public function isUpdateCore(): bool {
		global $pagenow;
		return $this->isAdminBackend() && $pagenow === 'update-core.php';
	}
}
