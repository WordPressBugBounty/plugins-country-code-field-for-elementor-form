<?php
/**
 * Class for creating the add-on of country codes to tel fields.
 *
 * @package ccfef
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	/**
	 * Class for creating the add-on of country codes to tel fields.
	 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class CFEFP_COUNTRY_FIELD_REGISTER {
	/**
	 * Plugin instance.
	 *
	 * @var CFEFP_COUNTRY_FIELD_REGISTER
	 *
	 * @access private
	 * private static $instance = null;
	 * Function to create an object of the class.
	 */
	private static $instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @return CFEFP_COUNTRY_FIELD_REGISTER
	 * @static
	 */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	/**
	 * Function for adding the country code functionality.
	 */
	public function __construct() {

        $this->add_country_code_field();
    }


	/**
	 * Function for creating an object of the CFEFP_COUNTRY_CODE_FIELD class.
	 */
	public function add_country_code_field() {
		
        require_once CCFEF_PLUGIN_DIR . 'includes/repeater-field/class-ccfef-country-code-addon.php';
        CFEFP_COUNTRY_CODE_FIELD::get_instance();
    }
}
