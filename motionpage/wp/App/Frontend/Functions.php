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

/**
 * Class Functions
 *
 * @package motionpage\App\Frontend
 * @since 2.1.7
 */
class Functions extends Base {
	/**
	 * Return all results from wpdb table [motionpage_scripts]
	 * @since   1.0.0
	 * @param   array $bypassFilterArray | getFilterValue('scripts', 'bypass', [])
	 */
	public function getAllScripts($bypassFilterArray): array {
		global $wpdb;

		$plugin_dbv = $this->plugin->databaseVersion();
		$current_dbv = motionpage()->getOption('motionpage_db_version', $plugin_dbv);

		$is_new_dbv = version_compare($current_dbv, $plugin_dbv, '>=');
		$table_name = \esc_sql($wpdb->prefix . ($is_new_dbv ? 'motionpage_code' : 'motionpage_scripts'));

		if (
			is_array($bypassFilterArray) &&
			!empty($bypassFilterArray) &&
			count(array_filter($bypassFilterArray, 'is_numeric')) === count($bypassFilterArray)
		) {
			$placeholders = array_fill(0, count($bypassFilterArray), '%d');
			$sql = sprintf('SELECT * FROM %s WHERE id NOT IN (%s)', $table_name, implode(',', $placeholders));
			return $wpdb->get_results($wpdb->prepare($sql, ...$bypassFilterArray));
		}

		return $wpdb->get_results($wpdb->prepare('SELECT * FROM %1$s', $table_name));
	}

	/**
	 * Return all post types for Oxygen Templates
	 * @since   1.5.0
	 */
	public function getOxygenTemplates(int $POST_ID): array {
		$inner_oxy_templates = [];
		if (class_exists('OxygenElement', false)) {
			$ct_other_template = \get_post_meta($POST_ID, 'ct_other_template', true);
			if (!empty($ct_other_template) && $ct_other_template > 0) {
				$inner_oxy_templates[] = (string) $ct_other_template;
				$parent_template = \get_post_meta($ct_other_template, 'ct_parent_template', true);

				if ($parent_template) {
					$inner_oxy_templates[] = (string) $parent_template;
				}
			} elseif (function_exists('ct_get_posts_template')) {
				$temp_id = \ct_get_posts_template($POST_ID)->ID ?? false;
				if ($temp_id) {
					$inner_oxy_templates[] = (string) $temp_id;
					$parent_template = \get_post_meta($temp_id, 'ct_parent_template', true);
					if ($parent_template) {
						$inner_oxy_templates[] = (string) $parent_template;
					}
				}
			}
		}

		return $inner_oxy_templates;
	}

	/**
	 * Determine if timeline should be loaded or not
	 * @since   1.5.0
	 */
	public function getIsLive(object $timeline, int $POST_ID): bool {
		$same_post_id = (int) $timeline->post_id === $POST_ID;

		if (isset($_GET['mp']) && !empty($_GET['mp'])) {
			if ($_GET['mp'] === 'foucBypass') {
				return false;
			}

			if ($_GET['mp'] === 'preview' && $same_post_id) {
				if (!\has_filter('motionpage/utils/bypassReduced')) {
					\add_filter('motionpage/utils/bypassReduced', '__return_true');
				}

				return true;
			}

			$limiter = strpos($_GET['mp'], '-') !== false ? '-' : ',';
			$split_by_limiter = explode($limiter, $_GET['mp']);

			if (in_array($timeline->id, $split_by_limiter)) {
				if (!\has_filter('motionpage/utils/bypassReduced')) {
					\add_filter('motionpage/utils/bypassReduced', '__return_true');
				}

				return true;
			}
		}

		if (!$timeline->is_active) {
			return false;
		}

		$is_global = filter_var($timeline->is_global, FILTER_VALIDATE_BOOLEAN);
		//Categories
		$categories = explode(',', $timeline->cats ?? '');
		$is_categories = false;
		foreach ($categories as $category) {
			if (empty($category)) {
				continue;
			}

			if (@preg_match($category, $_SERVER['REQUEST_URI'])) {
				$is_categories = true;
				break;
			}
		}

		// Post Types
		$db_types_array = explode(',', $timeline->types ?? '');
		$is_oxy_tmpl = !empty(array_intersect($this->getOxygenTemplates($POST_ID), $db_types_array));
		$is_wp_tmpl = \get_post_type($POST_ID) && in_array(\get_post_type($POST_ID), $db_types_array);
		// Other
		$is_404 = \is_404() && in_array('$404', $db_types_array);
		$is_search = \is_search() && in_array('$search', $db_types_array);

		// return true if timeline should be live on the frontend
		return $same_post_id ||
			$is_global ||
			$is_categories ||
			$is_oxy_tmpl ||
			$is_wp_tmpl ||
			$is_404 ||
			$is_search;
	}

	/**
	 * Prevent Scripts from loading at all
	 * @param bool $prevent_load | getFilterValue('utils', 'stopper', false)
	 */
	public function preventLoad($prevent_load): bool {
		if (
			!isset($_GET['mp']) &&
			isset($_COOKIE['mp-block']) &&
			htmlspecialchars($_COOKIE['mp-block']) === 'true'
		) {
			$prevent_load = true;
		}

		if (($_GET['mp'] ?? '') === 'debug') {
			$prevent_load = true;
		}

		return $prevent_load;
	}
}
