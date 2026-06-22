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

namespace motionpage\App\Rest\RestBuilder;

use motionpage\Common\Utils\CacheUtils;

/**
 * The callback for the [EDIT] REST API endpointt
 * @since 2.1.0
 */
class Edit extends AllPoints {
	public function editPath(\WP_REST_Request $request) {
		global $wpdb;
		$wpdb->hide_errors();

		$params = $request->get_json_params();
		$code_table = \esc_sql($wpdb->prefix . 'motionpage_code');

		switch ($params['type']) {
			case 'toggleGlobal':
				return $this->toggleGlobal($params, $wpdb, $code_table);
			case 'toggleActive':
				return $this->toggleActive($params, $wpdb, $code_table);
			case 'updateAnimation':
				return $this->updateAnimation($params, $wpdb, $code_table);
			case 'updateGeneratedCode':
				return $this->updateGeneratedCode($params, $wpdb, $code_table);
			case 'saveSdkBundle':
				return $this->saveSdkBundle($params);

			default:
				http_response_code(400);
				exit('Invalid request');
		}
	}

	private function toggleGlobal($params, $wpdb, string $code_table): \WP_REST_Response {
		$id = (int) $params['id'];
		$is_global_state = (int) $params['is_global'];

		$wpdb->query($wpdb->prepare('SELECT EXISTS (SELECT 1 FROM %1$s WHERE id = %2$d)', $code_table, $id));

		if ($wpdb->last_error !== '') {
			return new \WP_REST_Response(['error' => $wpdb->last_error], 500);
		}

		$wpdb->update($code_table, ['is_global' => $is_global_state], ['id' => $id]);
		if ($wpdb->last_error !== '') {
			return new \WP_REST_Response(['error' => $wpdb->last_error], 500);
		}

		\do_action('motionpage/action/api/update');
		self::purgePageCaches();
		return new \WP_REST_Response([
			'message' => sprintf(
				'Timeline %d global state toggled to %s',
				$id,
				$is_global_state === 1 ? 'global' : 'local'
			)
		]);
	}

	private function toggleActive($params, $wpdb, string $code_table): \WP_REST_Response {
		$id = (int) $params['id'];
		$is_active_state = (int) $params['is_active'];

		$wpdb->query($wpdb->prepare('SELECT EXISTS (SELECT 1 FROM %1$s WHERE id = %2$d)', $code_table, $id));

		if ($wpdb->last_error !== '') {
			return new \WP_REST_Response(['error' => $wpdb->last_error], 500);
		}

		$wpdb->update($code_table, ['is_active' => $is_active_state], ['id' => $id]);
		if ($wpdb->last_error !== '') {
			return new \WP_REST_Response(['error' => $wpdb->last_error], 500);
		}

		\do_action('motionpage/action/api/update');
		self::purgePageCaches();
		return new \WP_REST_Response([
			'message' => sprintf(
				'Timeline %d activate state toggled to %s',
				$id,
				$is_active_state === 1 ? 'active' : 'inactive'
			)
		]);
	}

	private function updateAnimation($params, $wpdb, string $code_table): \WP_REST_Response {
		$this->ensureGeneratedCodeColumn($code_table);

		$id = (int) $params['id'];

		$errors = [];
		$updateDataRow = function ($id) use ($wpdb, $params, &$errors) {
			$data_table = \esc_sql($wpdb->prefix . 'motionpage_data');
			$wpdb->query($wpdb->prepare('SELECT EXISTS (SELECT 1 FROM %1$s WHERE id = %2$d)', $data_table, $id));

			if ($wpdb->last_error !== '') {
				$errors[] = ['error' => $wpdb->last_error];
				return;
			}

			$updated = $wpdb->update(
				$data_table,
				[
					'script_name' => $params['script_name'],
					'trigger_name' => $params['trigger_name'],
					'reload' => $params['reload']
				],
				['id' => $id]
			);

			if ($wpdb->last_error !== '') {
				$errors[] = ['error' => $wpdb->last_error];
				return;
			}

			if ($updated === false) {
				$errors[] = ['error' => 'No rows updated'];
				return;
			}

			return $id;
		};

		$id = $updateDataRow($id);

		if (!empty($errors)) {
			return new \WP_REST_Response(['errors' => $errors], 500);
		}

		$errors = [];
		$updateCodeRow = function ($id) use ($wpdb, $code_table, $params, &$errors) {
			$wpdb->query($wpdb->prepare('SELECT EXISTS (SELECT 1 FROM %1$s WHERE id = %2$d)', $code_table, $id));

			if ($wpdb->last_error !== '') {
				$errors[] = ['error' => $wpdb->last_error];
				return;
			}

			$updated = $wpdb->update(
				$code_table,
				[
					'script_value' => $params['script_value'],
					'generated_code' => $params['generated_code'] ?? null,
					'post_id' => (int) $params['post_id'],
					'is_global' => (int) $params['is_global'],
					'plugins' => $params['plugins'],
					'types' => $params['types'],
					'cats' => $params['cats'],
					'data_id' => $id
				],
				['id' => $id]
			);

			if ($wpdb->last_error !== '') {
				$errors[] = ['error' => $wpdb->last_error];
				return;
			}

			if ($updated === false) {
				$errors[] = ['error' => 'No rows updated'];
				return;
			}

			return $id;
		};

		$id = $updateCodeRow($id);

		if (!empty($errors)) {
			return new \WP_REST_Response(['errors' => $errors], 500);
		}

		\do_action('motionpage/action/api/update', [
			'v1' => [
				'db_id' => (int) $params['id'],
				'selectors' => $params['selectors'] ?? ''
			]
		]);
		self::purgePageCaches();

		return new \WP_REST_Response(['ids' => ['data_id' => $id]]);
	}

	private function updateGeneratedCode($params, $wpdb, string $code_table): \WP_REST_Response {
		$this->ensureGeneratedCodeColumn($code_table);

		$id = (int) $params['id'];
		$generated_code = $params['generated_code'] ?? null;
		$reload = $params['reload'] ?? null;
		// Optional: rewrite script_value when domain URLs have changed
		$script_value = array_key_exists('script_value', $params) ? $params['script_value'] : null;

		$wpdb->query($wpdb->prepare('SELECT EXISTS (SELECT 1 FROM %1$s WHERE id = %2$d)', $code_table, $id));

		if ($wpdb->last_error !== '') {
			return new \WP_REST_Response(['error' => $wpdb->last_error], 500);
		}

		$code_update_data = ['generated_code' => $generated_code];
		if ($script_value !== null) {
			$code_update_data['script_value'] = $script_value;
		}

		$updated = $wpdb->update(
			$code_table,
			$code_update_data,
			['id' => $id]
		);

		if ($wpdb->last_error !== '') {
			return new \WP_REST_Response(['error' => $wpdb->last_error], 500);
		}

		if ($updated === false) {
			return new \WP_REST_Response(['error' => 'No rows updated'], 500);
		}

		// If reload is provided, also update the data table
		if ($reload !== null) {
			$data_table = \esc_sql($wpdb->prefix . 'motionpage_data');
			// Get the data_id from the code row
			$data_id = $wpdb->get_var(
				$wpdb->prepare('SELECT data_id FROM `' . $code_table . '` WHERE id = %d', $id)
			);
			if (!$data_id) {
				return new \WP_REST_Response(['error' => 'Could not resolve data_id for reload update'], 500);
			}
			$reload_updated = $wpdb->update(
				$data_table,
				['reload' => $reload],
				['id' => (int) $data_id]
			);
			if ($wpdb->last_error !== '') {
				return new \WP_REST_Response(['error' => $wpdb->last_error], 500);
			}
			if ($reload_updated === false) {
				return new \WP_REST_Response(['error' => 'Failed to update reload data'], 500);
			}
		}

		\do_action('motionpage/action/api/update');
		self::purgePageCaches();
		return new \WP_REST_Response(['message' => 'Generated code updated']);
	}

	/**
	 * Purge known page caches after animation data changes.
	 *
	 * Animation code is inlined in the HTML via wp_add_inline_script, so page-level
	 * caches (LiteSpeed, WP Rocket, WP Super Cache, W3TC, etc.) must be purged to
	 * ensure visitors get the updated animations immediately after save.
	 *
	 * Delegates to {@see CacheUtils::purgePageCaches()} so the same logic is
	 * reused on plugin update and from any other entry point that mutates
	 * frontend animation output.
	 *
	 * @since 3.0.0
	 */
	private static function purgePageCaches(): void {
		CacheUtils::purgePageCaches();
	}

	private function saveSdkBundle(array $params): \WP_REST_Response {
		$sdk_bundle = $params['sdk_bundle'] ?? null;

		if (empty($sdk_bundle)) {
			return new \WP_REST_Response(['error' => 'SDK bundle content is required'], 400);
		}

		// Create SDK directory in uploads
		$upload_base = \wp_upload_dir()['basedir'];
		$sdk_folder = '/motionpage-sdk';
		$sdk_path = $upload_base . $sdk_folder;

		// Create directory with security files
		if (!is_dir($sdk_path)) {
			if (!motionpage()->createDir($sdk_path)) {
				return new \WP_REST_Response(['error' => 'Failed to create SDK directory'], 500);
			}

			// Add index.php for security
			$save_index = file_put_contents($sdk_path . '/index.php', "<?php //Silence is golden\n");
			if ($save_index === false || $save_index == -1) {
				return new \WP_REST_Response(['error' => 'Failed to create index.php file'], 500);
			}

			// Add .htaccess (allow JS but block PHP)
			$htaccess = <<<EOT
			<FilesMatch "\.(php|php\.)$">
			  order allow,deny
			  deny from all
			</FilesMatch>
			EOT;
			$save_hta = file_put_contents($sdk_path . '/.htaccess', $htaccess);
			if ($save_hta === false || $save_hta == -1) {
				return new \WP_REST_Response(['error' => 'Failed to create .htaccess file'], 500);
			}
		}

		// Use a stable filename — cache busting is handled via ?ver= query param
		// in wp_register_script. This avoids 404s when content changes mid-session.
		$file_name = 'sdk-bundle.js';
		$file_path = $sdk_path . '/' . $file_name;

		$result = file_put_contents($file_path, $sdk_bundle);
		if ($result === false) {
			return new \WP_REST_Response(['error' => 'Failed to write SDK bundle'], 500);
		}

		// Store content hash so wp_register_script version includes it for cache busting.
		// The frontend enqueue appends this to the plugin version: ?ver=3.0.7-ab12cd34
		$hash = substr(md5($sdk_bundle), 0, 8);
		motionpage()->updateOption('motionpage_sdk_hash', $hash);

		// Record the Builder version that produced this bundle. The Builder's
		// `useAutoBundleRefresh` reads this on boot and silently regenerates
		// the bundle when the on-disk version is older than the running
		// Builder, so SDK fixes shipped in a plugin update reach the frontend
		// without the customer having to manually re-save every animation.
		// Caller (sendSdkBundle) sources the value from window.motionpage.version,
		// so the stored value matches the Builder that wrote the bytes — not the
		// plugin's current MOTIONPAGE_VERSION (which may be ahead during the
		// upgrade window between PHP install and the first Builder open).
		$bundle_version = isset($params['bundle_version']) && is_string($params['bundle_version'])
			? sanitize_text_field($params['bundle_version'])
			: '';
		if ($bundle_version !== '') {
			motionpage()->updateOption('motionpage_sdk_bundle_version', $bundle_version);
		}

		// Clean up any legacy content-hash filenames (sdk-{hash}.js) from previous versions
		$old_files = glob($sdk_path . '/sdk-*.js');
		if ($old_files) {
			foreach ($old_files as $old_file) {
				if (basename($old_file) !== $file_name) {
					@unlink($old_file);
				}
			}
		}

		// Purge page caches: a freshly written SDK bundle may add new APIs (e.g.
		// Motion.context) that the inline animation script relies on. Cached HTML
		// served from host-level page caches (LiteSpeed, WP Rocket, Cloudflare,
		// etc.) can otherwise reference an older inline script / older bundle URL
		// and break animations until the page cache is manually cleared.
		self::purgePageCaches();

		// Build URL
		$file_url = \wp_upload_dir()['baseurl'] . $sdk_folder . '/' . $file_name;

		return new \WP_REST_Response([
			'message' => 'SDK bundle saved',
			'file_url' => $file_url,
			'file_path' => $file_path,
			'file_size' => strlen($sdk_bundle)
		]);
	}
}
