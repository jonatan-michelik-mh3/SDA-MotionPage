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

namespace motionpage\App\General;

use motionpage\Common\Abstracts\Base;

/**
 * @since 2.0.0
 */
class Blocks extends Base {
	public function init(): void {
		/**
		 * This general class is always being instantiated as requested in the Scaffold class
		 * @see Scaffold::__construct
		 */

		//\add_action('init', fn() => \register_block_type($this->plugin->assetsPath() . '/blocks/canvas'));
	}
}
