<?php
namespace ZIOR\WP\FilePond;

use ElementorPro\Modules\ThemeBuilder\Module;
use ElementorPro\Modules\ThemeBuilder\Classes\Conditions_Manager;
use Elementor\Plugin;
use ElementorPro\Plugin as PluginPro;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles FilePond integration for WordPress.
 */
class FilePond {

	/**
	 * Singleton instance of the class.
	 *
	 * @var FilePond|null
	 */
	private static ?FilePond $instance = null;

	/**
	 * Verifies the security nonce.
	 *
	 * @return bool True if nonce is valid, false otherwise.
	 */
	private function verify_nonce(): bool {
		$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';

		return ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_filepond_upload_nonce' );
	}

	/**
	 * Retrieves and structures the uploaded file.
	 *
	 * @return array|false The structured file array or false on failure.
	 */
	private function get_uploaded_file(): array|bool {
		$files = $_FILES['form_fields'] ?? array();

		if ( empty( $files ) || ! is_array( $files ) ) {
			return false;
		}

		$field_name = array_key_first( $files['name'] );
		$file_keys  = array( 'name', 'type', 'tmp_name', 'error', 'size' );
		$file       = array();

		foreach ( $file_keys as $key ) {
			$file[ $key ] = is_array( $files[ $key ][ $field_name ] ) ? $files[ $key ][ $field_name ][0] : $files[ $key ][ $field_name ];
		}

		return $file;
	}

	/**
	 * Validates the uploaded file type and size.
	 *
	 * @param array  $file       The uploaded file array.
	 * @param array  $valid_types Allowed file types.
	 * @param int    $max_size   Maximum file size in bytes.
	 *
	 * @return bool True if valid, false otherwise.
	 */
	private function is_valid_file( array $file, array $valid_types, int $max_size ): bool {
		$valid_file_type = apply_filters( 'wp_filepond_validate_file_type', false, $file, $valid_types );

		if ( ! $valid_file_type ) {
			wp_send_json_error( array( 'error' => get_option( 'wp_fp_file_type_error', '' ) ), 415 );
			return false;
		}

		$valid_file_size = apply_filters( 'wp_filepond_validate_file_size', false, $file, $max_size );

		if ( ! $valid_file_size ) {
			wp_send_json_error( array( 'error' => get_option( 'wp_fp_file_size_error', '' ) ), 413 );
			return false;
		}

		return true;
	}

	/**
	 * Constructor.
	 *
	 * Hooks into WordPress to enqueue scripts and styles.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ), 10 );
		add_action( 'wp_ajax_wp_filepond_upload', array( $this, 'handle_filepond_upload' ), 10 );
		add_action( 'wp_ajax_nopriv_wp_filepond_upload', array( $this, 'handle_filepond_upload' ), 10 );
		add_action( 'wp_ajax_wp_filepond_remove', array( $this, 'handle_filepond_remove' ), 10 );
		add_action( 'wp_ajax_nopriv_wp_filepond_remove', array( $this, 'handle_filepond_remove' ), 10 );

		add_filter( 'wp_filepond_validate_file_type', array( $this, 'validate_file_type' ), 10, 3 );
		add_filter( 'wp_filepond_validate_file_size', array( $this, 'validate_file_size' ), 10, 3 );
	}

	/**
	 * Enqueues scripts and styles for the admin area.
	 */
	public function enqueue_admin_scripts(): void {
		$configuration = get_configuration();

		wp_enqueue_style( 'wp-filepond-admin', PLUGIN_URL . 'dist/main.min.css', array(), null );
		wp_enqueue_script( 'filepond-wp-integration', PLUGIN_URL . 'dist/filepond.min.js', array(), null, true );
		wp_enqueue_script( 'wp-filepond-admin', PLUGIN_URL . 'dist/main.min.js', array( 'jquery', 'filepond-wp-integration' ), null, true );
		wp_localize_script( 'wp-filepond-admin', 'wpFilePondIntegration', $configuration );
	}

	/**
	 * Enqueues scripts and styles for the front-end.
	 */
	public function enqueue_frontend_scripts(): void {
		$configuration = get_configuration();

		wp_enqueue_style( 'filepond-wp-integration', PLUGIN_URL . 'dist/filepond.min.css', array(), null );
		wp_enqueue_style( 'wp-filepond-public', PLUGIN_URL . 'dist/main.min.css', array(), null );
		wp_enqueue_script( 'filepond-wp-integration', PLUGIN_URL . 'dist/filepond.min.js', array(), null, true );
		wp_enqueue_script( 'wp-filepond-public', PLUGIN_URL . 'dist/main.min.js', array( 'jquery', 'filepond-wp-integration' ), null, true );
		wp_localize_script( 'wp-filepond-public', 'wpFilePondIntegration', $configuration );
	}

	/**
	 * Retrieves the singleton instance of the class.
	 *
	 * @return FilePond The single instance of the class.
	 */
	public static function get_instance(): FilePond {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Handles the removal of an uploaded file via FilePond.
	 *
	 * This function verifies the nonce for security, retrieves the file URL from the request,
	 * converts it to the file path, and attempts to delete the file from the server.
	 *
	 * @return void Outputs JSON response indicating success or failure.
	 */
	public function handle_filepond_remove(): void {
		// Verify security nonce.
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( array( 'error' => __( 'Security check failed.', 'filepond-wp-integration' ) ), 403 );
		}

		// Retrieve the file URL from the request body.
		$file_url = file_get_contents( 'php://input' );

		if ( ! $file_url ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid file URL.', 'filepond-wp-integration' ) )
			);
		}

		// Convert the file URL to a file path.
		$upload_dir = wp_upload_dir();
		$file_path  = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url );

		// Check if the file exists and delete it.
		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
			wp_send_json_success(
				array( 'message' => __( 'File deleted successfully.', 'filepond-wp-integration' ) )
			);
		} else {
			wp_send_json_error(
				array( 'message' => __( 'File not found.', 'filepond-wp-integration' ) )
			);
		}
	}

	/**
	 * Handles file uploads via FilePond.
	 *
	 * This function verifies security checks, validates the uploaded file, 
	 * processes the file upload, and saves it to a custom directory.
	 *
	 * @return void Outputs JSON response indicating success or failure.
	 */
	public function handle_filepond_upload(): void {
		// Verify security nonce.
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( array( 'error' => __( 'Security check failed.', 'filepond-wp-integration' ) ), 403 );
		}

		// Retrieve and validate uploaded file.
		$file = $this->get_uploaded_file();
		if ( empty( $file ) ) {
			wp_send_json_error( array( 'error' => __( 'No valid file uploaded.', 'filepond-wp-integration' ) ) );
		}

		// Retrieve secret key.
		$secret_key = sanitize_text_field( wp_unslash( $_POST['secret_key'] ?? '' ) );

		if ( empty( $secret_key ) ) {
			wp_send_json_error( array( 'error' => __( 'Security check failed.', 'filepond-wp-integration' ) ), 403 );
		}

		// Retrieve and validate file properties.
		$args        = decrypt_data( $secret_key );
		$valid_types = explode( ',', $args['types'] );

		if ( ! $this->is_valid_file( $file, $valid_types, $args['size'] ) ) {
			return;
		}

		// Ensure WordPress file functions are available.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Temporarily modify the upload directory.
		add_filter( 'upload_dir', array( $this, 'set_upload_directory' ) );

		// Upload the file.
		$uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );

		// Restore the default upload directory.
		remove_filter( 'upload_dir', array( $this, 'set_upload_directory' ) );

		// Respond with the upload result.
		if ( $uploaded && ! isset( $uploaded['error'] ) ) {
			wp_send_json_success( $uploaded );
		} else {
			wp_send_json_error( $uploaded, 400 );
		}
	}

	/**
	 * Modify the upload directory based on the custom setting.
	 *
	 * @param array $dirs Array of upload directory paths.
	 * @return array Modified upload directory paths.
	 */
	public function set_upload_directory( array $dirs ): array {
		$custom_location = get_option( 'wp_fp_upload_location', '' );
		$custom_location = sanitize_text_field( $custom_location );

		if ( empty( $custom_location ) ) {
			return $dirs;
		}

		$upload_location = '/uploads/' . $custom_location;
		$absolute_path   = WP_CONTENT_DIR . $upload_location;

		// Check if the directory exists; if not, create it.
		if ( ! file_exists( $absolute_path ) ) {
			wp_mkdir_p( $absolute_path );
		}

		$dirs['path']   = $absolute_path;
		$dirs['url']    = WP_CONTENT_URL . $upload_location;
		$dirs['subdir'] = $upload_location;

		return $dirs;
	}

	/**
	 * Validate if the uploaded file size is within the allowed limit.
	 *
	 * @param bool  $valid    Whether the file is already considered valid.
	 * @param array $file     Uploaded file data from $_FILES.
	 * @param int   $max_size Maximum allowed file size in bytes.
	 * @return bool True if the file size is valid, false otherwise.
	 */
	public function validate_file_size( bool $valid, array $file, int $max_size ): bool {
		if ( $valid ) {
			return true;
		}

		return ( $file['size'] <= $max_size );
	}

	/**
	 * Validate if the uploaded file type is allowed.
	 *
	 * @param bool   $valid         Whether the file is already considered valid.
	 * @param array  $file          Uploaded file data from $_FILES.
	 * @param array  $allowed_types List of allowed MIME types (e.g., ['image/png', 'image/jpeg']).
	 * @return bool True if the file type is valid, false otherwise.
	 */
	public function validate_file_type( bool $valid, array $file, array $allowed_types ): bool {
		if ( $valid ) {
			return true;
		}

		// Get the MIME type using wp_check_filetype.
		$file_type = wp_check_filetype( $file['name'] );

		// Validate file type against allowed MIME types.
		return ( ! empty( $file_type['type'] ) && in_array( $file_type['type'], $allowed_types, true ) );
	}
}
