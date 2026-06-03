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

namespace motionpage\Common\Utils;

// Adapted from https://github.com/andrefelipe/vite-php-setup/blob/master/public/helpers.php

define('MOTIONPAGE_IS_DEVELOPMENT', motionpage()->isDev());
define('MOTIONPAGE_DEV_URL', 'https://' . motionpage()->getDevURL() . ':5173/');
define('MOTIONPAGE_ASSETS_PREFIX', 'hypewolf/mp/');

/**
 * Class Vite
 *
 * @package motionpage\Common\Utils
 *
 * @author	HypeWolf OÜ <hello@hypewolf.com>
 * @link		https://github.com/andrefelipe/vite-php-setup/blob/master/public/helpers.php
 * @since		2.0.0
 */

class Vite {
	public static function useVite(string $script = '', array $js_dep = []) {
		// prettier-ignore
		if (!$script) return;

		$is_embed = \str_contains($script, 'embed');
		if (MOTIONPAGE_IS_DEVELOPMENT && !$is_embed) {
			\add_action(
				'wp_print_scripts',
				function () {
					$url = MOTIONPAGE_DEV_URL;
					echo <<<HTML
					<script type="module">
					import RefreshRuntime from "{$url}@react-refresh";
					RefreshRuntime.injectIntoGlobalHook(window)
					window.\$RefreshReg$ = () => {}
					window.\$RefreshSig$ = () => (type) => type
					window.__vite_plugin_react_preamble_installed__ = true
					</script>
					<script type="module" crossorigin src="{$url}@vite/client"></script>
					HTML;
				},
				10
			);
		}

		\add_action('wp_head', function () use (&$script) {
			foreach (self::importsUrls($script) as $url) {
				echo '<link rel="modulepreload" href="' . $url . '">';
			}
		});

		// not needed on dev, it's inject by Vite
		// prettier-ignore
		if (!MOTIONPAGE_IS_DEVELOPMENT) {
			foreach (self::cssUrls($script) as $url) {
				\wp_enqueue_style(MOTIONPAGE_ASSETS_PREFIX . $script, $url);
			}
		}

		return self::enqueue($script, $js_dep);
	}

	private static function enqueue($script, $js_deps) {
		//if (\wp_script_is("module/hypewolf/mp/$entry", 'enqueued')) {
		//	return;
		//}

		$url = MOTIONPAGE_IS_DEVELOPMENT ? MOTIONPAGE_DEV_URL . $script : self::assetUrl($script);

		// prettier-ignore
		if (!$url) return '';

		$name = 'module/' . MOTIONPAGE_ASSETS_PREFIX . $script;

		\wp_enqueue_script(
			$name,
			$url,
			$js_deps,
			MOTIONPAGE_IS_DEVELOPMENT ? filemtime(MOTIONPAGE_DIR_PATH . 'src/' . $script) : MOTIONPAGE_VERSION,
			true
		);

		return $name;
	}

	// ? Helpers to locate files

	private static function getManifest(): array {
		if (MOTIONPAGE_IS_DEVELOPMENT) {
			return [];
		}

		$fName = 'manifest.json';
		$fPath = rtrim(MOTIONPAGE_DIR_PATH, '/') . '/dist/.vite/' . $fName;
		if (file_exists($fPath)) {
			$content = file_get_contents(rtrim(MOTIONPAGE_DIR_PATH, '/') . '/dist/.vite/' . $fName);
			return json_decode($content, true);
		}
		return [];
	}

	private static function assetUrl(string $entry): string {
		$manifest = self::getManifest();
		return isset($manifest[$entry])
			? rtrim(MOTIONPAGE_DIR_URL, '/') . '/dist/' . $manifest[$entry]['file']
			: rtrim(MOTIONPAGE_DIR_URL, '/') . '/dist/' . $entry;
	}

	private static function getPublicURLBase() {
		//return MOTIONPAGE_IS_DEVELOPMENT ? '/dist/' : rtrim(MOTIONPAGE_DIR_URL, '/');
		return MOTIONPAGE_IS_DEVELOPMENT ? '/' : rtrim(MOTIONPAGE_DIR_URL, '/') . '/dist/';
	}

	private static function importsUrls(string $entry): array {
		$urls = [];
		$manifest = self::getManifest();

		if (!empty($manifest[$entry]['imports'])) {
			foreach ($manifest[$entry]['imports'] as $imports) {
				$urls[] = self::getPublicURLBase() . $manifest[$imports]['file'];
			}
		}
		return $urls;
	}

	private static function cssUrls(string $entry): array {
		$urls = [];
		$manifest = self::getManifest();

		if (!empty($manifest[$entry]['css'])) {
			foreach ($manifest[$entry]['css'] as $file) {
				$urls[] = self::getPublicURLBase() . $file;
			}
		}
		return $urls;
	}
}
