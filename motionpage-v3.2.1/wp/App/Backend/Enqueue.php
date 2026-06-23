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
use motionpage\Common\Utils\Vite;

/**
 * Class Enqueue
 *
 * @package motionpage\App\Backend
 * @since 2.0.0
 */
class Enqueue extends Base {
	/**
	 * @since 2.0.0
	 */
	public function init(): void {
		/**
		 * This backend class is only being instantiated in the backend as requested in the Scaffold class
		 *
		 * @see Requester::isBuilder()
		 * @see Scaffold::__construct
		 *
		 */
		\add_action('init', [$this, 'stripGSAP'], 10);
		\add_action('init', [$this, 'crossOrigin'], 10);
		\add_action('wp_loaded', [$this, 'register'], 0);
		\add_action('admin_enqueue_scripts', [$this, 'builderScripts']);
		\add_action('woocommerce_init', [$this, 'removeWooPaymentBlocks']);

		//\add_filter(
		//  'option_active_plugins',
		//  function ($plugins) {
		//      error_log(print_r($plugins, true));
		//      return $plugins;
		//  },
		//  999
		//);
	}

	public function stripGSAP(): void {
		$main_settings = motionpage()->getMainSettings();
		if (!empty($main_settings) && ($main_settings['advanced']['stripGSAP'] ?? 0) === 1) {
			\add_filter('motionpage/lib/stripGSAP', '__return_true');
		}
	}

	public function crossOrigin(): void {
		if (\is_ssl()) {
			$has_page = isset($_GET['page']);
			if (!$has_page) {
				return;
			}

			$is_mp = $_GET['page'] === MOTIONPAGE_NAME;
			$is_hidden = $_GET['page'] === $this->plugin->hiddenMenuSlug();
			$coep_filter = \apply_filters('motionpage/utils/requireCorp', false);

			if (($is_mp || $is_hidden) && (motionpage()->canUseCoep() || $coep_filter)) {
				header('Cross-Origin-Opener-Policy: same-origin');
				header('Cross-Origin-Embedder-Policy: require-corp');
			}
		}
	}

	public function register(): void {
		$js_route = MOTIONPAGE_DIR_URL . 'assets/js/';
		$gsap_route = $js_route . 'gsap/';
		$gsap_version = $this->plugin->gsapVersion();

		\wp_register_script('mp-gsap', $gsap_route . 'gsap.min.js', [], $gsap_version, true);
		\wp_register_script('mp-DrawSVG', $gsap_route . 'DrawSVGPlugin.min.js', ['mp-gsap'], $gsap_version, true);

		\wp_register_script('mp-wasm-ffmpeg', $js_route . 'ffmpeg.min.js', [], '0.12.15', true);
		\wp_script_add_data('mp-wasm-ffmpeg', 'mp-async', 'true');
	}

	private function dequeues(): void {
		// Hide WP Menus
		global $menu;
		$menu = [];

		\wp_deregister_script('react');

		\remove_action('wp_head', 'print_emoji_detection_script', 7);
		\remove_action('admin_print_scripts', 'print_emoji_detection_script');
		\remove_action('wp_print_styles', 'print_emoji_styles');
		\remove_action('admin_print_styles', 'print_emoji_styles');
		\remove_filter('the_content_feed', 'wp_staticize_emoji');
		\remove_filter('comment_text_rss', 'wp_staticize_emoji');
		\remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

		\remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');

		\add_filter('show_admin_bar', '__return_false', PHP_INT_MAX);
		\add_filter('admin_footer_text', '__return_empty_string', 11);
		\add_filter('update_footer', '__return_empty_string', 11);

		// Brindle QuickPop iframe fix
		if (defined('QP_PLUGIN_DIR')) {
			\wp_dequeue_script('qp-functions');
			\wp_dequeue_style('qp-styles');
			\wp_dequeue_style('qp-font-styles');
		}

		if (class_exists('Udb\Setup', false)) {
			\add_action(
				'admin_enqueue_scripts',
				function (): void {
					\wp_dequeue_style('udb-admin');
					\wp_dequeue_script('udb-notice-dismissal');
				},
				9999
			);
			\wp_dequeue_style('font-awesome');
			\wp_dequeue_style('font-awesome-shims');
		}

		if (class_exists('DrawAttention_Newsletter')) {
			\wp_deregister_style('da-custom-meta-box-styles');
			\wp_deregister_script('da-news-letter-js');
		}

		\add_action(
			'admin_enqueue_scripts',
			function (): void {
				$styles = ['acf-global', 'otgs-icons', 'wpml-wizard'];
				foreach ($styles as $style) {
					\wp_dequeue_style($style);
				}
			},
			99
		);

		\add_action(
			'admin_enqueue_scripts',
			function (): void {
				$styles = ['acf-global', 'otgs-icons', 'wpml-wizard'];
				foreach ($styles as $style) {
					\wp_dequeue_style($style);
				}
			},
			99
		);

		\add_filter(
			'script_loader_tag',
			function ($tag) {
				if (strpos($tag, 'sitepress-') !== false) {
					$tag = '';
				}
				if (strpos($tag, 'adminify-') !== false) {
					$tag = '';
				}
				if (strpos($tag, 'porto_') !== false) {
					$tag = '';
				}

				return $tag;
			},
			10,
			2
		);

		\add_filter(
			'style_loader_tag',
			function ($tag) {
				if (strpos($tag, 'sitepress-') !== false) {
					$tag = '';
				}
				if (strpos($tag, 'adminify-') !== false) {
					$tag = '';
				}
				if (strpos($tag, 'porto_') !== false) {
					$tag = '';
				}

				return $tag;
			},
			10,
			2
		);

		//\add_filter(
		//  'script_loader_tag',
		//  function ($tag, $handle) {
		//      $scripts = [
		//          'moxiejs',
		//          'plupload',
		//          'jquery-migrate',
		//          'svg-painter',
		//          'json2',
		//          'hoverIntent',
		//          'regenerator-runtime',
		//          'wp-polyfill',
		//          'common',
		//          'hoverintent-js',
		//          'admin-bar',
		//          'shortcode',
		//          'wp-plupload',
		//          'jquery-ui-core',
		//          'jquery-ui-mouse',
		//          'jquery-ui-sortable',
		//          'wp-console-ace-editor',
		//          'wp-console-ace-editor-lang',
		//          'wp-console',
		//          'wp-react-refresh-runtime',
		//          'wp-react-refresh-entry',
		//          'react',
		//          'react-dom',
		//          'wp-rich-text',
		//          'wp-components',
		//          'wp-compose',
		//          'wp-element',
		//          'wp-data',
		//          'wp-date',
		//          'wp-redux-routine',

		//          'wp-i18n',
		//          'media-models',
		//          'mediaelement-core',
		//          'mediaelement-migrate',
		//          'wp-mediaelement',
		//          'wp-api-request',
		//          'wp-dom-ready',
		//          'wp-a11y',
		//          'clipboard',
		//          'media-views',
		//          'media-editor',
		//          'media-audiovideo',
		//          'mce-view',
		//          'imgareaselect',
		//          'image-edit',
		//          'heartbeat',
		//          'wp-auth-check',
		//          'lodash',
		//          'wp-url',
		//          'wp-api-fetch',
		//          'moment',
		//          'wp-deprecated',
		//          'wp-dom',
		//          'wp-escape-html',
		//          'wp-is-shallow-equal',
		//          'wp-keycodes',
		//          'wp-priority-queue',
		//          'wp-primitives',
		//          'wp-warning',
		//          'dismissible-notices',
		//      ];
		//      if (in_array($handle, $scripts)) {
		//          $tag = '';
		//      }

		//      return $tag;
		//  },
		//  10,
		//  2
		//);
	}

	private function dequeuesBuilder(): void {
		\add_action(
			'admin_bar_menu',
			function ($wp_admin_bar): void {
				$wp_admin_bar->remove_node('wp-logo');
				$wp_admin_bar->remove_node('my-account');
			},
			999
		);

		$styles = [
			'admin-bar',
			'colors',
			'classic-theme-styles',
			'oxygen-vars',
			'ct-admin-style',
			'wp-admin',
			'admin-menu'
		];

		foreach ($styles as $style) {
			\wp_dequeue_style($style);
			\wp_deregister_style($style);
		}
	}

	/**
	 * Determine if database update is needed
	 * @since 2.0.0
	 */
	public function isDBUpdate(string $current_dbv): bool {
		return version_compare($current_dbv, $this->plugin->databaseVersion(), '<');
	}

	/**
	 * Enqueue the scripts related to Motion.page builder
	 * @since 2.0.0
	 */
	public function builderScripts(): void {
		\do_action('motionpage/action/builder'); // upgradeProcess is hooked here

		// Force fresh settings read after upgradeProcess() may have updated settings.
		// WordPress option cache can be stale after ALTER TABLE + immediate writes.
		\wp_cache_delete('motionpage_main', 'options');

		\add_filter('js_do_concat', '__return_false'); // Page Optimize

		$this->dequeuesBuilder();
		$this->dequeues();

		\add_filter('get_avatar', function ($avatar) {
			return str_replace('<img', '<img crossorigin="anonymous"', $avatar);
		});

		$current_dbv = motionpage()->getOption('motionpage_db_version', $this->plugin->databaseVersion());
		$main_settings = motionpage()->getMainSettings();

		$ums = (int) ini_get('upload_max_filesize');
		$pms = (int) ini_get('post_max_size');

		// WP Meteor
		\add_filter('wpmeteor_enabled', '__return_false');

		$localization = [
			//'rest_url' => \rest_url(),
			'rest_url' => \untrailingslashit(\get_rest_url(null, $this->plugin->internalRest())),
			'admin_url' => \untrailingslashit(\admin_url()),
			'home_url' => \untrailingslashit(\home_url()),
			'nonce' => \wp_create_nonce('FZBN9G29d8pnN7Z6GK'),
			'headerNonce' => \wp_create_nonce('YRSH8YUD396XJH66ZE'),
			'homepage_id' => (int) motionpage()->getOption('page_on_front', 0),
			'version' => MOTIONPAGE_VERSION,
			// Builder boot needs these to decide whether post-upgrade background
			// migration/bundle work can safely clear the admin notice.
			'migrationStatus' => (string) ($main_settings['system']['migration_status'] ?? 'complete'),
			'sdkBundleVersion' => (string) motionpage()->getOption('motionpage_sdk_bundle_version', ''),
			'dbv' => \esc_attr($current_dbv),
			'theme' => ($main_settings['system']['theme'] ?? 'dark') === 'dark' ? 'dark' : 'light',
			'b' => [
				'valid' => ($main_settings['license_status'] ?? 'invalid') === 'valid',
				'overflowX' => ($main_settings['advanced']['overflow'] ?? 0) === 1,
				'isOxygen' => class_exists('OxygenElement', false) && \post_type_exists('ct_template'),
				'dbUpdate' => $this->isDBUpdate($current_dbv),
				'hasPolylang' => class_exists('Polylang', false),
				'hasWPML' => class_exists('SitePress', false),
				'isBricks' => defined('BRICKS_DB_TEMPLATE_SLUG') && BRICKS_DB_TEMPLATE_SLUG,
				'siteUrl' => $main_settings['system']['siteUrl'] ?? \untrailingslashit(\home_url()),
			],
			'upload' => [
				'maxSizeUnit' => 'bytes',
				'maxFileCount' => (int) ini_get('max_file_uploads'),
				'maxSize' => ($pms > $ums ? $ums : $pms) * 1024 * 1024,
				//'maxFileCount' => 20,
				//'maxSize' => 1 * 1024 * 1024, // 1MB - testing
				'seqFolder' => $this->plugin->sequenceFolder(),
				'uploadsUrl' => wp_upload_dir()['baseurl']
			],
			'posts' => [
				'types' => implode(',', motionpage()->getPostTypes())
			]
		];

		\add_action(
			'wp_print_scripts',
			function (): void {
				$scriptsDataAttr = motionpage()->getScriptsDataAttr();
				echo <<<HTML
				<script async type="module" crossorigin {$scriptsDataAttr}>
					import initSwc,{transformSync} from "https://cdn.jsdelivr.net/npm/@swc/wasm-web@1.12.5/wasm.js";
					await initSwc();
					window.transformSync = transformSync;
				</script>
				HTML;
			},
			99
		);

		$entry = Vite::useVite('main.tsx', ['wp-api', 'mp-DrawSVG', 'mp-wasm-ffmpeg']);
		\wp_localize_script($entry, MOTIONPAGE_NAME, $localization);

		\do_action('motionpage/action/builder/end');
	}

	/**
	 * Prevent 'wc-payment-method-cod' error due to 'react'
	 */
	public function removeWooPaymentBlocks(): bool {
		global $wp_filter;
		if (empty($wp_filter['wp_print_scripts']->callbacks[1])) {
			return false;
		}
		foreach ($wp_filter['wp_print_scripts']->callbacks[1] as $hook) {
			if (
				is_array($hook['function']) &&
				$hook['function'][1] === 'verify_payment_methods_dependencies' &&
				is_object($hook['function'][0]) &&
				get_class($hook['function'][0]) === 'Automattic\\WooCommerce\\Blocks\\Payments\\Api'
			) {
				$wp_filter['wp_print_scripts']->remove_filter('wp_print_scripts', $hook['function'], 1);
				return true;
			}
		}
		return false;
	}
}
