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

namespace motionpage\App\General;

use motionpage\Common\Abstracts\Base;

/**
 * @since 2.0.0
 */
class Shortcodes extends Base {
	public function init(): void {
		/**
		 * This general class is always being instantiated as requested in the Scaffold class
		 * @see Scaffold::__construct
		 */

		\add_shortcode('mp-block', [$this, 'blockFC']);
		\add_shortcode('mp-unblock', [$this, 'unblockFC']);
		\add_shortcode('mp-canvas', [$this, 'canvas']);
	}

	/**
	 * @since   1.6.0
	 */
	public function blockFC($atts = [], $content = null): ?string {
		if (isset($_COOKIE['mp-block']) && htmlspecialchars($_COOKIE['mp-block']) === 'true') {
			return null;
		}

		$default_atts = [
			'class' => 'mp-block__button',
			'days' => 30
		];

		$atts = \shortcode_atts($default_atts, $atts);
		$days = (int) $atts['days'];
		$class = \esc_attr($atts['class']);

		$e_days_legacy = \apply_filters('motionpage_block_days', $days);
		$e_days = \apply_filters('motionpage/utils/block/days', $e_days_legacy);
		$scriptsDataAttr = motionpage()->getScriptsDataAttr();
		$js_string = <<<HTML
		<script {$scriptsDataAttr}>function __MP_SC(e){const t=new Date;t.setTime(t.getTime()+24*e*60*60*1e3);let o="expires="+t.toUTCString();document.cookie="mp-block=true;"+o+";path=/",location.reload();return false;}</script>
		HTML;
		$html_string = <<<HTML
		<a href="#" onclick="__MP_SC({$e_days})" class="{$class}" style="cursor:pointer;">{$content}</a>
		HTML;
		return $js_string . $html_string;
	}

	/**
	 * @since   1.6.0
	 */
	public function unblockFC($atts = [], $content = null): ?string {
		if (!isset($_COOKIE['mp-block'])) {
			return null;
		}

		if (isset($_COOKIE['mp-block']) && htmlspecialchars($_COOKIE['mp-block']) === 'false') {
			return null;
		}

		$default_atts = ['class' => 'mp-block__button unblock'];
		$atts = \shortcode_atts($default_atts, $atts);
		$class = \esc_attr($atts['class']);
		$scriptsDataAttr = motionpage()->getScriptsDataAttr();
		$js_string = <<<HTML
		<script {$scriptsDataAttr}>function __MP_SC(e){const t=new Date;t.setTime(t.getTime()+24*e*60*60*1e3);let o="expires="+t.toUTCString();document.cookie="mp-block=false;"+o+";path=/",location.reload();return false;}</script>
		HTML;
		$html_string = <<<HTML
		<a href="#" onclick="__MP_SC(1)" class="{$class}" style="cursor:pointer;">{$content}</a>
		HTML;
		return $js_string . $html_string;
	}

	/**
	 * @since   1.6.0
	 */
	public function canvas($atts = []): string {
		$default_atts = ['id' => '', 'no_js_url' => ''];
		$atts = \shortcode_atts($default_atts, $atts);

		$id = \esc_attr($atts['id']);
		$no_js_url = \esc_url($atts['no_js_url']);

		static $canvas_index = 0;
		$canvas_index++;

		$style =
			$canvas_index === 1
				? <<<HTML
				<style>mp-sequence>canvas {position: absolute;top: 0;left: 0;width: 100%;height: 100%;}mp-sequence {display: block;position: relative;min-width: 200px;min-height: 200px;width: 100%;height: 100%;}mp-sequence img {position: absolute;top: 0;left: 0;width: 100%;height: 100%;}mp-sequence noscript img {object-fit: cover;}</style>
				HTML
				: '';

		$final_id = empty($id) ? 'mp-sequence-' . $canvas_index : $id;
		$no_js = empty($no_js_url) ? '' : '<noscript><img src="' . $no_js_url . '" /></noscript>';

		return <<<HTML
		<mp-sequence id="{$final_id}">
			{$style}
			{$no_js}
			<canvas></canvas>
		</mp-sequence>
		HTML;
	}
}
