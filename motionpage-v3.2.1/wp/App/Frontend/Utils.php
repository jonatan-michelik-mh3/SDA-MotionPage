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

//use motionpage\Common\Abstracts\Base;

/**
 * Class Utils
 *
 * @package motionpage\App\Frontend
 * @since 2.1.7
 */
class Utils {
	/**
	 * Return correct POST ID | Loaded in 'wp' actions
	 * @since   1.5.0
	 */
	public function getPostID(): int {
		$POST_ID = \get_the_ID() ?: 0;

		if (function_exists('is_shop') && \is_shop()) {
			$POST_ID = (int) motionpage()->getOption('woocommerce_shop_page_id', 0);
		}

		return $POST_ID;
	}

	public function isBuilders(): bool {
		$is_oxygen = defined('SHOW_CT_BUILDER') || defined('OXYGEN_IFRAME');
		$is_bricks =
			isset($_GET['bricksbuilder']) ||
			(function_exists('bricks_is_builder_iframe') && \bricks_is_builder_iframe()) ||
			(function_exists('bricks_is_builder') && \bricks_is_builder()) ||
			(function_exists('bricks_is_builder_main') && \bricks_is_builder_main());
		$is_divi = isset($_GET['et_fb']);
		$is_beaver = isset($_GET['fl_builder']);
		$is_brizzy = isset($_GET['brizy-edit-iframe']);
		return $is_oxygen || $is_beaver || $is_divi || $is_bricks || $is_brizzy;
	}
}
