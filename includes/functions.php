<?php
namespace ZIOR\FilePond;

use Mimey\MimeTypes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves all plugin options.
 *
 * This function fetches the list of option names and retrieves their values
 * from the WordPress options table.
 *
 * @return array An associative array of option names and their corresponding values.
 */
function get_plugin_options(): array {
	$plugin_options = array();
	$options        = get_options();

	foreach ( $options as $option ) {
		$option_value = get_option( $option['option_name'] );
		$plugin_options[ $option['option_name'] ] = $option_value;	
	}

	return $plugin_options;
}

/**
 * Retrieves the FilePond uploader configuration settings.
 *
 * This function fetches stored options related to file handling and
 * applies the 'wp_filepond_uploader_configurations' filter for customization.
 *
 * @return array An associative array of configuration settings.
 */
function get_uploader_configurations(): array {
	$plugin_options      = get_plugin_options();
	$accepted_file_types = $plugin_options['wp_filepond_file_types_allowed'];
	$accepted_file_types = convert_extentions_to_mime_types( $accepted_file_types );

	$uploader_configurations = array(
		'acceptedFileTypes' => $accepted_file_types,
		'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
		'labelIdle'         => $plugin_options['wp_filepond_button_label'] ?? 'Browse Image',
		'labelMaxFileSize'  => apply_filters( 'wp_filepond_label_max_file_size', '' ),	
		'nonce'             => wp_create_nonce( 'filepond_uploader_nonce' ),
	);
	
	$file_type_error = $plugin_options['wp_filepond_file_type_error'] ?? '';

	if ( ! empty( $file_type_error ) ) {
		$uploader_configurations['labelFileTypeNotAllowed'] = $file_type_error;
	}

	$file_size_error = $plugin_options['wp_filepond_file_size_error'] ?? '';

	if ( ! empty( $file_size_error ) ) {
		$uploader_configurations['labelMaxFileSizeExceeded'] = $file_size_error;
	}

	return apply_filters( 'wp_filepond_uploader_configurations', $uploader_configurations );
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

/**
 * Returns the settings options for the plugin.
 *
 * @return array The settings options.
 */
function get_options(): array {
	$options = array(
		array(
			'option_group' => 'wp_filepond_options_group',
			'option_name'  => 'wp_filepond_button_label',
			'sanitize'     => 'sanitize_text_field',
		),
		array(
			'option_group' => 'wp_filepond_options_group',
			'option_name'  => 'wp_filepond_file_types_allowed',
			'sanitize'     => 'sanitize_text_field',
		),
		array(
			'option_group' => 'wp_filepond_options_group',
			'option_name'  => 'wp_filepond_enable_preview',
			'sanitize'     => 'absint',
		),
		array(
			'option_group' => 'wp_filepond_options_group',
			'option_name'  => 'wp_filepond_preview_height',
			'sanitize'     => 'absint',
		),
		array(
			'option_group' => 'wp_filepond_options_group',
			'option_name'  => 'wp_filepond_file_type_error',
			'sanitize'     => 'sanitize_text_field',
		),
		array(
			'option_group' => 'wp_filepond_options_group',
			'option_name'  => 'wp_filepond_file_size_error',
			'sanitize'     => 'sanitize_text_field',
		),
		array(
			'option_group' => 'wp_filepond_options_group',
			'option_name'  => 'wp_filepond_max_file_size',
			'sanitize'     => 'absint',
		),
	);

	return apply_filters( 'wp_filepond_options', $options );
}