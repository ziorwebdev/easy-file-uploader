<?php
namespace ZIOR\FilePond;

use function ZIOR\FilePond\get_options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * Contains instance or null
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Loads a template file using WordPress's load_template function.
	 *
	 * This function checks if the template file exists before including it.
	 * It also extracts the provided data array into separate variables
	 * to be accessible inside the template.
	 *
	 * @param string $template_name Template file name (without .php extension).
	 * @param array  $data          Optional. Data to pass to the template. Default empty array.
	 *
	 * @return void
	 */
	private function load_template( string $template_name, array $data = [] ): void {
		$template_file = WP_FILEPOND_PLUGIN_DIR . sprintf( 'views/%s.php', $template_name );

		if ( ! file_exists( $template_file ) ) {
			return;
		}

		// Extract data into variables to be used in the template
		if ( ! empty( $data ) ) {
			extract( $data, EXTR_SKIP ); // Prevents overwriting existing variables
		}

		// Load the template and pass data
		load_template( $template_file, false, $data );
	}

	private function get_settings_fields(): array {
		$settings_fields = array(
			array(
				'id'       => 'wp_filepond_max_file_size',
				'title'    => __( 'Max. File Size', 'filepond-wp-integration' ),
				'callback' => array( $this, 'max_file_size_callback' ),
				'section'  => 'wp_filepond_general_section',
			),
			array(
				'id'       => 'wp_filepond_button_label',
				'title'    => __( 'Default Button Label', 'filepond-wp-integration' ),
				'callback' => array( $this, 'button_label_callback' ),
				'section'  => 'wp_filepond_general_section',
			),
			array(
				'id'       => 'wp_filepond_file_types_allowed',
				'title'    => __( 'Default File Types Allowed', 'filepond-wp-integration' ),
				'callback' => array( $this, 'file_types_allowed_callback' ),
				'section'  => 'wp_filepond_general_section',
			),
			array(
				'id'       => 'wp_filepond_file_type_error',
				'title'    => __( 'File Type Error Message', 'filepond-wp-integration' ),
				'callback' => array( $this, 'file_type_error_message_callback' ),
				'section'  => 'wp_filepond_general_section',
			),
			array(
				'id'       => 'wp_filepond_file_size_error',
				'title'    => __( 'File Size Error Message', 'filepond-wp-integration' ),
				'callback' => array( $this, 'file_size_error_message_callback' ),
				'section'  => 'wp_filepond_general_section',
			),
		);

		return apply_filters( 'wp_filepond_settings_fields', $settings_fields );
	}

	/**
	 * Returns the settings sections for the plugin.
	 *
	 * @return array The settings sections.
	 */
	private function get_settings_sections(): array {
		$settings_sections = array(
			'wp_filepond_general_section' => array(
				'title'    => __( 'General Settings', 'filepond-wp-integration' ),
				'callback' => array( $this, 'section_callback' )
			)
		);

		return apply_filters( 'wp_filepond_settings_sections', $settings_sections );
	}

	/**
	 * Class constructor.
	 *
	 * Hooks into WordPress to add the plugin's settings page and register settings.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'wp_filepond_settings_after', array( $this, 'render_marketing_card' ) );
	}

	/**
	 * Returns instance of Settings.
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
	 * Adds the settings page under the WordPress "Settings" menu.
	 *
	 * This function registers a submenu page under "Settings" in the WordPress admin dashboard.
	 * Only users with the `manage_options` capability can access the settings page.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'WP FilePond', 'filepond-wp-integration' ), // Page title
			__( 'WP FilePond', 'filepond-wp-integration' ), // Menu title
			'manage_options',                   // Required capability
			'filepond-wp-integration',                       // Menu slug
			array( $this, 'render_settings_page' ) // Callback function
		);
	}

	/**
	 * Registers settings, sections, and fields for the plugin.
	 *
	 * This function registers settings with WordPress, adds a settings section, 
	 * and defines various fields for user configuration.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// Register settings
		$options = get_options();

		foreach ( $options as $option ) {
			register_setting( $option['option_group'], $option['option_name'], array(
				'sanitize_callback' => $option['sanitize']
			) );
		}

		// Add each settings section
		$sections = $this->get_settings_sections();
		$fields   = $this->get_settings_fields();

		foreach ( $sections as $section_id => $section ) {
			add_settings_section(
				$section_id,
				$section['title'],
				$section['callback'],
				'filepond-wp-integration'
			);

			foreach ( $fields as $field ) {
				if ( $field['section'] !== $section_id ) {
					continue;
				}

				add_settings_field(
					$field['id'],
					$field['title'],
					$field['callback'],
					'filepond-wp-integration',
					$field['section']
				);
			}
		}
	}

	/**
	 * Renders the settings page.
	 *
	 * This function loads the settings template and provides necessary data.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		// Data to pass to the template
		$data = array(
			'options_group' => 'wp_filepond_options_group',
			'page_slug'     => 'filepond-wp-integration',
		);

		$this->load_template( 'settings', $data );
	}

	/**
	 * Callback function to render the section description in the settings page.
	 *
	 * This function outputs a brief description for the FilePond integration settings.
	 *
	 * @return void
	 */
	public function section_callback(): void {
		printf(
			'<p>%s</p>',
			esc_html__( 'Configure the FilePond integration settings.', 'filepond-wp-integration' )
		);
	}

	/**
	 * Callback function to render the "File Type Error Message" textarea in the settings page.
	 *
	 * This function retrieves the stored error message for invalid file types, sanitizes it,
	 * and outputs a textarea input field for user customization.
	 *
	 * @return void
	 */
	public function file_type_error_message_callback(): void {
		// Retrieve the file type error message, defaulting to an empty string.
		$message = get_option( 'wp_filepond_file_type_error', '' );
		$message = sanitize_textarea_field( $message ); // Ensure safe text output

		// Output the textarea field with proper escaping.
		printf(
			'<textarea name="wp_filepond_file_type_error" rows="3" cols="50" maxlength="120">%s</textarea>',
			esc_textarea( $message ) // Escape output to prevent XSS
		);

		// Output the description with proper escaping.
		printf(
			'<p class="help-text">%s</p>',
			esc_html__( 'Enter an error message to show when an uploaded file type is invalid. Leave blank to use the FilePond default message.', 'filepond-wp-integration' )
		);
	}

	/**
	 * Callback function to render the "File Size Error Message" textarea in the settings page.
	 *
	 * This function retrieves the stored error message for files exceeding the size limit,
	 * sanitizes it, and outputs a textarea input field for user customization.
	 *
	 * @return void
	 */
	public function file_size_error_message_callback(): void {
		// Retrieve the file size error message, defaulting to an empty string.
		$message = get_option( 'wp_filepond_file_size_error', '' );
		$message = sanitize_textarea_field( $message ); // Ensure safe text output

		// Output the textarea field with proper escaping.
		printf(
			'<textarea name="wp_filepond_file_size_error" rows="3" cols="50" maxlength="120">%s</textarea>',
			esc_textarea( $message ) // Escape output to prevent XSS
		);

		// Output the description with proper escaping.
		printf(
			'<p class="help-text">%s</p>',
			esc_html__( 'Enter an error message to show when an uploaded file exceeds the file size limit.', 'filepond-wp-integration' )
		);
	}

	/**
	 * Callback function to render the "Button Label" input field in the settings page.
	 *
	 * This function retrieves the stored button label, ensures its validity,
	 * and outputs a text input field for user customization.
	 *
	 * @return void
	 */
	public function button_label_callback(): void {
		// Retrieve the button label option from the database, defaulting to an empty string.
		$button_label = get_option( 'wp_filepond_button_label', '' );
		$button_label = sanitize_text_field( $button_label ); // Ensure safe text output

		// Output the input field with proper escaping.
		printf(
			'<input type="text" name="wp_filepond_button_label" value="%s">',
			esc_attr( $button_label ) // Escape output to prevent XSS
		);
	}

	/**
	 * Callback function to render the file types allowed input field in the settings page.
	 *
	 * This function retrieves the allowed file types from the database, sanitizes the value,
	 * and outputs an input field for users to modify it. It also includes a description 
	 * to guide users on how to format the input.
	 *
	 * @return void
	 */
	public function file_types_allowed_callback(): void {
		// Retrieve the allowed file types option from the database, defaulting to an empty string.
		$file_types = get_option( 'wp_filepond_file_types_allowed', '' );
		$file_types = is_string( $file_types ) ? sanitize_text_field( $file_types ) : ''; // Ensure it's a clean string

		// Output the input field with proper escaping to prevent XSS.
		printf(
			'<input type="text" name="wp_filepond_file_types_allowed" value="%s">',
			esc_attr( $file_types ) // Escape output to prevent XSS
		);

		// Output the description with proper escaping for security.
		printf(
			'<p>%s</p>',
			esc_html__( 'Default allowed file types, separated by a comma (jpg, gif, pdf, etc). Can be overridden in the field settings.', 'filepond-wp-integration' )
		);
	}

	/**
	 * Callback function to render the max file size setting field.
	 * 
	 * This function retrieves the max file size option from the database and 
	 * displays an input field along with a description. The value is sanitized
	 * and properly escaped for security.
	 */
	public function max_file_size_callback(): void {
		// Retrieve the max file size setting from the database, defaulting to 100 MB.
		$max_file_size = get_option( 'wp_filepond_max_file_size', 100 );
		$max_file_size = (int) $max_file_size; // Ensure it is strictly an integer.

		// Output a number input field with proper escaping and value handling.
		printf(
			'<input type="number" name="wp_filepond_max_file_size" value="%d" min="1" step="1">',
			esc_attr( $max_file_size ) // Escape for output safety.
		);

		// Display a help text for the input field.
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Default max. file size in MB. Can be overridden in the field settings.', 'filepond-wp-integration' )
		);
	}

	/**
	 * Renders the marketing card template.
	 *
	 * This function loads the marketing card template file from the 'pro/views' directory.
	 * It uses load_template() to ensure proper inclusion within the WordPress ecosystem.
	 *
	 * @return void
	 */
	public function render_marketing_card(): void {
		load_template( WP_FILEPOND_PLUGIN_DIR . 'views/marketing.php', false );
	}
}
