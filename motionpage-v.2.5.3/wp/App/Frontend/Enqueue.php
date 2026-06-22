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

namespace motionpage\App\Frontend;

use motionpage\Common\Abstracts\Base;
use motionpage\Common\Utils\Vite;
use motionpage\App\Frontend\Functions;
use motionpage\App\Frontend\Utils;

/**
 * Class Enqueue
 *
 * @package motionpage\App\Frontend
 * @since 2.0.0
 */
class Enqueue extends Base {
	private bool $isDev;

	private Functions $functions;

	private Utils $utils;

	// Check if we are in the iframe
	private bool $iframeCtx = false;

	/**
	 * Check if overflow-x is set inside advanced => motionpage
	 * @since   1.0.0
	 */
	private bool $is_overflow = false;

	/**
	 * Check the value of debugMode
	 * @since   1.0.0
	 */
	private bool $is_debug_mode = false;

	/**
	 * Check the value of hashAnchorLinks
	 * @since   2.1.0
	 */
	private bool $hashAnchorLinks = false;

	/**
	 * Save fetched animations as variable
	 * @since   1.4.0
	 */
	private array $scripts_holder = [];

	/**
	 * Check if we need ScrollSmoother
	 * @since   1.5.0
	 */
	private bool $is_scrollSmoother = false;

	/**
	 * ScrollSmoother code
	 * @since   1.5.0
	 */
	private string $scrollSmoother = '';

	/**
	 * ScrollSmoother FixedSticky
	 * @since   2.0.0
	 */
	private bool $fixedSticky = false;

	/**
	 * Check if we need Lenis
	 * @since   2.3.0
	 */
	private bool $is_lenis = false;

	/**
	 * Lenis code
	 * @since   2.3.0
	 */
	private string $lenis = '';

	/**
	 * Prevent scripts from loading in other builders
	 * @since   1.4.0
	 */
	private bool $not_in_builders = true;

	/**
	 * Validated and used data from motionpage()->getMainSettings()
	 * @since   2.1.7
	 */
	private array $settings = [];

	/**
	 * Disable ScrollSmoother for Admin
	 * @since   2.1.0
	 */
	private bool $dssa = false;

	/**
	 * Breakpoints
	 * @since   2.1.6.7
	 */
	private array $breakpoints = [];

	/**
	 * Group all apply_filters
	 * @since   2.1.6.7
	 */
	private array $filters = [];

	/**
	 * Last mp-script id
	 * @since   2.1.6.7
	 * @var array
	 */
	private string $lastScriptId = '';

	/**
	 * Remove other instances of GSAP on the page
	 * @since   2.1.8
	 */
	private bool $strip_gsap = false;

	/**
	 * @since 2.0.0
	 */
	public function init(): void {
		/**
		 * This frontend class is only being instantiated in the frontend as requested in the Scaffold class
		 *
		 * @see Requester::isFrontend()
		 * @see Scaffold::__construct
		 */
		$this->isDev = $this->plugin->isDev();

		$this->functions = new Functions();
		$this->utils = new Utils();

		// Check for builders early before any output
		if (\is_customize_preview() || $this->utils->isBuilders()) {
			$this->not_in_builders = false;
		}

		\add_action('init', [$this, 'iframeDefined'], 0);
		\add_action('init', [$this, 'settings'], 10);
		\add_action('send_headers', [$this, 'sendHeaders'], 10);
		\add_action('wp', [$this, 'builderCheck'], 0);
		\add_action('wp', [$this, 'defineFilters'], 1);
		\add_action('wp_head', [$this, 'head'], 0);
		\add_action('wp_enqueue_scripts', [$this, 'register'], 0);
		\add_action('wp_enqueue_scripts', [$this, 'frontend'], 999);
	}

	/**
	 * Re-check builder status at wp hook when builder plugins are fully initialized
	 * This catches builders that weren't detectable during early init()
	 * @since 2.5.3
	 */
	public function builderCheck(): void {
		// Skip if already detected as builder
		if (!$this->not_in_builders) {
			return;
		}

		// Re-check now that builder plugins are fully loaded
		if ($this->utils->isBuilders()) {
			$this->not_in_builders = false;
		}
	}

	/**
	 * Check if we are in the iframe
	 * @since   1.0.0
	 */
	public function iframeDefined(): void {
		if (!empty($_GET['motionpage_iframe'] ?? '') && \current_user_can('manage_options')) {
			$this->iframeCtx = true;
			\do_action('motionpage/action/iframe/defined');
		}
	}

	public function settings(): void {
		$main_settings = motionpage()->getMainSettings();
		if (!empty($main_settings)) {
			$hashFix = ($main_settings['scrollSmoother']['isOpen'] ?? 0) === 1;
			$this->is_overflow = ($main_settings['advanced']['overflow'] ?? 0) === 1;
			$this->is_debug_mode = ($main_settings['advanced']['debugMode'] ?? 0) === 1;
			$this->hashAnchorLinks = !$hashFix && ($main_settings['advanced']['hashAnchorLinks'] ?? 0) === 1;

			// is GSAP from CDN
			$this->settings['cdn'] = ($main_settings['cdn'] ?? 0) === 1;
			$this->settings['system'] = [
				// Polylang & WPML translation sync
				'polylangSync' => ($main_settings['system']['polylang'] ?? 0) === 1,
				'wpmlSync' => ($main_settings['system']['wpml'] ?? 0) === 1
			];

			$this->dssa =
				!$this->iframeCtx &&
				\current_user_can('manage_options') &&
				($main_settings['advanced']['dssa'] ?? 0) === 1;

			if (
				!$this->dssa &&
				($main_settings['scrollSmoother']['isOpen'] ?? 0) === 1 &&
				!empty($main_settings['scrollSmoother']['code'] ?? '')
			) {
				$this->is_scrollSmoother = true;
				$this->scrollSmoother = htmlspecialchars_decode($main_settings['scrollSmoother']['code'], ENT_QUOTES);
				$this->fixedSticky = ($main_settings['scrollSmoother']['fixedSticky'] ?? 0) === 1;
			}

			// Configure Lenis smooth scrolling
			// Page-specific exclusions are handled later during enqueue to ensure WordPress query is loaded
			if (
				($main_settings['lenis']['isOpen'] ?? 0) === 1 &&
				!empty($main_settings['lenis']['code'] ?? '')
			) {
				$this->is_lenis = true;
				$this->lenis = htmlspecialchars_decode($main_settings['lenis']['code'], ENT_QUOTES);
			}

			$this->breakpoints = $main_settings['breakpoints'] ?? [];
			$this->strip_gsap = ($main_settings['advanced']['stripGSAP'] ?? 0) === 1;
		}
	}

	/**
	 * Check if the current page should be excluded from Lenis smooth scrolling
	 * 
	 * This method is called during the enqueue process when WordPress query is fully loaded,
	 * ensuring accurate page detection for exclusion logic.
	 * 
	 * @return bool True if current page should be excluded from Lenis, false otherwise
	 * @since 2.3.0
	 */
	private function isCurrentPageExcludedFromLenis(): bool {
		$main_settings = motionpage()->getMainSettings();
		$excludedPages = $main_settings['lenis']['excludedPages'] ?? '';
		
		if (empty($excludedPages)) {
			return false; // No exclusions configured
		}
		
		// Get current post ID using multiple detection methods for reliability
		$currentPostId = 0;
		
		// Method 1: Check if we're on the homepage first
		if (\is_front_page()) {
			$currentPostId = (int) motionpage()->getOption('page_on_front', 0);
		}
		// Method 2: Try to get the current post ID from the main query
		elseif (\is_singular()) {
			$currentPostId = \get_queried_object_id();
		}
		// Method 3: Fallback to get_the_ID() for edge cases
		elseif (\get_the_ID()) {
			$currentPostId = \get_the_ID();
		}
		// Method 4: Use utils method as final fallback
		else {
			$currentPostId = $this->utils->getPostID();
		}
		
		// Parse excluded page IDs from comma-separated string
		$excludedIds = array_map('intval', explode(',', $excludedPages));
		$excludedIds = array_filter($excludedIds); // Remove empty values
		
		// Check if current page is in the exclusion list
		return in_array($currentPostId, $excludedIds);
	}

	public function sendHeaders(): void {
		if (\is_ssl() && $this->iframeCtx) {
			$coep_filter = \apply_filters('motionpage/utils/requireCorp', false);
			if (motionpage()->canUseCoep()) {
				header('Cross-Origin-Embedder-Policy: credentialless');
			} elseif ($coep_filter) {
				header('Cross-Origin-Embedder-Policy: require-corp');
			}
		}
	}

	public function defineFilters(): void {
		$POST_ID = $this->utils->getPostID();

		$legacy_b_scr = \apply_filters('motionpage_before_scripts', '', $POST_ID) ?? '';
		$legacy_a_scr = \apply_filters('motionpage_after_scripts', '', $POST_ID) ?? '';

		$pu = 'motionpage/utils/';
		$ps = 'motionpage/scripts/';
		$this->filters = [
			'utils' => [
				'gsapDeferAsync' => \apply_filters($pu . 'gsapDeferAsync', '', $POST_ID),
				'fouc' => \apply_filters($pu . 'fouc', false, $POST_ID),
				'stopper' => \apply_filters($pu . 'stopper', false, $POST_ID),
				'polylang' => \apply_filters($pu . 'polylang', $this->settings['system']['polylangSync'], $POST_ID),
				'wpml' => \apply_filters($pu . 'wpml', $this->settings['system']['wpmlSync'], $POST_ID),
				'disableMobile' => \apply_filters($pu . 'disableMobile', false, $POST_ID),
				'bypassReduced' => \apply_filters($pu . 'bypassReduced', false, $POST_ID),
				'lazyloaded' => \apply_filters($pu . 'lazyloaded', false, $POST_ID),
				'historyExit' => \apply_filters($pu . 'historyExit', true, $POST_ID),
				'cdnRoute' => \apply_filters($pu . 'cdnRoute', '//cdn.jsdelivr.net/npm/gsap@')
			],
			'scripts' => [
				'bypass' => \apply_filters($ps . 'bypass', [], $POST_ID),
				'before/reduced' => \apply_filters($ps . 'before/reduced', '', $POST_ID),
				'after/reduced' => \apply_filters($ps . 'after/reduced', '', $POST_ID),
				'before' => \apply_filters($ps . 'before', $legacy_b_scr, $POST_ID),
				'after' => \apply_filters($ps . 'after', $legacy_a_scr, $POST_ID)
			]
		];
	}

	private function getFilterValue(string $namespace, string $filterName, $default) {
		$filterValue = $this->filters[$namespace][$filterName];
		if (!$this->isDev) {
			return $filterValue ?? $default;
		}

		if (isset($filterValue)) {
			return $filterValue ?? $default;
		}

		throw new \Exception(
			'Motion.page ' . 'motionpage' . '\\' . $namespace . '\\' . $filterName . ' filter not found!'
		);
	}


	/**
	 * @since   1.0.0
	 */
	public function head(): void {
		$prevent_load = $this->functions->preventLoad($this->getFilterValue('utils', 'stopper', false));

		$version = $this->plugin->version();

		if (!$prevent_load) {
			if ($this->not_in_builders) {
				$scriptsDataAttr = motionpage()->getScriptsDataAttr();
				echo <<<HTML
				<script {$scriptsDataAttr}>window.MOTIONPAGE_FRONT={version:"{$version}"}</script>
				HTML;

				// Add Lenis CSS if Lenis is enabled
				if ($this->is_lenis) {
					echo <<<HTML
					<style>
					html.lenis, html.lenis body {
						height: auto;
					}
					.lenis.lenis-smooth {
						scroll-behavior: auto !important;
					}
					.lenis.lenis-smooth [data-lenis-prevent] {
						overscroll-behavior: contain;
					}
					.lenis.lenis-stopped {
						overflow: hidden;
					}
					.lenis.lenis-smooth iframe {
						pointer-events: none;
					}
					</style>
					HTML;
				}
			} else {
				// In builders: Clean up any Lenis classes and destroy instance
				echo <<<HTML
				<script>
				// Remove Lenis classes from HTML element when in builders
				document.documentElement.className = document.documentElement.className.replace(/lenis(-\w+)?/g, '').trim();
				// Destroy any existing Lenis instance
				if (window._mp_lenis) {
					try {
						window._mp_lenis.destroy();
					} catch (e) {}
					delete window._mp_lenis;
				}
				if (window._mp_lenis_cleanup) {
					try {
						window._mp_lenis_cleanup();
					} catch (e) {}
					delete window._mp_lenis_cleanup;
				}
				if (window._mp_lenis_raf) {
					cancelAnimationFrame(window._mp_lenis_raf);
					delete window._mp_lenis_raf;
				}
				</script>
				HTML;
			}

			$timelines = $this->functions->getAllScripts($this->getFilterValue('scripts', 'bypass', []));
			$this->scripts_holder = $timelines;

			$has_live = false;

			if (!empty($timelines)) {
				foreach ($timelines as $timeline) {
					$is_live = $this->functions->getIsLive($timeline, $this->utils->getPostID());

					if ($is_live) {
						$has_live = true;
						if ($this->strip_gsap) {
							\add_filter('motionpage/lib/stripGSAP', '__return_true');
						}
						break;
					}
				}
			}

			/**
			 * Disable this feature by adding a motionpage/utils/fouc filter and setting it to __return_true
			 */
			if ($has_live && !$this->iframeCtx && !$this->getFilterValue('utils', 'fouc', false)) {
				$scriptsDataAttr = motionpage()->getScriptsDataAttr();
				echo <<<HTML
				<style>body{visibility:hidden;}</style>
				<script {$scriptsDataAttr}>document.addEventListener("DOMContentLoaded",()=>(document.body.style.visibility="inherit"));</script>
				<noscript><style>body{visibility:inherit;}</style></noscript>
				HTML;
			}
		}
	}

	public function register(): void {
		// Builder check already done in init()

		$js_route = MOTIONPAGE_DIR_URL . 'assets/js/';
		$gsap_route = $js_route . 'gsap/';
		$gsap_route_cdn = $js_route . 'gsap/';
		$version = $this->plugin->version();
		$gsap_version = $this->plugin->gsapVersion();

		if ($this->settings['cdn']) {
			$jsd_route = $this->getFilterValue('utils', 'cdnRoute', '//cdn.jsdelivr.net/npm/gsap@');
			$jsd_route = $jsd_route . $gsap_version . '/dist/';
		}

		\wp_register_script('mp-ISWorker', $js_route . 'ISWorker.js', [], $this->plugin->version(), false);
		\wp_script_add_data('mp-ISWorker', 'mp-module', true);

		\wp_register_script(
			'mp-ImageSequence',
			$js_route . 'ImageSequence.js',
			['mp-ISWorker', 'mp-gsap'],
			$version,
			true
		);

		\wp_script_add_data('mp-ImageSequence', 'mp-module', true);

		\wp_register_script('mp-Cursor', $js_route . 'Cursor.js', ['mp-gsap'], $version, true);

		\wp_script_add_data('mp-Cursor', 'mp-module', true);

		\wp_register_script('mp-MouseMove', $js_route . 'MouseMove.js', ['mp-gsap'], $version, true);

		\wp_register_script('mp-gsap', $gsap_route_cdn . 'gsap.min.js', [], $gsap_version, true);
		$gsap_defer_async = $this->getFilterValue('utils', 'gsapDeferAsync', '');
		if ($gsap_defer_async === 'defer' || $gsap_defer_async === 'async') {
			\wp_script_add_data('mp-gsap', 'mp-' . $gsap_defer_async, true);
		}

		\wp_register_script('mp-ClassName', $gsap_route . 'ClassName.min.js', ['mp-gsap'], $gsap_version, true);

		\wp_register_script('mp-Flip', $gsap_route_cdn . 'Flip.min.js', ['mp-gsap'], $gsap_version, true);

		\wp_register_script(
			'mp-ScrollTrigger',
			$gsap_route_cdn . 'ScrollTrigger.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);

		\wp_register_script('mp-Observer', $gsap_route_cdn . 'Observer.min.js', ['mp-gsap'], $gsap_version, true);
		\wp_register_script(
			'mp-SplitText',
			$gsap_route_cdn . 'SplitText.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);
		\wp_register_script(
			'mp-DrawSVG',
			$gsap_route_cdn . 'DrawSVGPlugin.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);
		\wp_register_script(
			'mp-ScrollToPlugin',
			$gsap_route_cdn . 'ScrollToPlugin.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-CSSRulePlugin',
			$gsap_route_cdn . 'CSSRulePlugin.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-CustomEase',
			$gsap_route_cdn . 'CustomEase.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-ScrollSmoother',
			$gsap_route_cdn . 'ScrollSmoother.min.js',
			['mp-ScrollTrigger'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-Lenis',
			$js_route . 'lenis.min.js',
			[],
			'1.3.4',
			true
		);

		\wp_register_script(
			'mp-MotionPathPlugin',
			$gsap_route_cdn . 'MotionPathPlugin.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-MotionPathHelper',
			$gsap_route_cdn . 'MotionPathHelper.min.js',
			['mp-MotionPathPlugin'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-ScrambleText',
			$gsap_route_cdn . 'ScrambleTextPlugin.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-MorphSVG',
			$gsap_route_cdn . 'MorphSVGPlugin.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-GSDevTools',
			$gsap_route_cdn . 'GSDevTools.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);

		\wp_register_script(
			'mp-Flip',
			$gsap_route_cdn . 'Flip.min.js',
			['mp-gsap'],
			$gsap_version,
			true
		);
	}

	/**
	 * @since 2.1.7
	 * @param string $script_id
	 */
	public function mpEnqueueScript($script_id): void {
		\wp_enqueue_script('mp-' . $script_id);
		$this->lastScriptId = 'mp-' . $script_id;
	}

	/**
	 * @since   1.0.0
	 */
	public function frontend(): void {
		\do_action('motionpage/action/front');

		$prevent_load = $this->functions->preventLoad($this->getFilterValue('utils', 'stopper', false));

		$allow_disable_mobile = $this->getFilterValue('utils', 'disableMobile', false);

		if ($allow_disable_mobile && \wp_is_mobile()) {
			$prevent_load = true;
		}

		$pageExit = false;
		$has_splitText = false;
		$has_gensel = false;
		$polylang_sync = $this->getFilterValue('utils', 'polylang', $this->settings['system']['polylangSync']);
		$wpml_sync = $this->getFilterValue('utils', 'wpml', $this->settings['system']['wpmlSync']);

		if (!$prevent_load && $this->not_in_builders) {
			// TODO : Replace this with custom page
			if (preg_match('/undefined\b/', \home_url($_SERVER['REQUEST_URII'] ?? ''))) {
				\wp_safe_redirect(\home_url(), 307);
				exit();
			}

			// TODO : ScrollSmoother wihtout timelines
			if (!empty($this->scripts_holder)) {
				$POST_ID = $this->utils->getPostID();

				$bypass_reduced_motion = '';
				$script_prefix = '';
				$script = '';

				foreach ($this->scripts_holder as $script_holder) {
					$show_on_front = $this->functions->getIsLive($script_holder, $POST_ID);

					if (
						$polylang_sync &&
						!$show_on_front &&
						(function_exists('pll_current_language') && function_exists('pll_languages_list'))
					) {
						$current_language = \pll_current_language('slug');
						$languages = \pll_languages_list();
						$languages = array_diff($languages, [$current_language]);
						if (function_exists('pll_get_post')) {
							foreach ($languages as $language) {
								$translated = \pll_get_post($POST_ID, $language);
								if ($translated) {
									$show_on_front = $this->functions->getIsLive($script_holder, $translated);
									if ($show_on_front) {
										break;
									}
								}
							}
						}
					}

					if (
						$wpml_sync &&
						!$show_on_front &&
						function_exists('icl_object_id') &&
						function_exists('icl_get_languages')
					) {
						$current_language = \apply_filters('wpml_current_language', null);
						$languages = \apply_filters('wpml_active_languages', null, 'orderby=id&order=desc');

						foreach ($languages as $language) {
							if ($language['language_code'] == $current_language) {
								continue;
							}

							$translated = \apply_filters(
								'wpml_object_id',
								$POST_ID,
								'post',
								false,
								$language['language_code']
							);

							if ($translated) {
								$show_on_front = $this->functions->getIsLive($script_holder, $translated);
								if ($show_on_front) {
									break;
								}
							}
						}
					}

					//\wp_enqueue_script('mp-Flip');
					//\wp_enqueue_script('mp-Observer');

					if ($show_on_front) {
						if (!wp_script_is('mp-gsap', 'enqueued')) {
							$this->mpEnqueueScript('gsap');

							//\wp_localize_script('mp-gsap', 'HYPEWOLF', [
							//	'v' => $this->plugin->version()
							//]);

							if ($this->is_overflow) {
								$script_prefix .= 'document.body.style.overflowX="hidden";';
							}

							if (!$this->iframeCtx) {
								$script_prefix .=
									'gsap.registerPlugin({name:"transition",init(t,e,r){return this.target=t,this.tween=r,this.reverting=gsap.core.reverting||function(){},!!t.style},render(t,{target:e,tween:r,reverting:i}){e.style.transition=(1===r.progress()||!r._time&&i())&&"isFromStart"!==r.data?"":"unset"}});';

								$script_prefix .= 'gsap.defaults({duration:1,transition:"unset"});';
							}

							$is_debug_mode = $this->is_debug_mode ? 'true' : 'false';
							$script_prefix .= 'gsap.config({nullTargetWarn:' . $is_debug_mode . '});';
						}

						if (property_exists($script_holder, 'plugins') && $script_holder->plugins !== null) {
							if (
								!\wp_script_is('mp-SplitText', 'enqueued') &&
								strpos($script_holder->plugins, 'SplitText') !== false
							) {
								$this->mpEnqueueScript('SplitText');
								$has_splitText = true;
							}

							if (
								!$has_gensel &&
								$has_splitText &&
								!$this->iframeCtx &&
								strpos($script_holder->plugins, '_mp_GENSEL') !== false
							) {
								\wp_add_inline_script(
									'mp-SplitText',
									'function _mp_GENSEL(e){const n=[];for(;e.tagName;){let t=0;if(e.parentNode){const n=e.parentNode.children;for(;t<n.length&&n[t]!==e;)t++}n.unshift(e.nodeName+(t>0?`:nth-child(${t+1})`:"")),e=e.parentNode}return n.join(" > ")}',
									'before'
								);
								$has_gensel = true;
							}

							$plugin_names = [
								'MouseMove',
								'CustomEase',
								'ImageSequence',
								'DrawSVG',
								'ScrambleText',
								'MorphSVG',
								'Cursor',
								'ClassName',
								'Flip'
							];

							foreach ($plugin_names as $plugin_name) {
								if (
									!\wp_script_is('mp-' . $plugin_name, 'enqueued') &&
									strpos($script_holder->plugins, $plugin_name) !== false
								) {
									$this->mpEnqueueScript($plugin_name);

									if ($plugin_name === 'ImageSequence') {
										$medium = $this->breakpoints['tablets']['value'] ?? 1024;
										$small = $this->breakpoints['phones']['value'] ?? 768;
										$scriptsDataAttr = motionpage()->getScriptsDataAttr();
										echo <<<HTML
										<script {$scriptsDataAttr}>window.MOTIONPAGE_FRONT.bp= {s:{$small},m:{$medium}};</script>
										HTML;
									}
								}
							}

							if (!$pageExit && strpos($script_holder->plugins, 'pageExit') !== false) {
								$pageExit = true;
							}
						}

						if (strpos($script_holder->plugins, 'BypassReducedMotion') !== false) {
							$bypass_reduced_motion .= $script_holder->script_value;
							continue;
						}

						$script .= $script_holder->script_value;
					}
				}

				$can_load = $script || $bypass_reduced_motion || $this->is_scrollSmoother || $this->is_lenis || $this->hashAnchorLinks;

				if (($can_load && $pageExit) || $this->iframeCtx) {
					$scriptsDataAttr = motionpage()->getScriptsDataAttr();
					echo <<<HTML
					<script {$scriptsDataAttr}>
						window.MOTIONPAGE_FRONT.attachExitClick= function (el,uid,SPD=false) {
							if (!el | !uid) return;
							if (top.mp_iframe) return;
							el.addEventListener("click", function (e) {
								if(!SPD) e.preventDefault();
								window[uid].play().call(() => (location.href = void 0 !== this ? this : location.href));
							});
						}
					</script>
					HTML;
				}

				if ($can_load) {
					$reduce_motion = '';

					// ? REDUCED MOTION
					if ($script) {
						$remove_r_m = $this->getFilterValue('utils', 'bypassReduced', false);

						if (!$remove_r_m) {
							$reduce_motion .= 'if(!matchMedia("(prefers-reduced-motion: reduce)").matches){';
						}

						$reduce_motion .= $this->getFilterValue('scripts', 'before/reduced', '');
						$reduce_motion .= $script;
						$reduce_motion .= $this->getFilterValue('scripts', 'after/reduced', '');
						if (!$remove_r_m) {
							$reduce_motion .= '}';
						}
					}

					$dcl_wrapper = 'document.addEventListener("DOMContentLoaded",()=>{';
					$dcl_wrapper .= $script_prefix;
					$dcl_wrapper .= $this->getFilterValue('scripts', 'before', '');

					$scrolltrigger_loaded = false;

					if ($this->is_scrollSmoother) {
						$scrolltrigger_loaded = true;
						$this->mpEnqueueScript('ScrollTrigger');
						$this->mpEnqueueScript('ScrollSmoother');
						if ($this->fixedSticky) {
							$is_bricks = function_exists('bricks_is_builder_main') && \bricks_is_builder_main();
							$dcl_wrapper .= $is_bricks
								? ''
								: 'Array.from(document.getElementsByTagName("*")).filter((e=>["fixed","sticky"].includes(getComputedStyle(e).position))).reverse().forEach((p=>{document.querySelector("body").prepend(p)}));';
						}

						$dcl_wrapper .= $this->scrollSmoother;
						$dcl_wrapper .=
							'addEventListener("load",(()=>{if("ScrollSmoother"in _$W&&"function"==typeof ScrollSmoother.get){const o=ScrollSmoother.get();o&&"function"==typeof o.refresh&&o.refresh(!0)}}));';
					}

					// Load Lenis smooth scrolling if enabled and page is not excluded
					if ($this->is_lenis && !$this->isCurrentPageExcludedFromLenis()) {
						$this->mpEnqueueScript('Lenis');
						$dcl_wrapper .= $this->lenis;
					}

					$dcl_wrapper .= $reduce_motion;
					$dcl_wrapper .= $bypass_reduced_motion;
					$dcl_wrapper .= $this->getFilterValue('scripts', 'after', '');

					if (strpos($dcl_wrapper, 'motionPath') !== false) {
						$this->mpEnqueueScript('MotionPathPlugin');
					}

					// removed s as stripos is slower than strpos
					$is_scrollTrigger = strpos($dcl_wrapper, 'crollTrigger') !== false;
					if ($is_scrollTrigger && !$scrolltrigger_loaded) {
						$scrolltrigger_loaded = true;
						$this->mpEnqueueScript('ScrollTrigger');
					}

					if ($scrolltrigger_loaded) {
						$dcl_wrapper .=
							'_$W._mp_refresher=(t=0)=>{ScrollTrigger&&setTimeout((()=>{ScrollTrigger.sort(),ScrollTrigger.getAll().forEach((r)=>r.refresh())}),t)},addEventListener("load",(()=>_mp_refresher(92)));';

						if ($this->getFilterValue('utils', 'lazyloaded', false)) {
							$dcl_wrapper .=
								'document.addEventListener("lazyloaded",()=>ScrollTrigger&&ScrollTrigger.refresh(true));';
						}
					}

					if ($this->is_debug_mode && !$this->iframeCtx) {
						// TODO add FRONT debug variable and also add this to IS
						$dcl_wrapper .=
							'console.log("%c Debug Mode", "font-weight: bold; font-size: 50px;color: red; text-shadow: 3px 3px 0 rgb(217,31,38) , 6px 6px 0 rgb(226,91,14) , 9px 9px 0 rgb(245,221,8) , 12px 12px 0 rgb(5,148,68) , 15px 15px 0 rgb(2,135,206) , 18px 18px 0 rgb(4,77,145) , 21px 21px 0 rgb(42,21,113)");';
						$dcl_wrapper .= 'console.log(gsap.getTweensOf("*"));';
						if ($is_scrollTrigger) {
							$dcl_wrapper .= 'if(ScrollTrigger)console.log(ScrollTrigger?.getAll());';
						}

						$this->mpEnqueueScript('GSDevTools');
						$dcl_wrapper .= 'GSDevTools.create();';
					}

					$dcl_wrapper .= '});';

					// TODO : WIP COOKIE
					//function _mp_setCookie(n, e = 30, p = "/") {
					//  const d = new Date();
					//  d.setTime(d.getTime() + e * 86400000);
					//  document.cookie = `${n}=1;expires=${d.toUTCString()};path=${p}`;
					//}

					//function _mp_setCookie(e,t=30,o="/"){const i=new Date;i.setTime(i.getTime()+864e5*t),document.cookie=`${e}=1;expires=${i.toUTCString()};path=${o}`}

					//addEventListener("pageshow", function (event) {
					//  const historyTraversal =
					//      event.persisted ||
					//      (typeof performance !== "undefined" && performance.navigation.type === 2) ||
					//      performance.getEntriesByType("navigation")[0].type === "back_forward";
					//  if (historyTraversal) location.reload();
					//});

					$exit_fix = $this->getFilterValue('utils', 'historyExit', true);
					if ($pageExit && $exit_fix) {
						$history_exit_fix =
							'addEventListener("pageshow",(function(e){(e.persisted||void 0!==performance&&2===performance.navigation.type||"back_forward"===performance.getEntriesByType("navigation")[0].type)&&location.reload()}));';
						$dcl_wrapper = $history_exit_fix . $dcl_wrapper;
					}

					if ($this->hashAnchorLinks) {
						$this->mpEnqueueScript('ScrollToPlugin');
						$dcl_wrapper .=
							'document.addEventListener("DOMContentLoaded",(()=>{location.hash&&gsap.to(window, {duration:1,scrollTo:location.hash})}));';
					}

					if (
						!\wp_script_is('mp-ScrollToPlugin', 'enqueued') &&
						strpos($script_holder->plugins, 'ScrollToPlugin') !== false
					) {
						$this->mpEnqueueScript('ScrollToPlugin');
					}

					if ($this->dssa) {
						$dcl_wrapper .=
							'console.warn("%cScrollSmoother is disabled for admin users!", "font: 11px Inconsolata, monospace;color: #41FF00;");';
					}

					\wp_add_inline_script($this->lastScriptId, 'window._$W = window;' . $dcl_wrapper, 'after');
					\do_action('motionpage/action/front/end');
				}
			}

			// Handle ScrollSmoother and Lenis without requiring animations
			if ($this->is_scrollSmoother || $this->is_lenis) {
				$dcl_wrapper = 'document.addEventListener("DOMContentLoaded",()=>{';
				$script_prefix = '';
				
				// Add basic GSAP setup if not already done
				if (!wp_script_is('mp-gsap', 'enqueued')) {
					$this->mpEnqueueScript('gsap');
					
					if ($this->is_overflow) {
						$script_prefix .= 'document.body.style.overflowX="hidden";';
					}
					
					if (!$this->iframeCtx) {
						$script_prefix .=
							'gsap.registerPlugin({name:"transition",init(t,e,r){return this.target=t,this.tween=r,this.reverting=gsap.core.reverting||function(){},!!t.style},render(t,{target:e,tween:r,reverting:i}){e.style.transition=(1===r.progress()||!r._time&&i())&&"isFromStart"!==r.data?"":"unset"}});';
						
						$script_prefix .= 'gsap.defaults({duration:1,transition:"unset"});';
					}
					
					$is_debug_mode = $this->is_debug_mode ? 'true' : 'false';
					$script_prefix .= 'gsap.config({nullTargetWarn:' . $is_debug_mode . '});';
				}
				
				$dcl_wrapper .= $script_prefix;
				$dcl_wrapper .= $this->getFilterValue('scripts', 'before', '');
				
				$scrolltrigger_loaded = false;
				
				if ($this->is_scrollSmoother) {
					$scrolltrigger_loaded = true;
					$this->mpEnqueueScript('ScrollTrigger');
					$this->mpEnqueueScript('ScrollSmoother');
					if ($this->fixedSticky) {
						$is_bricks = function_exists('bricks_is_builder_main') && \bricks_is_builder_main();
						$dcl_wrapper .= $is_bricks
							? ''
							: 'Array.from(document.getElementsByTagName("*")).filter((e=>["fixed","sticky"].includes(getComputedStyle(e).position))).reverse().forEach((p=>{document.querySelector("body").prepend(p)}));';
					}
					
					$dcl_wrapper .= $this->scrollSmoother;
					$dcl_wrapper .=
						'addEventListener("load",(()=>{if("ScrollSmoother"in _$W&&"function"==typeof ScrollSmoother.get){const o=ScrollSmoother.get();o&&"function"==typeof o.refresh&&o.refresh(!0)}}));';
				}
				
				// Load Lenis smooth scrolling if enabled and page is not excluded
				// This handles cases where Lenis is needed but no Motion.page animations exist
				if ($this->is_lenis && !$this->isCurrentPageExcludedFromLenis()) {
					$this->mpEnqueueScript('Lenis');
					$dcl_wrapper .= $this->lenis;
				}
				
				$dcl_wrapper .= $this->getFilterValue('scripts', 'after', '');
				$dcl_wrapper .= '});';
				
				\wp_add_inline_script($this->lastScriptId, 'window._$W = window;' . $dcl_wrapper, 'after');
			}
		}

		if ($this->iframeCtx) {
			$this->iframe();
		}

		if ($prevent_load && !$this->iframeCtx && ($_GET['debug'] ?? '') === 'scripts') {
			$version = $this->plugin->version();
			$allow = (int) ($allow_disable_mobile && \wp_is_mobile());

			$scripts = [
				'mp-gsap',
				'mp-ScrollTrigger',
				'mp-ScrollSmoother',
				'mp-ScrollToPlugin',
				'mp-MotionPathPlugin',
				'mp-MotionPathHelper',
				'mp-Flip',
				'mp-Observer',
				'mp-SplitText',
				'mp-DrawSVG',
				'mp-CSSRulePlugin',
				'mp-CustomEase',
				'mp-ImageSequence',
				'mp-MouseMove',
				'mp-ScrambleText',
				'mp-MorphSVG',
				'mp-GSDevTools',
				'mp-Cursor'
			];

			foreach ($scripts as $script) {
				\wp_enqueue_script($script);
			}

			\wp_add_inline_script(
				'mp-SplitText',
				'function _mp_GENSEL(e){const n=[];for(;e.tagName;){let t=0;if(e.parentNode){const n=e.parentNode.children;for(;t<n.length&&n[t]!==e;)t++}n.unshift(e.nodeName+(t>0?`:nth-child(${t+1})`:"")),e=e.parentNode}return n.join(" > ")}',
				'before'
			);

			// TODO: add attachExitClick

			$code = 'window.MOTIONPAGE_FRONT={version:"' . $version . '", disabledMobile:' . $allow . '};';
			$code .=
				'console.log("%c Debug Mode", "font-weight: bold; font-size: 50px;color: red; text-shadow: 3px 3px 0 rgb(217,31,38) , 6px 6px 0 rgb(226,91,14) , 9px 9px 0 rgb(245,221,8) , 12px 12px 0 rgb(5,148,68) , 15px 15px 0 rgb(2,135,206) , 18px 18px 0 rgb(4,77,145) , 21px 21px 0 rgb(42,21,113)");';
			\wp_add_inline_script(end($scripts), 'window._$W = window;' . $code, 'after');
		}
	}

	private function iframe(): void {
		if (!\current_user_can('manage_options')) {
			return;
		}

		\do_action('motionpage/action/iframe');

		\add_filter('sgo_javascript_combine_exclude_all_inline', '__return_true');
		\add_filter('sgo_javascript_combine_exclude_all_inline_modules', '__return_true');

		\add_filter('js_do_concat', '__return_false'); // Page Optimize

		\add_filter('wt_cli_enable_js_blocking', '__return_false'); // Cookie Yes

		//\wp_deregister_script('react');

		\add_filter('qm/dispatch/html', '__return_false');

		\remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
		\add_filter('show_admin_bar', '__return_false', PHP_INT_MAX);

		if (version_compare(\get_bloginfo('version'), '6.4', '<')) {
			\remove_action('wp_head', '_admin_bar_bump_cb');
		} else {
			\add_action(
				'wp_enqueue_scripts',
				function () {
					wp_dequeue_style('admin-bar');
				},
				PHP_INT_MAX
			);

			\add_action('after_setup_theme', function () {
				remove_action('wp_head', '_admin_bar_bump_cb');
				remove_action('wp_head', 'wp_admin_bar_header');
				remove_action('wp_footer', 'wp_admin_bar_render', 1000);
				remove_action('admin_bar_menu', 'wp_admin_bar_header', 1);
			});
		}

		// Brindle QuickPop iframe fix
		if (defined('QP_PLUGIN_DIR')) {
			\wp_dequeue_script('qp-functions');
			\wp_dequeue_style('qp-styles');
			\wp_dequeue_style('qp-font-styles');
		}

		// Optimole JS/CSS fix
		\add_filter('optml_should_replace_page', '__return_true');

		// WP Meteor
		\add_filter('wpmeteor_enabled', '__return_false');

		Vite::useVite('embed/embed.ts', [
			'mp-MouseMove',
			'mp-ImageSequence',
			'mp-ScrollToPlugin',
			'mp-SplitText',
			'mp-DrawSVG',
			'mp-CustomEase',
			'mp-Flip',
			'mp-Observer',
			'mp-ScrollSmoother',
			'mp-Lenis',
			'mp-MotionPathHelper',
			'mp-ScrambleText',
			'mp-MorphSVG',
			'mp-Cursor',
			'mp-ClassName'
		]);

		\do_action('motionpage/action/iframe/end');
	}
}
