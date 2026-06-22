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
 * @author	CoreWeb OÜ
 * @link		https://github.com/andrefelipe/vite-php-setup/blob/master/public/helpers.php
 * @since		2.0.0
 */

class Vite {
	public static function useVite(string $script = '', array $js_dep = []) {
		// prettier-ignore
		if (!$script) return;

		$is_embed = \str_contains($script, 'embed');
		$original_script = $script;

		// Map user-friendly entry names to actual manifest keys (monorepo paths)
		$manifestKeyMap = [
			'embed/embed.ts' => '../../../../packages/builder/src/embed/embed.ts',
			'main.tsx' => 'main.tsx',
		];

		// Map dev server paths (what Vite dev server expects)
		$devPathMap = [
			'embed/embed.ts' => '@fs' . MOTIONPAGE_DIR_PATH . 'packages/builder/src/embed/embed.ts',
			'main.tsx' => 'main.tsx',
		];

		// Use mapped key for manifest lookup (production)
		$manifest_key = $manifestKeyMap[$script] ?? $script;
		// Use dev path for Vite dev server
		$dev_path = $devPathMap[$script] ?? $script;

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

		\add_action('wp_head', function () use (&$manifest_key) {
			foreach (self::importsUrls($manifest_key) as $url) {
				echo '<link rel="modulepreload" href="' . $url . '">';
			}
		});

		// CSS handling - always enqueue in production, Vite handles it in dev (except for embed)
		if (!MOTIONPAGE_IS_DEVELOPMENT) {
			foreach (self::cssUrls($manifest_key) as $url) {
				\wp_enqueue_style(MOTIONPAGE_ASSETS_PREFIX . $original_script, $url);
			}
		} elseif ($is_embed) {
			// In dev mode, embed CSS needs Vite client to inject it
			// Add Vite client script for embed to handle CSS injection
			\add_action(
				'wp_print_scripts',
				function () {
					$url = MOTIONPAGE_DEV_URL;
					echo '<script type="module" crossorigin src="' . $url . '@vite/client"></script>';
				},
				10
			);
		}

		return self::enqueue($manifest_key, $dev_path, $js_dep, $original_script);
	}

	private static function enqueue($manifest_key, $dev_path, $js_deps, $original_script) {
		//if (\wp_script_is("module/hypewolf/mp/$entry", 'enqueued')) {
		//	return;
		//}

		$url = MOTIONPAGE_IS_DEVELOPMENT ? MOTIONPAGE_DEV_URL . $dev_path : self::assetUrl($manifest_key);

		// prettier-ignore
		if (!$url) return '';

		$name = 'module/' . MOTIONPAGE_ASSETS_PREFIX . $original_script;

		\wp_enqueue_script(
			$name,
			$url,
			$js_deps,
			// In production, Vite embeds content hashes in filenames (e.g. main-Dn0kkXUo.js)
			// which handle cache busting. WordPress's ?ver= parameter must be omitted because
			// ES module dynamic imports reference files by bare URL — a URL mismatch causes
			// the browser to load the same module twice, creating duplicate app instances.
			MOTIONPAGE_IS_DEVELOPMENT ? time() : false,
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
