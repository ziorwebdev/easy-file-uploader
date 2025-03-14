<?php
namespace ZIOR\FilePond;

use ElementorPro\Modules\Forms\Classes;

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

	private ?string $temp_file_path = '';

	/**
	 * Deletes all files inside a folder.
	 *
	 * @param string $folder Folder path.
	 * @return bool True on success, false on failure.
	 */
	function delete_files( $folder ) {
		if ( ! is_dir( $folder ) ) {
			return false;
		}

		foreach ( glob( trailingslashit( $folder ) . '*' ) as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
	
		@rmdir( $folder );

		return true;
	}

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
	 * Safely rename a file to avoid overwriting an existing file.
	 *
	 * @param string $source      The source file path.
	 * @param string $destination The destination file path.
	 * @return string|false The new file path if successful, false on failure.
	 */
	private function safe_rename( $source, $destination ) {
		$path      = pathinfo( $destination );
		$dir       = $path['dirname'];
		$filename  = $path['filename'];
		$extension = isset( $path['extension'] ) ? '.' . $path['extension'] : '';

		$counter         = 1;
		$new_destination = $destination;

		while ( file_exists( $new_destination ) ) {
			$new_destination = sprintf( '%s/%s-%d%s', $dir, $filename, $counter, $extension );
			$counter++;
		}

		return rename( $source, $new_destination ) ? $new_destination : false;
	}


	/**
	 * Constructor.
	 *
	 * Hooks into WordPress to enqueue scripts and styles.
	 */
	public function __construct() {
		$this->temp_file_path = wp_upload_dir()['basedir'] . '/filepond-temp';

		add_action( 'wp_ajax_wp_filepond_upload', array( $this, 'handle_filepond_upload' ), 10 );
		add_action( 'wp_ajax_nopriv_wp_filepond_upload', array( $this, 'handle_filepond_upload' ), 10 );
		add_action( 'wp_ajax_wp_filepond_remove', array( $this, 'handle_filepond_remove' ), 10 );
		add_action( 'wp_ajax_nopriv_wp_filepond_remove', array( $this, 'handle_filepond_remove' ), 10 );
		add_action( 'wp_ajax_nopriv_wp_filepond_remove', array( $this, 'handle_filepond_remove' ), 10 );
		add_action( 'wp_filepond_process_field', array( $this, 'process_filepond_field' ), 10, 3 );

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
			wp_send_json_error( array( 'error' => __( 'Security check failed.', 'filepond-wp-integration' ) ), 403 );
		}

		// Retrieve the file id from the request body.
		$file_id = file_get_contents( 'php://input' );

		if ( ! $file_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Missing file ID.', 'filepond-wp-integration' ) )
			);
		}

		$temp_file_path = $this->temp_file_path . '/' . dirname( $unique_id );

		if ( $this->delete_files( $temp_file_path ) ) {
			wp_send_json_success(
				array( 'message' => __( 'Files deleted successfully.', 'filepond-wp-integration' ) )
			);
		} else {
			wp_send_json_error(
				array( 'message' => __( 'Failed to delete files.', 'filepond-wp-integration' ) )
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

			return;
		}

		// Retrieve and validate uploaded file.
		$file = $this->get_uploaded_file();

		if ( empty( $file ) ) {
			wp_send_json_error( array( 'error' => __( 'No valid file uploaded.', 'filepond-wp-integration' ) ) );

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

		$unique_id      = wp_generate_uuid4();
		$temp_file_path = $this->temp_file_path . '/' . $unique_id;

		wp_mkdir_p( $temp_file_path );

		if ( move_uploaded_file( $file['tmp_name'], $temp_file_path . '/' . $file['name'] ) ) {
			wp_send_json_success(
				array(
					'unique_id' => $unique_id . '/' . $file['name']
				)
			);
		} else {
			wp_send_json_error(
				array(
					'error' => 'Failed to move uploaded file.'
				)
			);
		}
	}

	/**
	 * Processes the FilePond field by moving files from the temporary directory to the upload directory.
	 *
	 * @param array               $field         The field data.
	 * @param Classes\Form_Record $record        The form record instance.
	 * @param Classes\Ajax_Handler $ajax_handler The AJAX handler instance.
	 */
	public function process_filepond_field( array $field, Classes\Form_Record $record, Classes\Ajax_Handler $ajax_handler ): void {
		$raw_values = (array) $field['raw_value']; // Ensure $raw_values is always an array.

		if ( empty( $raw_values ) ) {
			return;
		}

		$upload_dir  = wp_upload_dir();
		$upload_path = apply_filters( 'wp_filepond_upload_path', $upload_dir['path'] );
		$value_paths = $value_urls = [];

		foreach ( $raw_values as $unique_id ) {
			if ( empty( $unique_id ) ) {
				continue;
			}

			$source      = $this->temp_file_path . '/' . $unique_id;
			$destination = $upload_path . '/' . basename( $unique_id );

			// Move file to upload directory
			if ( $file_path = $this->safe_rename( $source, $destination ) ) {
				$value_paths[] = $file_path;
				$value_urls[]  = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path );
			}

			// Delete temporary folder containing the file
			$this->delete_files( dirname( $source ) );
		}

		// Store updated values in the record
		$record->update_field( $field['id'], 'value', implode( ', ', $value_urls ) );
		$record->update_field( $field['id'], 'raw_value', implode( ', ', $value_paths ) );
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