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

/**
 * Handle the upload of media files used mainly for Scroll Sequence
 * @since 2.1.0
 */
class Upload extends AllPoints {
	public function uploadPath(\WP_REST_Request $request): \WP_REST_Response {
		$upload = [];
		$sequence_folder = $this->plugin->sequenceFolder();

		$is_media_gallery = filter_var($request['gallery'], FILTER_VALIDATE_BOOLEAN);
		$is_chunks = filter_var($request['chunks'], FILTER_VALIDATE_BOOLEAN);

		// Check if $_FILES array is set and not empty
		if (!isset($_FILES) || empty($_FILES) || !is_array($_FILES)) {
			http_response_code(400);
			exit();
		}

		if (!isset($request['folder']) || !is_string($request['folder'])) {
			http_response_code(400);
			exit();
		}

		$uploaded_files = [];
		$errors = [];

		// Require the 'wp_handle_upload' function if not already available
		if (!function_exists('wp_handle_upload')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$main_folder = '/' . $sequence_folder;

		// Create the main folder if it doesn't exist
		if (
			!is_dir(\wp_upload_dir()['basedir'] . $main_folder) &&
			!motionpage()->createDir(\wp_upload_dir()['basedir'] . $main_folder)
		) {
			return new \WP_REST_Response(['error' => 'Failed to create directory'], 500);
		}

		$dir_name = $main_folder . '/' . motionpage()->sanitizeFolderName($request['folder']);
		$upload_sub_dir = \wp_upload_dir()['basedir'] . $dir_name;

		// Create a subdirectory for the specified folder, with an index.php and .htaccess file
		$index_php_file = $upload_sub_dir . '/' . 'index.php';
		if (!file_exists($index_php_file)) {
			motionpage()->createDir($upload_sub_dir);
			$save_index = file_put_contents($index_php_file, "<?php //Silence is golden\n");
			if ($save_index === false || $save_index == -1) {
				return new \WP_REST_Response(['error' => 'Failed to create index.php file'], 500);
			}

			$htaccess_f = <<<EOT
			<FilesMatch "\.(pl|py|jsp|asp|htm|shtml|sh|cgi|php|php\.)$">
			  order allow,deny
			  deny from all
			</FilesMatch>
			EOT;
			$save_hta = file_put_contents($upload_sub_dir . '/' . '.htaccess', $htaccess_f);
			if ($save_hta === false || $save_hta == -1) {
				return new \WP_REST_Response(['error' => 'Failed to create .htaccess file'], 500);
			}
		}

		// If uploading chunks, decode and save the chunk data
		if ($is_chunks) {
			//function decode_chunk($data) {
			//	$data = explode(';base64,', $data);
			//	if (!is_array($data) || !isset($data[1])) {
			//		return false;
			//	}

			//	$data = base64_decode($data[1]);
			//	if (!$data) {
			//		return false;
			//	}

			//	return $data;
			//}

			foreach ($_FILES as $file) {
				$safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '', basename($file['name']));
				$chunk_file_path = $upload_sub_dir . '/' . $safeName;
				file_put_contents($chunk_file_path, file_get_contents($file['tmp_name']), FILE_APPEND | LOCK_EX);
			}

			return new \WP_REST_Response(['path' => $chunk_file_path]);
		}

		//if (!function_exists('wp_get_image_editor')) {
		//	require_once ABSPATH . 'wp-includes/media.php';
		//}

		// Override the default WordPress upload directory and handle the file upload
		\add_filter('upload_dir', fn($uploads): array => motionpage()->modifyUploadDir($uploads, $dir_name));
		$upload_overrides = ['test_form' => false, 'test_sizebool' => false, 'test_type' => false];
		$allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
		foreach ($_FILES as $key => $file) {
			$fileMimeType = mime_content_type($file['tmp_name']);
			if (!in_array($fileMimeType, $allowedMimeTypes)) {
				$errors[] = ['name' => basename($file['name']), 'error' => 'Invalid file type'];
				continue;
			}

			$safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '', basename($file['name']));
			$_FILES[$key]['name'] = $safeName;

			$upload = \wp_handle_upload($_FILES[$key], $upload_overrides);
			if (empty($upload['error'])) {
				$uploaded_files[] = [
					'name' => basename($upload['file'] ?? '') || 'Missing File Name',
					'url' => $upload['url'] ?? 'Missing File URL'
				];

				//$editor = \wp_get_image_editor($upload['file']);
				//if (!\is_wp_error($editor)) {
				//	$editor->set_quality(50);
				//	$saved_image = $editor->save(null, 'image/webp');
				//}
				continue;
			}

			$errors[] = [
				'name' => basename($upload['file'] ?? '') || 'Missing File Name',
				'error' => $upload['error'] ?? 'Unknown Error'
			];
		}

		// Remove the custom upload directory filter
		\remove_filter('upload_dir', fn($uploads): array => motionpage()->modifyUploadDir($uploads, $dir_name));

		if ($is_media_gallery) {
			if (!function_exists('wp_insert_attachment')) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
			}

			$attachment_id = \wp_insert_attachment(
				[
					'guid' => $upload['url'],
					'post_mime_type' => $upload['type'],
					'post_title' => basename($upload['file']),
					'post_content' => '',
					'post_status' => 'inherit'
				],
				$upload['file']
			);

			if (\is_wp_error($attachment_id) || !$attachment_id) {
				error_log('Error in SQL query: ' . $attachment_id->get_error_message() ?? 'An error occurred.');
				http_response_code(400);
				exit('An error occurred.');
			}

			$hf_cat = 'happyfiles_category';
			if (!\term_exists($sequence_folder . '_motionpage')) {
				\wp_insert_term($sequence_folder . '_motionpage', $hf_cat, [
					'slug' => $sequence_folder,
					'name' => $sequence_folder
				]);
			}

			$dir_mp_hf = \term_exists($sequence_folder . '_motionpage');
			\wp_set_post_terms(
				$attachment_id,
				[
					'term_id' => $dir_mp_hf,
					'term_taxonomy_id' => $dir_mp_hf,
					'name' => $sequence_folder,
					'slug' => $sequence_folder,
					'term_group' => 0,
					'parent' => 0
				],
				$hf_cat
			);

			//\wp_update_attachment_metadata(
			//  $attachment_id,
			//  \wp_generate_attachment_metadata($attachment_id, $upload['file'])
			//);
		}

		//copy($upload['file'], $mp_folder . basename($upload['file']));
		//file_put_contents($mp_folder . $_FILES['files']['name'], $_FILES['files']);

		//$json = [];
		//if ($is_media_gallery) {
		//	$json = [
		//		'name' => basename($upload['file']),
		//		'url' => $upload['url'],
		//	];
		//}

		$response = [];

		if (!empty($uploaded_files)) {
			$response['files'] = $uploaded_files;
		}

		if (!empty($errors)) {
			unset($response['files']);
			$response['errors'] = $errors;
		}

		return new \WP_REST_Response($response, 200);
	}
}
