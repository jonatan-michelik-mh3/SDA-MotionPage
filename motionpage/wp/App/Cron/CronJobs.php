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

namespace motionpage\App\Cron;

use motionpage\Common\Abstracts\Base;

/**
 * Class Example
 *
 * @package Hakken\App\Cron
 * @since 1.0.0
 */
class CronJobs extends Base {
	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		/**
		 * This class is only being instantiated if DOING_CRON is defined in the requester as requested in the Scaffold class
		 *
		 * @see Requester::isCron()
		 * @see Scaffold::__construct
		 */

		//\add_action('motionpage/cron/default', [$this, 'default']);
	}
}
