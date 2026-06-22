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

/**
 * Class MigrationNotice
 *
 * Shows an admin notice when there are legacy animations (script_value exists
 * but generated_code doesn't) that need migration. The migration itself happens
 * automatically when the user opens the builder (useAutoMigrate hook).
 *
 * @package motionpage\App\Backend
 * @since 2.0.0
 */
class MigrationNotice extends Base {
	/**
	 * Initialize the class.
	 * @since 2.0.0
	 */
	public function init(): void {
		// Check the database directly for unmigrated animations.
		// This works on the first admin page load after a plugin update,
		// even before the builder page has been opened.
		global $wpdb;
		$code_table = $wpdb->prefix . 'motionpage_code';
		$data_table = $wpdb->prefix . 'motionpage_data';

		// Bail if tables don't exist yet (fresh install, no animations ever created)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ($wpdb->get_var("SHOW TABLES LIKE '{$data_table}'") !== $data_table) {
			return;
		}

		// Check if the generated_code column exists. If it doesn't, this is a
		// pre-3.0 site that hasn't opened the builder yet (upgradeProcess adds
		// the column). If the site has ANY active animations with script_value,
		// they need migration.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$column_exists = $wpdb->get_results(
			$wpdb->prepare('SHOW COLUMNS FROM `' . $code_table . '` LIKE %s', 'generated_code')
		);

		if (empty($column_exists)) {
			// Column doesn't exist → pre-3.0 DB. Check if there are any active
			// animations at all (they all need migration since none have generated_code).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$has_animations = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$code_table}` "
				. "WHERE `{$code_table}`.is_active = 1 "
				. "AND `{$code_table}`.script_value IS NOT NULL "
				. "AND `{$code_table}`.script_value != ''"
			);

			if ($has_animations > 0) {
				\add_action('admin_notices', [$this, 'displayNotice']);
			}
			return;
		}

		// Column exists — check for animations that have script_value but no generated_code
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$unmigrated_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM `{$data_table}` "
			. "LEFT JOIN `{$code_table}` ON `{$data_table}`.id = `{$code_table}`.data_id "
			. "WHERE `{$code_table}`.is_active = 1 "
			. "AND `{$code_table}`.script_value IS NOT NULL "
			. "AND `{$code_table}`.script_value != '' "
			. "AND (`{$code_table}`.generated_code IS NULL OR `{$code_table}`.generated_code = '')"
		);

		if ($unmigrated_count === 0) {
			return;
		}

		\add_action('admin_notices', [$this, 'displayNotice']);
	}

	/**
	 * Display migration notice
	 * @since 2.0.0
	 */
	public function displayNotice(): void {
		$builder_url = \admin_url('admin.php?page=motionpage');

		echo '<div class="notice notice-info is-dismissible">';
		echo '<p>';
		echo '<strong>⚡ Motion.page ' . \esc_html(MOTIONPAGE_VERSION) . '</strong> — ';
		echo 'New animation engine available! Open the builder to automatically migrate your animations. ';
		echo '<a href="' . \esc_url($builder_url) . '">Open Builder →</a>';
		echo '</p>';
		echo '</div>';
	}
}
