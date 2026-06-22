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
use motionpage\Common\Utils\CacheUtils;

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
		\add_filter('script_loader_src', [$this, 'stripModuleVersion'], 10, 2);
		\add_action('upgrader_process_complete', [$this, 'upgradeCompleted'], 10, 2);
		\add_action('motionpage/action/builder', [$this, 'upgradeProcess']);
		//\add_action('delete_attachment', [$this, 'deleteAttachment'], 10, 2);

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
		$action = $hook_extra['action'] ?? '';
		$type = $hook_extra['type'] ?? '';

		// Handle both 'update' (standard WP update) and 'install' (ZIP upload overwrite)
		if (
			is_array($hook_extra) &&
			($action === 'update' || $action === 'install') &&
			$type === 'plugin'
		) {
			// For 'update', plugins are in $hook_extra['plugins']
			// For 'install', check the upgrader result for our plugin
			$plugins = $hook_extra['plugins'] ?? [];

			if (!empty($plugins) && is_array($plugins)) {
				foreach ($plugins as $plugin) {
					if ($plugin === MOTIONPAGE_BASENAME) {
						\set_transient('motionpage/updated', MOTIONPAGE_VERSION);
						return;
					}
				}
			}

			// For install actions (ZIP upload), check the upgrader's plugin_info
			if ($action === 'install' && $upgrader instanceof \Plugin_Upgrader) {
				$plugin_info = $upgrader->plugin_info();
				if ($plugin_info === MOTIONPAGE_BASENAME) {
					\set_transient('motionpage/updated', MOTIONPAGE_VERSION);
				}
			}
		}
	}

	public function upgradeProcess(): void {
		$settings = motionpage()->getMainSettings();
		$last_version = $settings['system']['last'] ?? '0.0.0';
		$current_version = MOTIONPAGE_VERSION;

		$has_transient = \get_transient('motionpage/updated');

		// Run if the transient exists (normal WP update path) OR if the stored
		// version is behind the current version (catches ZIP uploads, failed
		// upgrade hooks, and any other path that skips upgrader_process_complete).
		$needs_upgrade = $has_transient || \version_compare($last_version, $current_version, '<');

		if ($needs_upgrade) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'motionpage_code';

			// Add generated_code column if it doesn't exist
			$column_exists = $wpdb->get_results(
				$wpdb->prepare('SHOW COLUMNS FROM `' . $table_name . '` LIKE %s', 'generated_code')
			);

			if (empty($column_exists)) {
				$wpdb->query(
					'ALTER TABLE `' . $table_name . '` ADD COLUMN `generated_code` longtext NULL AFTER `script_value`'
				);
			}

			// Check if any active animations still need migration (have script_value
			// but no generated_code). This is the ONE-TIME pre-3.0 → 3.0 migration.
			// Only set pending if there are ACTUAL legacy animations needing conversion.
			//
			// IMPORTANT: Do NOT delete SDK files or force migration on routine updates.
			// The SDK bundle in wp-content/uploads/motionpage-sdk/ survives plugin updates
			// and continues to work. Deleting it would break the frontend until the user
			// manually re-runs migration. The SDK bundle is regenerated automatically
			// when animations are saved in the builder.
			$data_table = $wpdb->prefix . 'motionpage_data';
			$unmigrated_count = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$data_table}` "
				. "LEFT JOIN `{$table_name}` ON `{$data_table}`.id = `{$table_name}`.data_id "
				. "WHERE `{$table_name}`.is_active = 1 "
				. "AND `{$table_name}`.script_value IS NOT NULL "
				. "AND `{$table_name}`.script_value != '' "
				. "AND (`{$table_name}`.generated_code IS NULL OR `{$table_name}`.generated_code = '')"
			);
			if ($unmigrated_count > 0) {
				$settings['system']['migration_status'] = 'pending';
			}

			// Update version tracking
			$settings['system']['last'] = $current_version;
			$settings['system']['current'] = $current_version;

			// Save settings
			motionpage()->updateOption('motionpage_main', $settings);

			motionpage()->flushRewriteRules();

			// Purge host page caches on plugin update.
			//
			// Animation code is inlined into the page HTML via wp_add_inline_script
			// and may reference SDK APIs (e.g. Motion.context() added in 3.1.0) that
			// were not present in the previously deployed SDK bundle. Without this
			// purge, host-level page caches (LiteSpeed/Hostinger, WP Rocket, Cloudflare,
			// SiteGround, etc.) keep serving stale HTML — sometimes with the new
			// inline wrapper but the old SDK URL — and animations silently break
			// until the user manually saves a page in their page builder. Purging
			// here closes that window automatically.
			CacheUtils::purgePageCaches();

			if ($has_transient) {
				\delete_transient('motionpage/updated');
			}
		}
		// NOTE: migration_status is only set to 'pending' during upgradeProcess()
		// when unmigrated animations are detected. It is used solely for the WP
		// admin notice (MigrationNotice.php) to prompt users to open the builder.
		// The builder's useAutoMigrate hook silently migrates on startup and
		// persists 'complete' back to the DB when done.
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
