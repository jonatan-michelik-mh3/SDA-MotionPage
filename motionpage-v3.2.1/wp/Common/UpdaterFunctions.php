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

namespace motionpage\Common;

use motionpage\Common\Abstracts\Base;

/**
 * Backward-compatible stub for the legacy EDD updater.
 *
 * The real implementation was removed when licensing moved to Convex.
 * This stub exists so that sites upgrading from v2.x don't crash —
 * old hooks still in memory may call motionpageUpdater()->checkUpdate()
 * during the upgrade request. Every method is a safe no-op.
 *
 * @package motionpage\Common
 */
class UpdaterFunctions extends Base {
	/** @param mixed $_transient_data */
	public function checkUpdate($_transient_data) {
		return $_transient_data;
	}

	/** @return mixed */
	public function pluginsApiFilter($_data, $_action = '', $_args = null) {
		return $_data;
	}

	/** @return string */
	public function getCachedVersionInfo($cache_key = '') {
		return '';
	}

	/** @return false */
	public function apiRequest(array $data) {
		return false;
	}

	public function setVersionInfoCache($value = '', $cache_key = ''): void {
	}

	/** @return false */
	public function getRepoApiData() {
		return false;
	}

	public function showUpdateNotification($file, $plugin): void {
	}

	/** @return array */
	public function getActivePlugins(): array {
		return [];
	}
}
