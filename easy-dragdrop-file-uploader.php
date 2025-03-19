<?php
/**
 * Plugin Name:  Easy DragDrop File Uploader
 * Plugin URI:   https://github.com/ZIORWebDev/easy-dragdrop-file-uploader
 * Description:  Enhances Elementor Pro Forms with a FilePond-powered drag-and-drop uploader for seamless file uploads.
 * Author:       ZiorWeb.Dev
 * Author URI:   https://ziorweb.dev
 * Version:      1.0.0
 * Requires PHP: 8.0
 * Requires WP:  6.0
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:  easy-dragdrop-file-uploader
 * Domain Path:  /languages
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/gpl-2.0.txt>.
 */

namespace ZIOR\DragDrop;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 *
 * Handles plugin initialization, constants, includes, and hooks.
 */
class Plugin {

	/**
	 * The single instance of the plugin.
	 *
	 * @var Plugin|null
	 */
	protected static ?Plugin $instance = null;


	/**
	 * Initializes the plugin.
	 *
	 * Sets up constants, includes required files, and loads the plugin.
	 */
	private function init(): void {
		$this->setup_constants();
		$this->includes();

		$loader = Loader::get_instance();
		$loader->load();
	}

	/**
	 * Defines plugin constants.
	 *
	 * Ensures constants are only defined once to prevent conflicts.
	 */
	private function setup_constants(): void {
		if ( ! defined( "ZIOR_DRAGDROP_PLUGIN_DIR" ) ) {
			define( "ZIOR_DRAGDROP_PLUGIN_DIR", plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( "ZIOR_DRAGDROP_PLUGIN_URL" ) ) {
			define( "ZIOR_DRAGDROP_PLUGIN_URL", plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( "ZIOR_DRAGDROP_PLUGIN_FILE" ) ) {
			define( "ZIOR_DRAGDROP_PLUGIN_FILE", __FILE__ );
		}
	}

	/**
	 * Includes necessary plugin files.
	 */
	private function includes(): void {
		require_once ZIOR_DRAGDROP_PLUGIN_DIR . 'vendor/autoload.php';
		require_once ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/loader.php';
		require_once ZIOR_DRAGDROP_PLUGIN_DIR . 'includes/functions.php';
	}

	/**
	 * Constructor.
	 *
	 * Initializes hooks required for the plugin.
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Retrieves the singleton instance of the plugin.
	 *
	 * @return Plugin The single instance of the plugin.
	 */
	public static function get_instance(): Plugin {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Loads plugin text domain for translations.
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain( 'easy-dragdrop-file-uploader', false, ZIOR_DRAGDROP_PLUGIN_DIR . '/languages' );
	}

	/**
	 * Plugin activation callback.
	 *
	 * This function is executed when the plugin is activated.
	 */
	public function activate_plugin(): void {}

	/**
	 * Plugin deactivation callback.
	 *
	 * This function is executed when the plugin is deactivated.
	 */
	public function deactivate_plugin(): void {}

	/**
	 * Adds a "Settings" link on the plugins page.
	 *
	 * @param array $links The existing action links.
	 * @return array Modified action links with the "Settings" link.
	 */
	public function add_settings_link( array $links ): array {
		// Define the settings link URL
		$settings_url  = admin_url( 'options-general.php?page=easy-dragdrop-file-uploader' );
		$settings_link = sprintf( '<a href="%s">', $settings_url ) . esc_html__( 'Settings', 'easy-dragdrop-file-uploader' ) . '</a>';

		// Prepend the settings link to the existing links.
		array_unshift( $links, $settings_link );

		return $links;
	}
}

/**
 * Initializes and starts the plugin.
 */
$plugin = Plugin::get_instance();

/**
 * Registers plugin activation and deactivation hooks.
 */
register_activation_hook( ZIOR_DRAGDROP_PLUGIN_FILE, array( $plugin, 'activate_plugin' ) );
register_deactivation_hook( ZIOR_DRAGDROP_PLUGIN_FILE, array( $plugin, 'deactivate_plugin' ) );
