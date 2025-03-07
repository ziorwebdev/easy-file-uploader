<?php
namespace ZIOR\FilePond;

use ZIOR\FilePond\Elementor\FilePondUpload;
use ElementorPro\Modules\Forms\Classes\Fields;
use ElementorPro\Modules\Forms\Registrars\Form_Fields_Registrar;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the registration of FilePond uploader form fields.
 */
class Register {

	/**
	 * Singleton instance of the class.
	 *
	 * @var Register|null
	 */
	private static $instance = null;

	/**
	 * Constructor for the class.
	 * Hooks into Elementor Pro to register custom form fields.
	 */
	public function __construct() {
		add_action( 'elementor_pro/forms/fields/register', array( $this, 'register_elementor_form_fields' ), 10 );
	}

	/**
	 * Retrieves the singleton instance of the class.
	 *
	 * @return Register The singleton instance.
	 */
	public static function get_instance(): Register {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the FilePond uploader field with Elementor's form field registry.
	 *
	 * @param ElementorPro\Modules\Forms\Registrars\Form_Fields_Registrar $fields Elementor form fields object.
	 */
	public function register_elementor_form_fields( Form_Fields_Registrar $form ): void {
		$form->register( new FilePondUpload() );
	}
}