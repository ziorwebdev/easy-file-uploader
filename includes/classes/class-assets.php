<?php
namespace ZIOR\FilePond;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles FilePond integration for WordPress.
 */
class Assets {

	/**
	 * Singleton instance of the class.
	 *
	 * @var Assets|null
	 */
	private static ?Assets $instance = null;

	private function enqueue_wp_filepond_scripts(): void {
		$uploader_configurations = get_uploader_configurations();

		wp_enqueue_style( 'wp-filepond-vendors', WP_FILEPOND_PLUGIN_URL . 'dist/vendors.min.css', array(), null );
		wp_enqueue_script( 'wp-filepond-vendors', WP_FILEPOND_PLUGIN_URL . 'dist/vendors.min.js', array(), null, true );

		// Allow other addon plugins to enqueue their own scripts and styles.
		do_action( 'enqueue_wp_filepond_scripts' );

		wp_enqueue_style( 'wp-filepond-uploader', WP_FILEPOND_PLUGIN_URL . 'dist/main.min.css', array(), null );
		wp_enqueue_script( 'wp-filepond-uploader', WP_FILEPOND_PLUGIN_URL . 'dist/main.min.js', array( 'jquery' ), null, true );

		wp_localize_script( 'wp-filepond-uploader', 'FilePondUploader', $uploader_configurations );
	}

	/**
	 * Constructor.
	 *
	 * Hooks into WordPress to enqueue scripts and styles.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ), 10 );
	}

	/**
	 * Enqueues scripts and styles for the admin area.
	 */
	public function enqueue_admin_scripts(): void {
		$this->enqueue_wp_filepond_scripts();

		// Allow other addon plugins to enqueue their own scripts and styles.
		do_action( 'enqueue_wp_filepond_admin_scripts' );
	}

	/**
	 * Enqueues scripts and styles for the front-end.
	 */
	public function enqueue_frontend_scripts(): void {
		$this->enqueue_wp_filepond_scripts();

		// Allow other addon plugins to enqueue their own scripts and styles.
		do_action( 'enqueue_wp_filepond_frontend_scripts' );
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
