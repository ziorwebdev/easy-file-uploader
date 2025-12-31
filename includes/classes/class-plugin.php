<?php
/**
 * Main plugin controller class for ZIOR Drag Drop.
 *
 * This class bootstraps the plugin, loads dependencies, sets up internationalization,
 * and initializes core services including the plugin updater.
 *
 * @package ZIOR\DragDrop
 * @since 1.0.0
 */

namespace ZIOR\DragDrop\Classes;

use ZIOR\DragDrop\Classes\Loader;
use function ZIOR\DragDrop\Functions\get_plugin_version;
use function ZIOR\DragDrop\Functions\get_default_max_file_size;

/**
 * The core plugin class for ZIOR Drag Drop.
 *
 * Responsible for defining core constants, loading dependencies, setting up localization,
 * and initializing the plugin loader and updater.
 *
 * Implements the singleton pattern to ensure only one instance is used.
 *
 * @package ZIOR\DragDrop
 * @since 1.0.0
 */
class Plugin {
	/**
	 * Path to the main plugin file.
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Singleton instance of the Plugin class.
	 *
	 * @var Plugin
	 */
	protected static $instance;

	/**
	 * Current version of the plugin.
	 *
	 * @var string
	 */
	protected $version = '';

	/**
	 * Initialize the plugin.
	 *
	 * Sets up constants, includes required files, and initializes the plugin updater.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init(): void {
		$this->setup_constants();
		$this->include_classes();

		$loader = Loader::get_instance();
		$loader->load();
	}

	/**
	 * Sets default plugin options.
	 *
	 * @since 1.1.7
	 * @return void
	 */
	private function set_default_options() {
		// Set default upload button label.
		update_option( 'easy_dragdrop_button_label', 'Browse Files' );

		// Set default file types.
		update_option( 'easy_dragdrop_file_types_allowed', 'jpg,jpeg,png,gif,bmp,webp,tiff,tif' );

		// Set the max file size. This is based on the server's upload_max_filesize.
		$default_max_file_size = get_default_max_file_size();
		update_option( 'easy_dragdrop_max_file_size', $default_max_file_size );
	}

	/**
	 * Include required plugin files.
	 *
	 * Loads configuration, core functions, loader class, and the updater helper.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function include_classes(): void {
		require_once ZIOR_DRAGDROP_PLUGIN_DIR . 'vendor/autoload.php';
		require_once ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/classes/class-loader.php';
	}

	/**
	 * Defines plugin constants used throughout the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_constants(): void {
		if ( ! defined( 'ZIOR_DRAGDROP_PLUGIN_VERSION' ) ) {
			define( 'ZIOR_DRAGDROP_PLUGIN_VERSION', $this->version );
		}
	}

	/**
	 * Retrieves the singleton instance of the Plugin class.
	 *
	 * Instantiates the class if it hasn't been already, and initializes it using the given plugin file path.
	 *
	 * @since 1.0.0
	 * @param string $plugin_file Absolute path to the main plugin file.
	 * @return static Instance of the Plugin class.
	 */
	public static function get_instance( string $plugin_file ): Plugin {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self( $plugin_file );
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * Sets the plugin file path and version, and hooks into the 'init' action
	 * to load the plugin's text domain for translations.
	 *
	 * @since 1.0.0
	 * @param string $plugin_file Absolute path to the main plugin file.
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->version     = get_plugin_version( $plugin_file );
	}

	/**
	 * Activate the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate_plugin(): void {
		$this->set_default_options();

		// Let developers hook on plugin activate.
		do_action( 'easy_dragdrop_plugin_activate' );
	}

	/**
	 * Deactivate the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate_plugin(): void {}
}
