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

namespace motionpage\App\Rest\RestBuilder;

use motionpage\Common\Abstracts\Base;

/**
 * @since 2.0.0
 */
class AllPoints extends Base {
	private ?Settings $settings = null;

	private ?Create $create = null;

	private ?Edit $edit = null;

	private ?Load $load = null;

	private ?Posts $posts = null;

	private ?Delete $delete = null;

	private ?Clear $clear = null;

	private ?Rebuild $rebuild = null;

	private ?Upload $upload = null;

	private ?Sequence $sequence = null;

	private ?ClientMode $clientMode = null;

	/**
	 * @since 2.0.0
	 */
	public function init(): void {
		/**
		 * This class is only being instantiated if REST_REQUEST is defined in the requester as requested in the Scaffold class
		 *
		 * @see Requester::isRestBuilder()
		 * @see Scaffold::__construct
		 */

		$this->settings = new Settings();
		$this->create = new Create();
		$this->edit = new Edit();
		$this->load = new Load();
		$this->posts = new Posts();
		$this->delete = new Delete();
		$this->clear = new Clear();
		$this->rebuild = new Rebuild();
		$this->upload = new Upload();
		$this->sequence = new Sequence();
		$this->clientMode = new ClientMode();

		if (class_exists('WP_REST_Server', false)) {
			\add_action('rest_api_init', [$this, 'addPluginRestAPI']);
		}
	}

	/**
	 * @since 2.0.0
	 */
	public function addPluginRestAPI(): void {
		$this->addCustomRoutes();
	}

	/**
	 * Accessibility:
	 * https://domain.com/wp-json/motionpage/v1/
	 *
	 * @since   1.0.0
	 */
	public function addCustomRoutes(): void {
		$routes = [];

		$routes[] = [
			'path' => 'settings', // Settings API point
			'callback' => [$this->settings, 'settingsGet'],
			'methods' => \WP_REST_Server::READABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'settings', // Settings API point
			'callback' => [$this->settings, 'settingsPost'], // application/x-www-form-urlencoded
			'methods' => \WP_REST_Server::EDITABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'client', // ClientMode API point
			'callback' => [$this->clientMode, 'clientModeGet'],
			'methods' => \WP_REST_Server::READABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'client', // ClientMode API point
			'callback' => [$this->clientMode, 'clientModePost'], // application/x-www-form-urlencoded
			'methods' => \WP_REST_Server::EDITABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'create', // Create API point
			'callback' => [$this->create, 'createPath'],
			'methods' => \WP_REST_Server::CREATABLE,
			'permission' => [$this, 'jsonNonce']
		];

		$routes[] = [
			'path' => 'import', // Import API point
			'callback' => [$this->create, 'createPath'],
			'methods' => \WP_REST_Server::CREATABLE,
			'permission' => [$this, 'jsonNonce']
		];

		$routes[] = [
			'path' => 'edit', // Edit API point
			'callback' => [$this->edit, 'editPath'],
			'methods' => \WP_REST_Server::EDITABLE,
			'permission' => [$this, 'jsonNonce']
		];

		$routes[] = [
			'path' => 'load', // Load API point
			'callback' => [$this->load, 'loadPath'],
			'methods' => \WP_REST_Server::READABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'posts', // Posts API point
			'callback' => [$this->posts, 'postsPath'],
			'methods' => \WP_REST_Server::READABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'wipe', // Delete API point
			'callback' => [$this->delete, 'deletePath'],
			'methods' => \WP_REST_Server::EDITABLE,
			'permission' => [$this, 'jsonNonce']
		];

		$routes[] = [
			'path' => 'clear', // Remove cache to fetch latest plugin version
			'callback' => [$this->clear, 'clearPath'],
			'methods' => \WP_REST_Server::READABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'rebuild', // Rebuild Database API point
			'callback' => [$this->rebuild, 'rebuildPath'],
			'methods' => \WP_REST_Server::READABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'upload', // Upload API point
			'callback' => [$this->upload, 'uploadPath'], // application/x-www-form-urlencoded
			'methods' => \WP_REST_Server::CREATABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'sequence', // Sequence API point
			'callback' => [$this->sequence, 'sequenceGet'],
			'methods' => \WP_REST_Server::READABLE,
			'permission' => [$this, 'queryNonce']
		];

		$routes[] = [
			'path' => 'sequence', // Sequence API point
			'callback' => [$this->sequence, 'sequencePost'], // application/x-www-form-urlencoded
			'methods' => \WP_REST_Server::EDITABLE,
			'permission' => [$this, 'queryNonce']
		];

		foreach ($routes as $route) {
			\register_rest_route($this->plugin->internalRest(), $route['path'], [
				'methods' => $route['methods'],
				'callback' => $route['callback'],
				'permission_callback' => $route['permission']
			]);
		}
	}

	/**
	 * Fills rows in the database with timeline data.
	 * @param array $timeline_data The timeline data to insert.
	 * @return array{code_id: int, data_id: int}|array{error: string, id: int, type: string, data_id?: int}
	 */
	protected function fillRows($timeline_data) {
		global $wpdb;
		$wpdb->hide_errors();
		$prefix = \esc_sql($wpdb->prefix . 'motionpage');
		$table_name_code = $prefix . '_code';
		$table_name_data = $prefix . '_data';

		/**
		 * @var int|array{error: string, id: int, type: string}
		 */
		$createDataRow = function () use ($wpdb, $table_name_data, $timeline_data) {
			$wpdb->insert($table_name_data, [
				'script_name' => \sanitize_text_field($timeline_data['script_name']),
				'trigger_name' => \sanitize_text_field($timeline_data['trigger_name']),
				'reload' => \sanitize_text_field($timeline_data['reload'])
			]);

			if ($wpdb->last_error !== '') {
				return [
					'error' => $wpdb->last_error,
					'id' => $timeline_data['id'],
					'type' => 'data:insert'
				];
			}

			$query_string = $wpdb->prepare('SELECT id FROM %1$s ORDER BY id DESC LIMIT 1', $table_name_data);
			$last_id = $wpdb->get_var($query_string);
			if ($wpdb->last_error !== '' || !isset($last_id)) {
				return [
					'error' => "Can't retrieve last inserted ID",
					'id' => $timeline_data['id'],
					'type' => 'data:select'
				];
			}

			return $last_id;
		};

		$createDataRes = $createDataRow();
		if (isset($createDataRes['error'])) {
			return $createDataRes;
		}

		$last_data_id = (int) $createDataRes;

		/**
		 * @var int|array{error: string, id: int, type: string, data_id?: int}
		 */
		$createCodeRow = function ($last_data_id) use ($wpdb, $table_name_code, $timeline_data) {
			$wpdb->insert($table_name_code, [
				'script_value' => $timeline_data['script_value'],
				'post_id' => (int) $timeline_data['post_id'],
				'is_global' => (int) $timeline_data['is_global'],
				'is_active' => (int) $timeline_data['is_active'],
				'plugins' => \sanitize_text_field($timeline_data['plugins']),
				'types' => \sanitize_text_field($timeline_data['types']),
				'cats' => \sanitize_text_field($timeline_data['cats']),
				'data_id' => $last_data_id
			]);

			if ($wpdb->last_error !== '') {
				return [
					'error' => $wpdb->last_error,
					'id' => $timeline_data['id'],
					'type' => 'code:insert',
					'data_id' => $last_data_id
				];
			}

			$query_string = $wpdb->prepare('SELECT id FROM %1$s ORDER BY id DESC LIMIT 1', $table_name_code);
			$last_id = $wpdb->get_var($query_string);
			if ($wpdb->last_error !== '' || !isset($last_id)) {
				return [
					'error' => "Can't retrieve last inserted ID",
					'id' => $timeline_data['id'],
					'type' => 'code:select',
					'data_id' => $last_data_id
				];
			}

			return $last_id;
		};

		$createCodeRes = $createCodeRow($last_data_id);
		if (isset($createCodeRes['error'])) {
			return $createCodeRes;
		}

		$last_code_id = (int) $createCodeRes;
		return ['code_id' => $last_code_id, 'data_id' => $last_data_id];
	}

	protected function permission(string $nonce): bool {
		if (!isset($nonce) || empty($nonce)) {
			return false;
		}

		return \current_user_can('manage_options') && \wp_verify_nonce($nonce, 'FZBN9G29d8pnN7Z6GK');
	}

	public function queryNonce(\WP_REST_Request $request): bool {
		$nonce = $request['nonce'] ?? '';
		return $this->permission($nonce);
	}

	public function jsonNonce(\WP_REST_Request $request): bool {
		$nonce = '';
		if ($params = $request->get_json_params()) {
			$nonce = $params['nonce'] ?? '';
		}

		return $this->permission($nonce);
	}

	/**
	 * Wrapper for WP_REST_Response
	 * @since 2.0.0
	 */
	protected function handleRestResponse(
		string $action,
		?string $error,
		array $data,
		$status = 200
	): \WP_REST_Response {
		$responseArray = array_merge(['action' => $action, 'error' => $error], $data);
		return new \WP_REST_Response($responseArray, $status);
	}

	public function decode($data) {
		if (is_string($data)) {
			return htmlspecialchars_decode($data, ENT_QUOTES);
		} elseif (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->decode($value);
			}

			return $data;
		} elseif (is_object($data)) {
			if ($data instanceof \Traversable) {
				foreach ($data as $key => $value) {
					$data->$key = $this->decode($value);
				}
			}

			return $data;
		} else {
			return $data;
		}
	}

	public function encode($data) {
		if (is_string($data)) {
			return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
		} elseif (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->encode($value);
			}

			return $data;
		} elseif (is_object($data)) {
			if ($data instanceof \Traversable) {
				foreach ($data as $key => $value) {
					$data->$key = $this->encode($value);
				}
			}

			return $data;
		} else {
			return $data;
		}
	}
}
