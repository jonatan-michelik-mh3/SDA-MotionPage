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

namespace motionpage\Config;

use motionpage\Common\Abstracts\Base;

/**
 * Internationalization and localization definitions
 *
 * @package motionpage\Config
 * @since 2.0.0
 */
final class I18n extends Base {
	/**
	 * Load the plugin text domain for translation
	 * @docs https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain
	 *
	 * @since 2.0.0
	 */
	public function load(): void {
		\load_plugin_textdomain($this->plugin->textDomain(), false, MOTIONPAGE_DIR_PATH . 'languages');
	}
}
