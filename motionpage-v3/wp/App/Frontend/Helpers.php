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

namespace motionpage\App\Frontend;

use motionpage\Common\Abstracts\Base;

/**
 * Helpers
 *
 * @package motionpage\App\Frontend
 * @since 2.0.0
 */
class Helpers extends Base {
	/**
	 * Initialize the class.
	 * @since 2.0.0
	 */
	public function init(): void {
		/**
		 * This backend class is only being instantiated in the backend as requested in the Scaffold class
		 *
		 * @see Requester::isFrontend()
		 * @see Scaffold::__construct
		 *
		 * Add plugin code here for frontend helpers specific functions
		 */
		\add_filter('script_loader_tag', [$this, 'filterScripts'], 0, 3);
		\add_filter('script_loader_src', [$this, 'stripModuleVersion'], 10, 2);
		//\add_action('wp_head', [$this, 'oxygenReusable'], 9999999);
	}

	/**
	 * Strip version query string from ES module scripts.
	 *
	 * ES module dynamic imports reference files by bare URL (e.g.
	 * `import{...}from"./main-Abc123.js"`). If WordPress appends ?ver=X
	 * to the entry script, the browser treats the bare and versioned URLs
	 * as different modules, causing duplicate loads. Vite already embeds
	 * content hashes in filenames for cache busting.
	 *
	 * @since 3.0.0
	 */
	public function stripModuleVersion(string $src, string $handle): string {
		if (\str_contains($handle, 'module/hypewolf/mp/')) {
			$src = \remove_query_arg('ver', $src);
		}
		return $src;
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

			$tag = str_replace('src=""', 'src="' . $src . '"', $tag);
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
	 * Preview Oxygen Reusable Content
	 * @since   1.5.0
	 */
	public function oxygenReusable(): void {
		if (!empty($_GET['motionpage_oxy_reusable'] ?? '')) {
			$reusable_id = htmlspecialchars($_GET['motionpage_oxy_reusable'], ENT_QUOTES, 'UTF-8');
			$sanitize = filter_var($reusable_id, FILTER_SANITIZE_NUMBER_INT);

			// component-init.php [oxygen]
			//$oxygen_vsb_css_styles = new \WP_Styles();
			//$oxygen_vsb_css_styles->add('oxygen-styles', ct_get_current_url('xlink=css'));
			//$oxygen_vsb_css_styles->enqueue(['oxygen-styles']);
			//$oxygen_vsb_css_styles->do_items();
			//ct_css_output

			echo \do_shortcode(\get_post_meta($sanitize, 'ct_builder_shortcodes', true));
			die();
		}
	}
}
