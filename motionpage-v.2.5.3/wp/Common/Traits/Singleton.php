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

namespace motionpage\Common\Traits;

/**
 * The singleton skeleton trait to instantiate the class only once
 *
 * @package motionpage\Common\Traits
 * @since		2.0.0
 */
trait Singleton {
	private static $instance;

	private function __construct() {
	}

	private function __clone() {
		\_doing_it_wrong(__FUNCTION__, \__('You are not allowed to clone this class.', MOTIONPAGE_NAME), '2.0.0');
	}

	public function __wakeup() {
		\_doing_it_wrong(
			__FUNCTION__,
			\__('You are not allowed to unserialize this class.', MOTIONPAGE_NAME),
			'2.0.0'
		);
	}

	/**
	 * @since 2.0.0
	 */
	final public static function init(): self {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
