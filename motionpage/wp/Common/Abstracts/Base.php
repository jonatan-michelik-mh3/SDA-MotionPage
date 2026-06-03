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

namespace motionpage\Common\Abstracts;

use motionpage\Config\Plugin;

/**
 * The Base class which can be extended by other classes to load in default methods
 *
 * @package motionpage\Common\Abstracts
 * @since 2.0.0
 */
abstract class Base {
	/**
	 * @var object : will be filled with data from the plugin config class
	 * @see Plugin
	 */
	protected Plugin $plugin;

	/**
	 * Base constructor.
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->plugin = Plugin::init();
	}
}
