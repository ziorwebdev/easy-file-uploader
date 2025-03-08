<?php
namespace ZIOR\FilePond;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles FilePond integration for WordPress.
 */
class Uploader {

	/**
	 * Singleton instance of the class.
	 *
	 * @var Uploader|null
	 */
	private static ?Uploader $instance = null;

	/**
	 * Verify the nonce for security validation.
	 *
	 * This function retrieves the nonce from the request headers, sanitizes it,
	 * and verifies its validity using `wp_verify_nonce()`.
	 *
	 * @return bool True if the nonce is valid, false otherwise.
	 */
	private function verify_nonce(): bool {
		$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';

		return ! empty( $nonce ) && wp_verify_nonce( $nonce, 'filepond_uploader_nonce' );
	}

	/**
	 * Retrieve the uploaded file data from the form submission.
	 *
	 * This function extracts the uploaded file details from the `$_FILES` array
	 * and structures it into a standard file data array.
	 *
	 * @return array|bool An associative array containing file data or false if no valid file is found.
	 */
	private function get_uploaded_file(): array|bool {
		$files = $_FILES['form_fields'] ?? array();

		if ( empty( $files ) || ! is_array( $files ) ) {
			return false;
		}

		$field_name = array_key_first( $files['name'] );
		$file_keys  = array( 'name', 'type', 'tmp_name', 'error', 'size' );
		$file       = array();

		// Extract the first file from the multidimensional $_FILES structure.
		foreach ( $file_keys as $key ) {
			$file[ $key ] = is_array( $files[ $key ][ $field_name ] ) ? $files[ $key ][ $field_name ][0] : $files[ $key ][ $field_name ];
		}

		return $file;
	}

	/**
	 * Validate the file type against allowed types.
	 *
	 * This function applies the 'wp_filepond_validate_file_type' filter to allow
	 * external modification of the validation logic.
	 *
	 * @param array $file        File data array containing file details.
	 * @param array $valid_types Array of allowed file types.
	 * 
	 * @return bool True if the file type is valid, false otherwise.
	 */
	private function is_valid_file_type( array $file, array $valid_types ): bool {
		return apply_filters( 'wp_filepond_validate_file_type', false, $file, $valid_types );
	}

	/**
	 * Validate the file size against the maximum allowed size.
	 *
	 * This function applies the 'wp_filepond_validate_file_size' filter to allow
	 * external modification of the validation logic.
	 *
	 * @param array $file    File data array containing file details.
	 * @param int   $max_size Maximum allowed file size in bytes.
	 * 
	 * @return bool True if the file size is valid, false otherwise.
	 */
	private function is_valid_file_size( array $file, int $max_size ): bool {
		return apply_filters( 'wp_filepond_validate_file_size', false, $file, $max_size );
	}

	/**
	 * Constructor.
	 *
	 * Hooks into WordPress to enqueue scripts and styles.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wp_filepond_upload', array( $this, 'handle_filepond_upload' ), 10 );
		add_action( 'wp_ajax_nopriv_wp_filepond_upload', array( $this, 'handle_filepond_upload' ), 10 );
		add_action( 'wp_ajax_wp_filepond_remove', array( $this, 'handle_filepond_remove' ), 10 );
		add_action( 'wp_ajax_nopriv_wp_filepond_remove', array( $this, 'handle_filepond_remove' ), 10 );

		add_filter( 'wp_filepond_validate_file_type', array( $this, 'validate_file_type' ), 10, 3 );
		add_filter( 'wp_filepond_validate_file_size', array( $this, 'validate_file_size' ), 10, 3 );
	}

	/**
	 * Retrieves the singleton instance of the class.
	 *
	 * @return Uploader The single instance of the class.
	 */
	public static function get_instance(): Uploader {
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
			wp_send_json_error( array( 'error' => __( 'Security check failed.', 'wp-filepond' ) ), 403 );
		}

		// Retrieve the file URL from the request body.
		$file_url = file_get_contents( 'php://input' );

		if ( ! $file_url ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid file URL.', 'wp-filepond' ) )
			);
		}

		// Convert the file URL to a file path.
		$upload_dir = wp_upload_dir();
		$file_path  = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url );

		if ( wp_delete_file( $file_path ) ) {
			wp_send_json_success(
				array( 'message' => __( 'File deleted successfully.', 'wp-filepond' ) )
			);
		} else {
			wp_send_json_error(
				array( 'message' => __( 'Failed to delete file.', 'wp-filepond' ) )
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
			wp_send_json_error( array( 'error' => __( 'Security check failed.', 'wp-filepond' ) ), 403 );

			return;
		}

		// Retrieve and validate uploaded file.
		$file = $this->get_uploaded_file();

		if ( empty( $file ) ) {
			wp_send_json_error( array( 'error' => __( 'No valid file uploaded.', 'wp-filepond' ) ) );

			return;
		}

		// Retrieve and validate file properties.
		$secret_key  = sanitize_text_field( wp_unslash( $_POST['secret_key'] ?? '' ) );
		$args        = decrypt_data( $secret_key );
		$valid_types = explode( ',', $args['types'] );

		if ( ! $this->is_valid_file_type( $file, $valid_types ) ) {
			wp_send_json_error( array( 'error' => get_option( 'wp_filepond_file_type_error', '' ) ), 415 );

			return;
		}

		if ( ! $this->is_valid_file_size( $file, $args['size'] ) ) {
			wp_send_json_error( array( 'error' => get_option( 'wp_filepond_file_size_error', '' ) ), 413 );

			return;
		}

		// Ensure WordPress file functions are available.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Trigger a custom action before the file upload process starts.
		do_action( 'wp_filepond_before_upload', $file, $args );

		// Upload the file using WordPress' built-in file upload handler.
		$uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );

		// Trigger a custom action after the file upload process is complete.
		do_action( 'wp_filepond_after_upload', $uploaded, $file, $args );

		// Respond with the upload result.
		if ( $uploaded && ! isset( $uploaded['error'] ) ) {
			wp_send_json_success( $uploaded );
		} else {
			wp_send_json_error( $uploaded, 400 );
		}
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