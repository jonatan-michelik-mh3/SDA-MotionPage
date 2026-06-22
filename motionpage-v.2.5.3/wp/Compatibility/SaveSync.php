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

namespace motionpage\Compatibility;

/**
 * @since 2.0.0
 */
class SaveSync {
	public function init(): void {
		/**
		 * Compatibility classes instantiates after anything else
		 * @see Scaffold::__construct
		 */
		\add_action('oxygen_enqueue_ui_scripts', [$this, 'syncSave'], 1500);
		\add_action('bricks_body', [$this, 'syncSave'], 1500);
		\add_action('admin_print_scripts-post.php', [$this, 'syncSave'], 1500);
		\add_action('elementor/editor/after_enqueue_scripts', [$this, 'syncSave'], 1500);
	}

	/**
	 * @since  1.5.0
	 */
	public function syncSave(): void {
		$selector = '';
		$message = '';
		$keyEvents = '';

		if (defined('SHOW_CT_BUILDER')) {
			$selector = '#oxygen-topbar .oxygen-save-button';
			$message = 'CtBuilderAjax?.postId';
		}

		if (
			function_exists('bricks_is_builder_iframe') &&
			!\bricks_is_builder_iframe() &&
			function_exists('bricks_is_builder') &&
			\bricks_is_builder()
		) {
			$selector = '#bricks-toolbar .save';
			$message = 'bricksData?.postId';
			$keyEvents = <<<JS
			document.addEventListener('keydown', function(event) {
					const isS = event.key === 's' || event.key === 'S';

					if ((event.metaKey || event.ctrlKey) && isS) {
						setTimeout(() => {
							sysend?.broadcast("motionpage-builders-save", { message: {$message} });
						}, 1000);
					}
			});
			JS;
		}

		//if (isset($_GET['breakdance']) && $_GET['breakdance'] === 'builder') {
		//	$selector = '[data-test-id="main-save-button"]';
		//	$message = 'Breakdance?.initialDocumentToLoadId';
		//}

		global $pagenow;
		$is_post_page = \is_admin() && ($pagenow === 'post.php' || \get_post_type() === 'post');
		if ($is_post_page) {
			$selector = 'button.editor-post-publish-button__button';
			$message = (int) \get_the_ID();
		}

		if (isset($_GET['action']) && $_GET['action'] === 'elementor') {
			$selector = '#elementor-panel-saver-button-publish';
			$message = 'window?.ElementorConfig?.initial_document?.id';
		}

		if ($selector && $message) {
			\wp_enqueue_script(
				'mp-sysend',
				MOTIONPAGE_DIR_URL . 'assets/js/' . 'sysend.min.js',
				[],
				'1.17.5',
				false
			);

			$code = <<<JS
			document.addEventListener("load", () => {
				setTimeout(() => {
					document.querySelector("{$selector}")?.addEventListener("click", () => {
						setTimeout(() => {
							sysend?.broadcast("motionpage-builders-save", { message: {$message} });
						}, 1000);
					});
				}, 2000);
			});
			{$keyEvents}
			JS;

			\wp_add_inline_script('mp-sysend', $code, 'after');

			if ($is_post_page) {
				\wp_localize_script('mp-sysend', MOTIONPAGE_NAME, [
					'motionpage_url' => \get_admin_url(null, 'admin.php?page=motionpage'),
					'page_id' => (int) \get_the_ID(),
					'redirect' => [
						'post_id' => (int) \get_the_ID(),
						'link' => \get_permalink(),
						'value' => html_entity_decode(\get_the_title())
					]
				]);
			}
		}
	}
}
