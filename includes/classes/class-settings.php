<?php
namespace ZIOR\FilePond;

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
		$template_file = PLUGIN_DIR . sprintf( 'views/%s.php', $template_name );

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
			__( 'WP FilePond', 'wp-filepond' ), // Page title
			__( 'WP FilePond', 'wp-filepond' ), // Menu title
			'manage_options',                   // Required capability
			'wp-filepond',                       // Menu slug
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
		$settings = apply_filters( 'wp_filepond_register_settings', array(
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
		));

		foreach ( $settings as $setting ) {
			register_setting( $setting['option_group'], $setting['option_name'], array(
				'sanitize_callback' => $setting['sanitize']
			) );
		}

		// Add the main settings section
		add_settings_section(
			'wp_filepond_main_section',
			__( 'Main Settings', 'wp-filepond' ),
			array( $this, 'section_callback' ),
			'wp-filepond'
		);

		// Define fields
		$fields = apply_filters( 'wp_filepond_register_fields', array(
			array(
				'id'       => 'wp_filepond_enable_preview',
				'title'    => __( 'Enable Preview', 'wp-filepond' ),
				'callback' => array( $this, 'enable_preview_callback' ),
			),
			array(
				'id'       => 'wp_filepond_max_file_size',
				'title'    => __( 'Max. File Size', 'wp-filepond' ),
				'callback' => array( $this, 'max_file_size_callback' ),
			),
			array(
				'id'       => 'wp_filepond_preview_height',
				'title'    => __( 'Preview Height', 'wp-filepond' ),
				'callback' => array( $this, 'preview_height_callback' ),
			),
			array(
				'id'       => 'wp_filepond_button_label',
				'title'    => __( 'Default Button Label', 'wp-filepond' ),
				'callback' => array( $this, 'button_label_callback' ),
			),
			array(
				'id'       => 'wp_filepond_file_types_allowed',
				'title'    => __( 'Default File Types Allowed', 'wp-filepond' ),
				'callback' => array( $this, 'file_types_allowed_callback' ),
			),
			array(
				'id'       => 'wp_filepond_file_type_error',
				'title'    => __( 'File Type Error Message', 'wp-filepond' ),
				'callback' => array( $this, 'file_type_error_message_callback' ),
			),
			array(
				'id'       => 'wp_filepond_file_size_error',
				'title'    => __( 'File Size Error Message', 'wp-filepond' ),
				'callback' => array( $this, 'file_size_error_message_callback' ),
			),
		));

		// Register each field
		foreach ( $fields as $field ) {
			add_settings_field(
				$field['id'],
				$field['title'],
				$field['callback'],
				'wp-filepond',
				'wp_filepond_main_section'
			);
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
			'page_slug'     => 'wp-filepond',
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
			esc_html__( 'Configure the FilePond integration settings. Leave the error messages blank to use the FilePond default messages.', 'wp-filepond' )
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
			esc_html__( 'Enter an error message to show when an uploaded file type is invalid. Leave blank to use the FilePond default message.', 'wp-filepond' )
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
			esc_html__( 'Enter an error message to show when an uploaded file exceeds the file size limit.', 'wp-filepond' )
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
			esc_html__( 'Default allowed file types, separated by a comma (jpg, gif, pdf, etc). Can be overridden in the field settings.', 'wp-filepond' )
		);
	}

	/**
	 * Callback function to render the "Enable Preview" checkbox in the settings page.
	 *
	 * This function retrieves the stored option for enabling file preview, ensures its validity,
	 * and outputs a checkbox input field. A description is also provided for user guidance.
	 *
	 * @return void
	 */
	public function enable_preview_callback(): void {
		// Retrieve the enable_preview option from the database, defaulting to false.
		$enable_preview = get_option( 'wp_filepond_enable_preview', false );
		$enable_preview = (bool) $enable_preview; // Ensure it's strictly boolean

		// Output the checkbox input field with proper escaping and checked attribute handling.
		printf(
			'<label><input type="checkbox" name="wp_filepond_enable_preview" value="1" %s> <span class="help-text">%s</span></label>',
			esc_attr( checked( $enable_preview, true, false ) ), // Ensure proper checkbox handling
			esc_html__( 'Check if you want to preview the file uploaded.', 'wp-filepond' )
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
			esc_html__( 'Default max. file size in MB. Can be overridden in the field settings.', 'wp-filepond' )
		);
	}

	/**
	 * Callback function to render the "Preview Height" input field in the settings page.
	 *
	 * This function retrieves the stored preview height value, ensures it is a valid integer,
	 * and outputs a number input field. A description is also provided for user guidance.
	 *
	 * @return void
	 */
	public function preview_height_callback(): void {
		// Retrieve the preview height option from the database, defaulting to 100.
		$preview_height = get_option( 'wp_filepond_preview_height', 100 );
		$preview_height = is_numeric( $preview_height ) ? intval( $preview_height ) : 100; // Ensure it's a valid integer

		// Output the input field with proper escaping.
		printf(
			'<input type="number" name="wp_filepond_preview_height" value="%d" min="1" step="1">',
			esc_attr( $preview_height ) // Escape output to prevent XSS
		);

		// Output the description with proper escaping.
		printf(
			'<p>%s</p>',
			esc_html__( 'Height of the file preview.', 'wp-filepond' )
		);
	}

	/**
	 * Callback function to render the "Upload Location" input field in the settings page.
	 *
	 * This function retrieves the stored upload location value, ensures it is a valid string,
	 * and outputs an input field for user customization.
	 *
	 * @return void
	 */
	public function upload_location_callback(): void {
		// Retrieve the upload location option from the database, defaulting to an empty string.
		$upload_location = get_option( 'wp_filepond_upload_location', '' );
	
		// Output the input field with proper escaping.
		printf(
			'<input type="text" name="wp_filepond_upload_location" value="%s">',
			esc_attr( $upload_location ) // Escape output to prevent XSS
		);

		// Output the description with proper escaping.
		printf(
			'<p>%s</p>',
			esc_html__( 'Location of the uploaded files. The directory relative to the WordPress uploads directory (e.g. "uploads/your-custom-folder"). Leave blank to use the default WordPress upload location.', 'wp-filepond' )
		);
	}
}
