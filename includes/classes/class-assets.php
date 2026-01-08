<?php
/**
 * Assets Class
 *
 * Handles enqueuing scripts and styles for both frontend
 * and Elementor editor iframe.
 *
 * @package ZIORWebDev\DragDrop
 */

namespace ZIORWebDev\DragDrop\Classes;

use function ZIORWebDev\DragDrop\Functions\get_uploader_configurations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Assets
 */
class Assets {

	/**
	 * Singleton instance.
	 *
	 * @var Assets|null
	 */
	private static ?Assets $instance = null;

	/**
	 * Constructor.
	 *
	 * Hooks asset loading into WordPress and Elementor.
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10 );
	}

	/**
	 * Enqueue admin scripts and styles used by the uploader.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		error_log( 'enqueue_admin_scripts: ' );
		wp_enqueue_script(
			'easy-dragdrop-uploader-editor',
			ZIORWEBDEV_DRAGDROP_PLUGIN_URL . 'dist/admin/main.min.js',
			array(),
			ZIORWEBDEV_DRAGDROP_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Enqueue frontend scripts and styles used by the uploader.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$configurations = get_uploader_configurations();

		// Vendor assets.
		wp_enqueue_style(
			'easy-dragdrop-vendors',
			ZIORWEBDEV_DRAGDROP_PLUGIN_URL . 'dist/vendors.min.css',
			array(),
			ZIORWEBDEV_DRAGDROP_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'easy-dragdrop-vendors',
			ZIORWEBDEV_DRAGDROP_PLUGIN_URL . 'dist/vendors.min.js',
			array(),
			ZIORWEBDEV_DRAGDROP_PLUGIN_VERSION,
			true
		);

		/**
		 * Allow extensions to enqueue additional vendor assets.
		 *
		 * @param array $configurations Uploader configurations.
		 */
		do_action( 'enqueue_easy_dragdrop_scripts', $configurations );

		// Main uploader assets.
		wp_enqueue_style(
			'easy-dragdrop-uploader',
			ZIORWEBDEV_DRAGDROP_PLUGIN_URL . 'dist/main.min.css',
			array( 'easy-dragdrop-vendors' ),
			ZIORWEBDEV_DRAGDROP_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'easy-dragdrop-uploader',
			ZIORWEBDEV_DRAGDROP_PLUGIN_URL . 'dist/main.min.js',
			array( 'jquery', 'easy-dragdrop-vendors' ),
			ZIORWEBDEV_DRAGDROP_PLUGIN_VERSION,
			true
		);

		wp_add_inline_script(
			'easy-dragdrop-uploader',
			'window.EasyDragDropUploader = ' . wp_json_encode( $configurations ) . ';',
			'before'
		);
	}

	/**
	 * Get singleton instance.
	 *
	 * @return Assets
	 */
	public static function get_instance(): Assets {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
