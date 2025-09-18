<?php
/**
 * Loader Class
 *
 * This file contains the definition of the Loader class, which is responsible
 * for loading the necessary classes for the Easy DragDrop Uploader plugin.
 *
 * @package ZIOR\DragDrop
 * @since 1.0.0
 */

namespace ZIOR\DragDrop\Classes;

use ZIOR\DragDrop\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loader Class
 *
 * This class is responsible for loading the necessary classes for the Easy DragDrop Uploader plugin.
 *
 * @package ZIOR\DragDrop
 * @since 1.0.0
 */
class Loader {

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns instance of Loader.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the autoloader and initializes classes..
	 *
	 * Sets up an SPL autoloader to automatically load classes that match a specific
	 * naming convention.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load() {
		// Set up SPL autoloader.
		spl_autoload_register(
			function ( $class_path ) {
				if ( ! preg_match( '/^ZIOR\\\\DragDrop.+$/', $class_path ) ) {
					return;
				}

				$classes = array(
					'Assets'            => ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/classes/class-assets.php',
					'CF7Uploader'       => ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/classes/integrations/fields/class-cf7uploader.php',
					'ElementorUploader' => ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/classes/integrations/fields/class-elementoruploader.php',
					'Register'          => ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/classes/integrations/class-register.php',
					'Settings'          => ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/classes/class-settings.php',
					'Uploader'          => ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/classes/integrations/class-uploader.php',
				);

				$class_name = explode( '\\', $class_path );

				if ( ! empty( $classes[ end( $class_name ) ] ) ) {
					include $classes[ end( $class_name ) ];
				}
			}
		);

		Classes\Settings::get_instance();
		Classes\Assets::get_instance();
		Classes\Integrations\Uploader::get_instance();
		Classes\Integrations\Register::get_instance();
	}
}
