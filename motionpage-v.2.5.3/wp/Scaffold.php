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

namespace motionpage;

use Composer\Autoload\ClassLoader;
use motionpage\Common\Abstracts\Base;
use motionpage\Common\Traits\Requester;
use motionpage\Common\Utils\Errors;
use motionpage\Config\Classes;
use motionpage\Config\I18n;
use motionpage\Config\Requirements;

/**
 * Scaffold the plugin
 *
 * @since 2.0.0
 */
final class Scaffold extends Base {
	/**
	 * Determine what we're requesting
	 *
	 * @see Requester
	 */
	use Requester;

	/**
	 * Used to debug the Scaffold class; this will print a visualised array
	 * of the classes that are loaded with the total execution time if set true
	 *
	 * @var array
	 */
	public $scaffold = ['debug' => false];

	/**
	 * List of class to init
	 * @var array : classes
	 */
	public $class_list = [];

	/**
	 * List of class to delay based on hook
	 * @var array : classes
	 */
	public $classes_with_hook = [];

	/**
	 * Composer autoload file list
	 * @var ClassLoader
	 */
	public $composer;

	/**
	 * Requirements class object
	 * @var Requirements
	 */
	protected $requirements;

	/**
	 * I18n class object
	 * @var I18n
	 */
	protected $i18n;

	/**
	 * Scaffold constructor that
	 * - Checks compatibility/plugin requirements
	 * - Defines the locale for this plugin for internationalization
	 * - Load the classes via Composer's class loader and initialize them on type of request
	 *
	 * @param ClassLoader $composer Composer autoload output.
	 * @throws \Exception
	 * @since 2.0.0
	 */
	public function __construct($composer) {
		parent::__construct();
		$this->startExecutionTimer();
		$is_not_error = $this->checkRequirements();
		$this->setLocale();
		if ($is_not_error) {
			$this->getClassLoader($composer);
			$this->loadClasses(Classes::get());
			\do_action('motionpage/action/loaded');
		}

		$this->debugger();
	}

	/**
	 * Check plugin requirements
	 * @since 2.0.0
	 */
	public function checkRequirements() {
		$set_timer = microtime(true);
		$this->requirements = new Requirements();
		$is_not_error = $this->requirements->check();
		$this->scaffold['check_requirements'] = $this->stopExecutionTimer($set_timer);
		return $is_not_error;
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 * @since 2.0.0
	 */
	public function setLocale(): void {
		$set_timer = microtime(true);
		$this->i18n = new I18n();
		$this->i18n->load();
		$this->scaffold['set_locale'] = $this->stopExecutionTimer($set_timer);
	}

	/**
	 * Get the class loader from Composer
	 * @param $composer
	 * @since 2.0.0
	 */
	public function getClassLoader(ClassLoader $composer): void {
		$this->composer = $composer;
	}

	/**
	 * Initialize the requested classes
	 * @param $classes : The loaded classes.
	 * @since 2.0.0
	 */
	public function loadClasses($classes): void {
		$set_timer = microtime(true);

		foreach ($classes as $class) {
			//if (
			//	!empty($class['hook']) &&
			//	is_string($class['hook']) &&
			//	!empty($class['on_request']) &&
			//	is_string($class['on_request'])
			//) {
			//	$this->getClasses($class['init'], $class['excludes'] ?? [], $class['hook'], $class['on_request']);
			//	continue;
			//}

			if (isset($class['on_request']) && is_array($class['on_request'])) {
				foreach ($class['on_request'] as $on_request) {
					if (!$this->request($on_request)) {
						continue;
					}
				}
			} elseif (isset($class['on_request']) && !$this->request($class['on_request'])) {
				continue;
			}

			$this->getClasses($class['init'], $class['excludes'] ?? []);
		}

		$this->initClasses();
		//$this->initClassesWithHook();
		$this->scaffold['initialized_classes']['timer'] = $this->stopExecutionTimer(
			$set_timer,
			'Total execution time of initialized classes'
		);
	}

	/**
	 * Init the classes
	 * @since 2.0.0
	 */
	public function initClasses(): void {
		$this->class_list = \apply_filters('motionpage/lib/classes', $this->class_list);
		foreach ($this->class_list as $class) {
			try {
				$set_timer = microtime(true);
				$this->scaffold['initialized_classes'][$class] = new $class();
				$this->scaffold['initialized_classes'][$class]->init();
				$this->scaffold['initialized_classes'][$class] = $this->stopExecutionTimer($set_timer);
			} catch (\Throwable $err) {
				\do_action('motionpage/action/class/init/failed', $err, $class);
				$class_escaped = \esc_html($class);
				$error_message = sprintf(
					\__('Could not load class "%s". Check the initialization method.', $this->plugin->textDomain()),
					$class_escaped
				);
				$error_title = \__('Plugin Initialization Error', $this->plugin->textDomain());
				$file_reference = 'Plugin Error';
				$generic_error = 'Error Code: 100';
				Errors::wpDie($error_message, $error_title, $file_reference, $generic_error);
			}
		}
	}

	public function initClassesWithHook(): void {
		$this->classes_with_hook = \apply_filters('motionpage/lib/classes/hooks', $this->classes_with_hook);
		foreach ($this->classes_with_hook as $class_with_hook) {
			$class = $class_with_hook['class'];
			$hook = $class_with_hook['hook'];
			$request = $class_with_hook['request'];
			try {
				if ($this->request($request)) {
					\add_action(
						$hook,
						function () use ($class): void {
							$set_timer = microtime(true);
							$this->scaffold['initialized_classes'][$class] = new $class();
							$this->scaffold['initialized_classes'][$class]->init();
							$this->scaffold['initialized_classes'][$class] = $this->stopExecutionTimer($set_timer);
						},
						100
					);
				}
			} catch (\Throwable $err) {
				\do_action('motionpage/action/class/hooked/init/failed', $err, $class, $hook, $request);
				$class_escaped = \esc_html($class);
				$error_message = sprintf(
					\__(
						'Could not load class "%s". The "init" method is probably missing or try a `composer dumpautoload -o` to refresh the autoloader.',
						$this->plugin->textDomain()
					),
					$class_escaped
				);
				$error_title = \__('Plugin initialize failed', $this->plugin->textDomain());
				$file_reference = 'Error in plugin initialization';
				$generic_error = 'Error code: 001';
				Errors::wpDie($error_message, $error_title, $file_reference, $generic_error);
			}
		}
	}

	/**
	 * Get classes based on the directory automatically using the Composer autoload
	 *
	 * @param string $namespace Class name to find.
	 * @return array Return the classes.
	 * @since 2.0.0
	 */
	public function getClasses(string $namespace, $excludes = [], $hook = '', $request = ''): array {
		$namespace = $this->plugin->namespace() . '\\' . $namespace;
		if (is_object($this->composer) !== false) {
			$classmap = $this->composer->getClassMap();

			// First we're going to try to load the classes via Composer's Autoload
			// which will improve the performance. This is only possible if the Autoloader
			// has been optimized.
			if (isset($classmap[$this->plugin->namespace() . '\\Scaffold'])) {
				if (!isset($this->scaffold['initialized_classes']['load_by'])) {
					$this->scaffold['initialized_classes']['load_by'] = 'Autoloader';
				}

				foreach (array_keys($classmap) as $class) {
					$class = (string) $class;
					if (0 !== strncmp($class, $namespace, strlen($namespace))) {
						continue;
					}

					$has_excluded = false;

					if (is_array($excludes)) {
						foreach ($excludes as $exclude) {
							if ($class === $namespace . $exclude) {
								$has_excluded = true;
								break;
							}
						}
					}

					if ($has_excluded) {
						continue;
					}

					if (!empty($hook) && !empty($request)) {
						$this->classes_with_hook[] = ['class' => $class, 'hook' => $hook, 'request' => $request];
						continue;
					}

					$this->class_list[] = $class;
				}

				return $this->class_list;
			}
		}

		// If the composer.json file is updated then Autoloader is not optimized and we
		// can't load classes via the Autoloader. The `composer dumpautoload -o` command needs to
		// to be called; in the mean time we're going to load the classes differently which will
		// be a bit slower. The plugin needs to be optimized before production-release
		Errors::writeLog([
			'title' => \__("Motion.page classes are not being loaded by Composer's Autoloader"),
			'message' => \__(
				'Try a `composer dumpautoload -o` to optimize the autoloader that will improve the performance on autoloading itself.'
			)
		]);
		// FIXME : this is missing $excludes and $hook
		return $this->getByExtraction($namespace);
	}

	/**
	 * Get classes by file extraction, will only run if autoload fails
	 *
	 * @param $namespace
	 * @since 2.0.0
	 */
	public function getByExtraction($namespace): array {
		if (!isset($this->scaffold['initialized_classes']['load_by'])) {
			$this->scaffold['initialized_classes']['load_by'] =
				'Extraction; Try a `composer dumpautoload -o` to optimize the autoloader.';
		}

		$find_all_classes = [];
		foreach ($this->filesFromThisDir() as $file) {
			$file_data = [
				// file_get_contents() is only discouraged by PHPCS for remote files
				'tokens' => token_get_all(file_get_contents($file->getRealPath())),
				'namespace' => ''
			];
			$find_all_classes = array_merge($find_all_classes, $this->extractClasses($file_data));
		}

		$this->classBelongsTo($find_all_classes, $namespace . '\\');
		return $this->class_list;
	}

	/**
	 * Extract class from file, will only run if autoload fails
	 *
	 * @param $file_data
	 * @param array $classes
	 * @since 2.0.0
	 */
	public function extractClasses($file_data, $classes = []): array {
		for ($index = 0; isset($file_data['tokens'][$index]); $index++) {
			if (!isset($file_data['tokens'][$index][0])) {
				continue;
			}

			if (T_NAMESPACE === $file_data['tokens'][$index][0]) {
				$index += 2; // Skip namespace keyword and whitespace
				while (isset($file_data['tokens'][$index]) && is_array($file_data['tokens'][$index])) {
					$file_data['namespace'] .= $file_data['tokens'][$index++][1];
				}
			}

			if (
				T_CLASS === $file_data['tokens'][$index][0] &&
				T_WHITESPACE === $file_data['tokens'][$index + 1][0] &&
				T_STRING === $file_data['tokens'][$index + 2][0]
			) {
				$index += 2; // Skip class keyword and whitespace
				// So it only works with 1 class per file (which should be psr-4 compliant)
				$classes[] = $file_data['namespace'] . '\\' . $file_data['tokens'][$index][1];
				break;
			}
		}

		return $classes;
	}

	/**
	 * Get all files from current dir, will only run if autoload fails
	 *
	 * @return mixed
	 * @since 2.0.0
	 */
	public function filesFromThisDir(): \RegexIterator {
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__));
		$files = new \RegexIterator($files, '/\.php$/');
		return $files;
	}

	/**
	 * Checks if class belongs to namespace, will only run if autoload fails
	 *
	 * @param $classes
	 * @param $namespace
	 * @since 2.0.0
	 */
	public function classBelongsTo($classes, $namespace): void {
		foreach ($classes as $class) {
			if (strpos($class, (string) $namespace) === 0) {
				$this->class_list[] = $class;
			}
		}
	}

	/**
	 * Start the execution timer of the plugin
	 * @since 2.0.0
	 */
	public function startExecutionTimer(): void {
		if ($this->scaffold['debug'] === true) {
			$this->scaffold['execution_time']['start'] = microtime(true);
		}
	}

	/**
	 * @param $timer
	 * @param string $tag
	 * @since 2.0.0
	 */
	public function stopExecutionTimer($timer, $tag = 'Execution time'): string {
		if ($this->scaffold['debug'] === true) {
			return 'Elapsed: ' .
				round((microtime(true) - $this->scaffold['execution_time']['start']) * 1000, 2) .
				' ms | ' .
				$tag .
				': ' .
				round((microtime(true) - $timer) * 1000, 2) .
				' ms';
		} else {
			return '';
		}
	}

	/**
	 * Visual presentation of the classes that are loaded
	 * @since 2.0.0
	 */
	public function debugger(): void {
		if ($this->scaffold['debug'] === true) {
			$this->scaffold['execution_time'] =
				'Total execution time ' .
				round((microtime(true) - $this->scaffold['execution_time']['start']) * 1000, 2) .
				' ms';
			\add_action('shutdown', function (): void {
				ini_set('highlight.comment', '#969896; font-style: italic');
				ini_set('highlight.default', '#FFFFFF');
				ini_set('highlight.html', '#D16568; font-size: 13px; padding: 0; display: block;');
				ini_set('highlight.keyword', '#7FA3BC; font-weight: bold; padding:0;');
				ini_set('highlight.string', '#F2C47E');
				$output = highlight_string("<?php\n\n" . var_export($this->scaffold, true), true);
				echo sprintf(
					'<div style="background-color: #1C1E21; padding:5px; position: fixed; z-index:9999; bottom:0;">%s</div>',
					$output
				);
			});
		}
	}
}
