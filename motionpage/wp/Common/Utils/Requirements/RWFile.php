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

namespace motionpage\Common\Utils\Requirements;

use Micropackage\Requirements\Abstracts\Checker;

class RWFile extends Checker {
	/**
	 * Checker name
	 * @var string
	 */
	protected $name = 'RWFile';

	/**
	 * Checks if the requirement is met
	 * @param  mixed $value Requirement.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	public function check($value): void {
		$content = function_exists('file_get_contents') && function_exists('file_put_contents');
		$dirs =
			function_exists('readdir') &&
			function_exists('closedir') &&
			function_exists('opendir') &&
			function_exists('is_dir') &&
			function_exists('mkdir') &&
			function_exists('rmdir') &&
			function_exists('scandir');

		if ($value === true) {
			if (!$content) {
				$this->add_error('Functions file_get/put_contents are required.');
			} elseif (!$dirs) {
				$this->add_error('Functions for working with directories are required.');
			}
		}
	}
}
