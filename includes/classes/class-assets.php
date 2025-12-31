<?php
/**
 * Assets Class
 *
 * This file contains the definition of the Assets class, which is responsible
 * for enqueuing scripts and styles for both frontend and admin areas of the
 * Easy DragDrop Uploader plugin.
 *
 * @package    ZIOR\DragDrop
 */

namespace ZIOR\DragDrop\Classes;

use function ZIOR\DragDrop\Functions\get_uploader_configurations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles DragDrop integration for WordPress.
 */
class Assets {

	/**
	 * Singleton instance of the class.
	 *
	 * @var Assets|null
	 */
	private static ?Assets $instance = null;

	/**
	 * Enqueue scripts and styles for the DragDrop Uploader.
	 *
	 * This function loads the necessary CSS and JavaScript files for the uploader,
	 * ensures dependencies are met, and provides localized script data.
	 *
	 * @return void
	 */
	private function enqueue_easy_dragdrop_scripts(): void {
		// Get uploader configurations.
		$uploader_configurations = get_uploader_configurations();

		// Enqueue vendor styles and scripts.
		wp_enqueue_style( 'easy-dragdrop-vendors', ZIOR_DRAGDROP_PLUGIN_URL . 'dist/vendors.min.css', array(), ZIOR_DRAGDROP_PLUGIN_VERSION );
		wp_enqueue_script( 'easy-dragdrop-vendors', ZIOR_DRAGDROP_PLUGIN_URL . 'dist/vendors.min.js', array(), ZIOR_DRAGDROP_PLUGIN_VERSION, true );

		/**
		 * Allow other plugins or addons to enqueue additional scripts and styles.
		 */
		do_action( 'enqueue_easy_dragdrop_scripts', $uploader_configurations );

		// Enqueue main uploader styles and scripts.
		wp_enqueue_style( 'easy-dragdrop-uploader', ZIOR_DRAGDROP_PLUGIN_URL . 'dist/main.min.css', array(), ZIOR_DRAGDROP_PLUGIN_VERSION );
		wp_enqueue_script( 'easy-dragdrop-uploader', ZIOR_DRAGDROP_PLUGIN_URL . 'dist/main.min.js', array( 'jquery' ), ZIOR_DRAGDROP_PLUGIN_VERSION, true );

		// Localize script to pass PHP variables to JavaScript.
		wp_add_inline_script(
			'easy-dragdrop-uploader',
			'window.EasyDragDropUploader = ' . wp_json_encode( $uploader_configurations ) . ';',
			'before'
		);
	}

	/**
	 * Constructor.
	 *
	 * Hooks into WordPress to enqueue scripts and styles.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10 );
	}

	/**
	 * Enqueues admin scripts and styles for the plugin.
	 *
	 * This function registers and enqueues the admin CSS file used for styling
	 * the plugin's admin interface.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {
		wp_enqueue_style( 'easy-dragdrop-admin', ZIOR_DRAGDROP_PLUGIN_URL . 'dist/admin/main.min.css', array(), ZIOR_DRAGDROP_PLUGIN_VERSION );
	}

	/**
	 * Enqueues scripts and styles for the front-end.
	 */
	public function enqueue_frontend_scripts(): void {
		$this->enqueue_easy_dragdrop_scripts();

		// Allow other addon plugins to enqueue their own scripts and styles.
		do_action( 'enqueue_easy_dragdrop_frontend_scripts' );
	}

	/**
	 * Retrieves the singleton instance of the class.
	 *
	 * @return Assets The single instance of the class.
	 */
	public static function get_instance(): Assets {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
