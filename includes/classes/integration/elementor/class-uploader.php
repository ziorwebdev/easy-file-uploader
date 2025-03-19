<?php
namespace ZIOR\DragDrop\Elementor;

use ElementorPro\Modules\Forms\Fields\Field_Base;
use Elementor\Controls_Manager;
use ElementorPro\Plugin;
use ElementorPro\Modules\Forms\Classes;

use function ZIOR\DragDrop\convert_extentions_to_mime_types;
use function ZIOR\DragDrop\get_allowed_html;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class DragDropUploader extends Field_Base {

	/**
	 * Retrieves easy dragdrop fields from the given field array and sets the 'attachment_type'.
	 *
	 * This function filters the provided fields array to return only those with
	 * 'field_type' set to 'easy-dragdrop-upload'. Additionally, it assigns the value
	 * of 'easy_dragdrop_attachment_type' to 'attachment_type' if it exists.
	 *
	 * @param array $fields The array of form fields.
	 * @return array The filtered array containing only FilePond fields with updated 'attachment_type'.
	 */
	private function get_option_setting_fields( array $fields ): array {
		$setting_fields = [];

		foreach ( $fields as $field ) {
			// Ensure the field is a dragdrop upload field
			if ( ! isset( $field['field_type'] ) || 'easy-dragdrop-upload' !== $field['field_type'] ) {
				$setting_fields[] = $field;

				continue;
			}

			// Set 'attachment_type' to 'easy_dragdrop_attachment_type' if it exists
			if ( isset( $field['easy_dragdrop_attachment_type'] ) ) {
				$field['attachment_type'] = $field['easy_dragdrop_attachment_type'];
			}

			$dragdrop_fields[] = $field;
		}

		return $setting_fields;
	}

	/**
	 * creates array of upload sizes based on server limits
	 * to use in the file_sizes control
	 * @return array
	 */
	private function get_upload_file_size_options() {
		$max_file_size = wp_max_upload_size() / pow( 1024, 2 ); //MB

		$sizes = [];

		for ( $file_size = 1; $file_size <= $max_file_size; $file_size++ ) {
			$sizes[ $file_size ] = $file_size . 'MB';
		}

		return $sizes;
	}

	/**
	 * Constructor.
	 *
	 * Initializes the Uploader class and hooks AJAX actions for handling file uploads.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Gets the field type identifier for Elementor.
	 *
	 * @return string The field type slug ('easy-dragdrop-upload').
	 */
	public function get_type() {
		return 'easy-dragdrop-upload';
	}

	/**
	 * Retrieves the display name of the field.
	 *
	 * @return string The translatable name of the field ('FilePond Upload').
	 */
	public function get_name() {
		return esc_html__( 'DragDrop Upload', 'easy-dragdrop-file-uploader' );
	}

	/**
	 * Updates Elementor form controls to include FilePond-specific settings.
	 *
	 * Adds custom settings such as max file size, allowed file types, multiple uploads, and max files.
	 *
	 * @param Widget_Base $widget The Elementor widget instance.
	 */
	public function update_controls( $widget ) {
		$elementor    = Plugin::elementor();
		$control_data = $elementor->controls_manager->get_control_from_stack( $widget->get_unique_name(), 'form_fields' );

		if ( is_wp_error( $control_data ) ) {
			return;
		}

		$field_controls = array(
			'easy_dragdrop_max_file_size' => array(
				'name'         => 'easy_dragdrop_max_file_size',
				'label'        => esc_html__( 'Max. File Size', 'easy-dragdrop-file-uploader' ),
				'type'         => Controls_Manager::SELECT,
				'condition'    => array(
					'field_type' => $this->get_type(),
				),
				'default'      => get_option( 'easy_dragdrop_max_file_size', 100 ),
				'options'      => $this->get_upload_file_size_options(),
				'description'  => esc_html__( 'If you need to increase max upload size please contact your hosting.', 'easy-dragdrop-file-uploader' ),
				'tab'          => 'content',
				'inner_tab'    => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			),
			'easy_dragdrop_file_types' => array(
				'name'         => 'easy_dragdrop_file_types',
				'label'        => esc_html__( 'Allowed File Types', 'easy-dragdrop-file-uploader' ),
				'label_block'  => true,
				'type'         => Controls_Manager::TEXT,
				'default'      => get_option( 'easy_dragdrop_file_types_allowed', '' ), 
				'ai'           => array(
					'active' => false,
				),
				'condition'    => array(
					'field_type' => $this->get_type(),
				),
				'description'  => esc_html__( 'Enter the allowed file types, separated by a comma (jpg, gif, pdf, etc).', 'easy-dragdrop-file-uploader' ),
				'tab'          => 'content',
				'inner_tab'    => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			),
			'easy_dragdrop_allow_multiple_upload' => array(
				'name'         => 'easy_dragdrop_allow_multiple_upload',
				'label'        => esc_html__( 'Multiple Files', 'easy-dragdrop-file-uploader' ),
				'type'         => Controls_Manager::SWITCHER,
				'condition'    => array(
					'field_type' => $this->get_type(),
				),
				'tab'          => 'content',
				'inner_tab'    => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			),
			'easy_dragdrop_max_files' => array(
				'name'         => 'easy_dragdrop_max_files',
				'label'        => esc_html__( 'Max. Files', 'easy-dragdrop-file-uploader' ),
				'type'         => Controls_Manager::NUMBER,
				'condition'    => array(
					'field_type'           => $this->get_type(),
					'easy_dragdrop_allow_multiple_upload' => 'yes',
				),
				'tab'          => 'content',
				'inner_tab'    => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			),
		);

		$control_data['fields'] = $this->inject_field_controls( $control_data['fields'], $field_controls );

		$widget->update_control( 'form_fields', $control_data );
	}

	/**
	 * Render the file upload input field.
	 *
	 * @param array  $item        The field settings array.
	 * @param int    $item_index  The index of the field in the form.
	 * @param object $form        The form object responsible for rendering.
	 */
	public function render( $item, $item_index, $form ) {
		// Add base attributes for the file upload field.
		$form->add_render_attribute( 'input' . $item_index, 'class', 'dragdrop-upload' );
		$form->add_render_attribute( 'input' . $item_index, 'type', 'file', true );

		// Handle multiple file uploads.
		if ( ! empty( $item['easy_dragdrop_allow_multiple_upload'] ) ) {
			$form->add_render_attribute( 'input' . $item_index, 'multiple', 'multiple' );
			$form->add_render_attribute(
				'input' . $item_index,
				'name',
				$form->get_attribute_name( $item ) . '[]',
				true
			);
		}

		$file_types   = convert_extentions_to_mime_types( $item['easy_dragdrop_file_types'] );
		$default_size = wp_max_upload_size() / 1024 / 1024;
		$attributes   = array(
				'data-filesize'  => esc_attr( $item['easy_dragdrop_max_file_size'] ?? $default_size ),
				'data-filetypes' => esc_attr( ! empty( $file_types ) ? implode( ',', $file_types ) : '' ),
				'data-label'     => esc_attr( $item['field_label'] ?? '' ),
				'data-maxfiles'  => esc_attr( $item['easy_dragdrop_max_files'] ?? '' ),
			);

		$form->add_render_attribute( 'input' . $item_index, $attributes );
		
		$input_attributes = $form->get_render_attribute_string( 'input' . $item_index );

		// Allow developers to modify the input attributes
		do_action( 'easy_dragdrop_before_render_input', $input_attributes );

		$allowed_html = get_allowed_html();

		echo wp_kses( '<input ' . $input_attributes . '>', $allowed_html );
	}

	/**
	 * Validates the uploaded file field.
	 *
	 * Checks whether the required file has been uploaded and adds an error message if missing.
	 *
	 * @param array                $field The field data.
	 * @param Classes\Form_Record  $record The form record instance.
	 * @param Classes\Ajax_Handler $ajax_handler The AJAX handler instance.
	 */
	public function validation( $field, Classes\Form_Record $record, Classes\Ajax_Handler $ajax_handler ) {
		// is the file required and missing?
		if ( $field['required'] && empty( $field['value'] ) ) {
			$ajax_handler->add_error( $field['id'], __( 'Upload a valid file', 'easy-dragdrop-file-uploader' ) );

			return;
		}

		return true;
	}

	/**
	 * Processes a form field.
	 *
	 * This function allows other developers to hook into the field processing
	 * using the `easy_dragdrop_process_field` action.
	 *
	 * @param mixed                 $field        The field data to process.
	 * @param Classes\Form_Record   $record       The form record instance.
	 * @param Classes\Ajax_Handler  $ajax_handler The AJAX handler instance.
	 */
	public function process_field( $field, Classes\Form_Record $record, Classes\Ajax_Handler $ajax_handler ) {
		// Allow other developers to process the field values.
		do_action( 'easy_dragdrop_process_field', $field, $record, $ajax_handler );
	}
}
