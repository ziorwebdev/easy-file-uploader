<?php
namespace ZIOR\FilePond;

use Mimey\MimeTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves the FilePond WP Integration configuration settings.
 *
 * This function fetches stored options related to file handling and
 * applies the 'wp_filepond_configuration' filter for customization.
 *
 * @return array An associative array of configuration settings.
 */
function get_configuration(): array {
	$accepted_file_types = get_option( 'wp_filepond_file_types_allowed', '' );
	$accepted_file_types = convert_extentions_to_mime_types( $accepted_file_types );

	$configuration = array(
		'acceptedFileTypes' => $accepted_file_types,
		'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
		'labelIdle'         => get_option( 'wp_filepond_button_label', 'Browse Image' ),
		'labelMaxFileSize'  => apply_filters( 'wp_filepond_label_max_file_size', '' ),	
		'nonce'             => wp_create_nonce( 'filepond_uploader_nonce' ),
	);
	
	$file_type_error = get_option( 'wp_filepond_file_type_error', '' );

	if ( ! empty( $file_type_error ) ) {
		$configuration['labelFileTypeNotAllowed'] = $file_type_error;
	}

	$file_size_error = get_option( 'wp_filepond_file_size_error', '' );

	if ( ! empty( $file_size_error ) ) {
		$configuration['labelMaxFileSizeExceeded'] = $file_size_error;
	}

	return apply_filters( 'wp_filepond_configuration', $configuration );
}

/**
 * Retrieves the MIME type for a given file extension.
 *
 * Uses the MimeTypes class to determine the appropriate MIME type.
 *
 * @param string $ext The file extension (e.g., 'jpg', 'png', 'pdf').
 * @return string The corresponding MIME type or an empty string if unknown.
 */
function get_mime_type( string $ext ): string {
	$mimes = new MimeTypes();

	return $mimes->getMimeType( $ext ) ?? '';
}

/**
 * Decrypts the given data.
 *
 * This function takes a string of encrypted data.
 * It then converts the decoded string into an associative array using json_decode.
 * 
 * @param string $data The encrypted data to be decrypted.
 * @return array|bool The decrypted data as an associative array or false if the data is invalid.
 */
function decrypt_data( string $data ): bool|array {
    $data = base64_decode( $data );
	
	return json_decode( $data, true );
}

function convert_extentions_to_mime_types( string $extentions ): array {
	$mime_types      = array();
	$file_extensions = array_map( 'trim', explode( ',', $extentions ) );

	// Allow developers to modify the file extensions
	$file_extensions = apply_filters( 'wp_filepond_file_extensions', $file_extensions );

	foreach ( $file_extensions as $file_extension ) {
		$mime_type = get_mime_type( $file_extension );

		if ( empty( $mime_type ) ) {
			continue;
		}

		$mime_types[] = $mime_type;
	}

	return $mime_types;
}	
